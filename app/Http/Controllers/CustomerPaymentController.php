<?php

namespace App\Http\Controllers;

use App\Models\DeliveryRequest;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CustomerPaymentController extends Controller
{
    public function create(DeliveryRequest $delivery)
    {
        // Authorization - customer can only pay for their own deliveries
        if ($delivery->sender->user_id !== Auth::id()) {
            abort(403, 'You can only pay for your own deliveries.');
        }

        // Check if there's a rejected payment - redirect to resubmit page
        if ($delivery->payment && $delivery->payment->rejected_by) {
            return redirect()->route('customer.payments.resubmit', [
                'delivery' => $delivery->id,
                'payment' => $delivery->payment->id
            ]);
        }

        // Check if delivery is completed (for postpaid) or approved (for prepaid)
        $isPostpaid = !in_array($delivery->payment_method, ['cash', 'gcash', 'bank']);
        
        if ($isPostpaid && !in_array($delivery->status, ['approved', 'completed', 'delivered'])) {
    return redirect()->route('customer.delivery-requests.show', $delivery->id)
        ->with('error', 'This delivery is not yet ready for payment.');
        }

        if (!$isPostpaid && $delivery->status !== 'approved') {
            return redirect()->route('customer.delivery-requests.show', $delivery->id)
                ->with('error', 'This delivery is not yet approved for payment.');
        }

        // Check if already paid
        if ($delivery->isPaid()) {
            return redirect()->route('customer.delivery-requests.show', $delivery->id)
                ->with('info', 'This delivery has already been paid.');
        }

        $delivery->load(['sender', 'receiver', 'packages']);

        // Determine payment type based on delivery payment method
        $paymentType = $isPostpaid ? 'postpaid' : 'prepaid';
        
        $availableMethods = $isPostpaid ? ['gcash', 'bank'] : ['gcash', 'bank'];

        return inertia('Customer/Payments/Create', [
            'delivery' => $delivery,
            'paymentType' => $paymentType,
            'paymentMethods' => $availableMethods
        ]);
    }

    // NEW METHOD: Show resubmission form for rejected payments
    public function resubmit(DeliveryRequest $delivery, Payment $payment)
    {
        // Authorization
        if ($delivery->sender->user_id !== Auth::id()) {
            abort(403, 'You can only pay for your own deliveries.');
        }
        
        if ($payment->delivery_request_id !== $delivery->id) {
            abort(404, 'Payment not found for this delivery.');
        }
        
        if (!$payment->rejected_by) {
            return redirect()->route('customer.payments.create', $delivery->id)
                ->with('info', 'This payment is not rejected.');
        }

        $delivery->load(['sender', 'receiver', 'packages']);
        $isPostpaid = !in_array($delivery->payment_method, ['cash', 'gcash', 'bank']);
        $availableMethods = $isPostpaid ? ['gcash', 'bank'] : ['gcash', 'bank'];

        return inertia('Customer/Payments/Resubmit', [
            'delivery' => $delivery,
            'existingPayment' => $payment,
            'paymentMethods' => $availableMethods
        ]);
    }

    // NEW METHOD: Update existing rejected payment
    public function update(Request $request, DeliveryRequest $delivery, Payment $payment)
    {
        // Authorization
        if ($delivery->sender->user_id !== Auth::id()) {
            abort(403, 'You can only pay for your own deliveries.');
        }
        
        if ($payment->delivery_request_id !== $delivery->id) {
            abort(404, 'Payment not found for this delivery.');
        }

        // Check if payment can be updated (must be rejected)
        if (!$payment->rejected_by) {
            return back()->with('error', 'This payment cannot be resubmitted.');
        }

        // Check delivery status based on payment type
        $isPostpaid = !in_array($delivery->payment_method, ['cash', 'gcash', 'bank']);
        
        if ($isPostpaid && !in_array($delivery->status, ['approved', 'completed', 'delivered'])) {
            return back()->with('error', 'This delivery is not yet completed.');
        }

        if (!$isPostpaid && $delivery->status !== 'approved') {
            return back()->with('error', 'This delivery is not yet approved for payment.');
        }

        if ($delivery->isPaid()) {
            return back()->with('error', 'This delivery has already been paid.');
        }
        
        // Determine payment type and source
        $paymentType = $isPostpaid ? 'postpaid' : 'prepaid';
        $paymentSource = $isPostpaid ? 'customer_online_postpaid' : 'customer_online';
        $availableMethods = $isPostpaid ? ['gcash', 'bank'] : ['gcash', 'bank'];

        $validated = $request->validate([
            'method' => ['required', Rule::in($availableMethods)],
            'reference_number' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request, $payment) {
                    // Check for duplicate reference numbers, excluding current payment
                    if (Payment::where('reference_number', $value)
                        ->where('id', '!=', $payment->id)
                        ->exists()) {
                        $fail('This reference number has already been used. Please check and try again.');
                    }
                }
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) use ($delivery) {
                    if ($value < $delivery->total_price * 0.9) {
                        $fail('Amount must be at least 90% of the total price');
                    }
                    if ($value > $delivery->total_price * 1.1) {
                        $fail('Amount cannot exceed 110% of the total price');
                    }
                }
            ],
            'receipt_image' => ['required', 'image', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($delivery, $payment, $validated, $request, $paymentSource) {
            $receiptPath = $request->file('receipt_image')->store('payment-receipts', 'public');

            // UPDATE: Change payment method if originally cash but paying online
            if ($delivery->payment_method === 'cash' && in_array($validated['method'], ['gcash', 'bank'])) {
                $delivery->update(['payment_method' => $validated['method']]);
            }

            // Update the existing payment instead of creating new
           $payment->update([
            'method' => $validated['method'],
            'reference_number' => $validated['reference_number'],
            'source' => $paymentSource,
            'amount' => $validated['amount'],
            'receipt_image' => $receiptPath,
            'notes' => $validated['notes'] ?? null,
            'rejected_by' => null,   // Clears rejector
            'rejected_at' => null,   // Clears rejection time
            'rejection_reason' => null, // Clears rejection reason
            'submitted_by_type' => get_class(Auth::user()),
            'submitted_by_id' => Auth::id(),
            'paid_at' => now(),
            'status' => 'pending_verification', // Reset the status
        ]);

            // Update delivery request payment status
            $delivery->update([
                'payment_status' => 'pending_verification',
                'payment_source' => 'online'
            ]);

            // Update delivery order payment status if exists
            if ($delivery->deliveryOrder) {
                $delivery->deliveryOrder->update([
                    'payment_status' => 'pending_verification'
                ]);
            }

            // Send notification to staff for verification
            // NotificationService::notifyStaffOfPendingPayment($payment);
        });

        return redirect()->route('customer.payments.show', $payment->id)
            ->with('success', 'Payment resubmitted successfully! It will be processed after verification.');
    }

   public function store(Request $request, DeliveryRequest $delivery)
{
    // Authorization
    if ($delivery->sender->user_id !== Auth::id()) {
        abort(403, 'You can only pay for your own deliveries.');
    }

    // Check if there's an existing rejected payment - redirect to update
    if ($delivery->payment && $delivery->payment->rejected_by) {
        return redirect()->route('customer.payments.resubmit', [
            'delivery' => $delivery->id,
            'payment' => $delivery->payment->id
        ]);
    }

    // Your existing delivery status checks...
    $isPostpaid = !in_array($delivery->payment_method, ['cash', 'gcash', 'bank']);
    
    if ($isPostpaid && !in_array($delivery->status, ['approved', 'completed', 'delivered'])) {
        return back()->with('error', 'This delivery is not yet completed.');
    }

    if (!$isPostpaid && $delivery->status !== 'approved') {
        return back()->with('error', 'This delivery is not yet approved for payment.');
    }

    if ($delivery->isPaid()) {
        return back()->with('error', 'This delivery has already been paid.');
    }
    
    // Determine payment type
    $paymentType = $isPostpaid ? 'postpaid' : 'prepaid';
    $paymentSource = $isPostpaid ? 'customer_online_postpaid' : 'customer_online';
    
    $availableMethods = $isPostpaid ? ['gcash', 'bank'] : ['gcash', 'bank'];

    $validated = $request->validate([
        'method' => ['required', Rule::in($availableMethods)],
        'reference_number' => [
            'required',
            'string',
            'max:255',
            function ($attribute, $value, $fail) use ($request) {
                if (Payment::where('reference_number', $value)->exists()) {
                    $fail('This reference number has already been used. Please check and try again.');
                }
            }
        ],
        'amount' => [
            'required',
            'numeric',
            'min:0.01',
            function ($attribute, $value, $fail) use ($delivery) {
                if ($value < $delivery->total_price * 0.9) {
                    $fail('Amount must be at least 90% of the total price');
                }
                if ($value > $delivery->total_price * 1.1) {
                    $fail('Amount cannot exceed 110% of the total price');
                }
            }
        ],
        'receipt_image' => ['required', 'image', 'max:2048'],
        'notes' => ['nullable', 'string', 'max:500'],
    ]);

    DB::transaction(function () use ($delivery, $validated, $request, $paymentType, $paymentSource) {
        $receiptPath = $request->file('receipt_image')->store('payment-receipts', 'public');

        // UPDATE: Change payment method if originally cash but paying online
        if ($delivery->payment_method === 'cash' && in_array($validated['method'], ['gcash', 'bank'])) {
            $delivery->update(['payment_method' => $validated['method']]);
        }

        // Build payment data with all required fields
        $paymentData = [
            'type' => $paymentType,
            'method' => $validated['method'],
            'reference_number' => $validated['reference_number'],
            'source' => $paymentSource,
            'amount' => $validated['amount'],
            'receipt_image' => $receiptPath,
            'notes' => $validated['notes'] ?? null,
            'submitted_by_type' => get_class(Auth::user()),
            'submitted_by_id' => Auth::id(),
            'paid_at' => now(),
            'status' => 'pending_verification',
        ];

        // Add delivery_order_id if available
        if ($delivery->deliveryOrder) {
            $paymentData['delivery_order_id'] = $delivery->deliveryOrder->id;
        }

        $payment = $delivery->payment()->create($paymentData);

        // Double-check data completeness
        $payment->ensureCompleteData();

        // Update delivery request payment status
        $delivery->update([
            'payment_status' => 'pending_verification',
            'payment_source' => 'online'
        ]);

        // Update delivery order payment status if exists
        if ($delivery->deliveryOrder) {
            $delivery->deliveryOrder->update([
                'payment_status' => 'pending_verification'
            ]);
        }
    });

    // Redirect to the payment show page instead of delivery show
    $paymentId = $delivery->payment()->latest()->first()->id;
    return redirect()->route('customer.payments.show', $paymentId)
        ->with('success', 'Payment submitted successfully! It will be processed after verification.');
}

    // ADD THIS MISSING METHOD
    public function show(Payment $payment)
    {
        // Authorization - customer can only view their own payments
        if ($payment->deliveryRequest->sender->user_id !== Auth::id()) {
            abort(403, 'You can only view your own payments.');
        }

        $payment->load([
            'deliveryRequest.sender',
            'deliveryRequest.receiver', 
            'deliveryRequest.packages',
            'deliveryRequest.pickUpRegion',
            'deliveryRequest.dropOffRegion',
            'verifiedBy',
            'rejectedBy'
        ]);

        // Add the full URL for the receipt image
        if ($payment->receipt_image) {
            $payment->receipt_image_url = Storage::url($payment->receipt_image);
        }

        return inertia('Customer/Payments/Show', [
            'payment' => $payment
        ]);
    }
}