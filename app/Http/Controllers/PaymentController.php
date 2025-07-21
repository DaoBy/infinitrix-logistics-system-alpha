<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRequest;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;


class PaymentController extends Controller
{
    public function index(Request $request)
    {
        // Get filters from request
        $type = $request->input('type', 'prepaid');
        $status = $request->input('status', '');
        $search = $request->input('search', '');

        // Prepaid: payment_method in ['cash','gcash','bank']
        $prepaidQuery = \App\Models\DeliveryRequest::query()
            ->whereIn('payment_method', ['cash', 'gcash', 'bank'])
            ->whereIn('status', ['approved', 'pending_payment', 'completed']); // <-- include completed

        // Postpaid: payment_method not in ['cash','gcash','bank'] and not null
        $postpaidQuery = \App\Models\DeliveryOrder::query()
            ->whereHas('deliveryRequest', function($q) {
                $q->whereNotNull('payment_method')
                  ->whereNotIn('payment_method', ['cash', 'gcash', 'bank'])
                  ->whereIn('status', ['approved', 'completed']); // <-- include completed
            });

        // Apply search filter
        if ($search) {
            $prepaidQuery->where(function($q) use ($search) {
                $q->where('reference_number', 'like', "%$search%")
                  ->orWhereHas('sender', function($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  })
                  ->orWhereHas('receiver', function($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  });
            });
            $postpaidQuery->where(function($q) use ($search) {
                $q->where('id', 'like', "%$search%")
                  ->orWhereHas('deliveryRequest.sender', function($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  })
                  ->orWhereHas('deliveryRequest.receiver', function($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  });
            });
        }

        // Apply status filter
        if ($status) {
            if ($type === 'prepaid') {
                $prepaidQuery->where('payment_status', $status);
            } else {
                $postpaidQuery->where('payment_status', $status);
            }
        }

        // Eager load relationships
        $prepaidQuery->with(['sender', 'receiver', 'packages', 'payment']);
        $postpaidQuery->with([
            'deliveryRequest.sender',
            'deliveryRequest.receiver',
            'deliveryRequest.packages',
            'deliveryRequest.payment', // <-- add this line
        ]);

        // Paginate (always run both)
        $prepaidRequests = $prepaidQuery->orderByDesc('id')->paginate(5, ['*'], 'page')->withQueryString();
        $postpaidRequests = $postpaidQuery->orderByDesc('id')->paginate(5, ['*'], 'postpaid_page')->withQueryString();

        // Cast total_price to float for prepaid
        if ($prepaidRequests) {
            $prepaidRequests->getCollection()->transform(function ($item) {
                $item->total_price = (float) $item->total_price;
                return $item;
            });
        }

        return Inertia::render('Admin/Payments/Index', [
            'prepaidRequests' => $prepaidRequests,
            'postpaidRequests' => $postpaidRequests,
            'filters' => [
                'type' => $type,
                'status' => $status,
                'search' => $search,
            ]
        ]);
    }

    public function show(DeliveryRequest $delivery)
    {
        $delivery->load(['sender', 'receiver', 'packages', 'pickUpRegion', 'dropOffRegion']);

        return Inertia::render('Admin/Payments/Show', [
            'delivery' => $delivery,
            'paymentMethods' => Payment::METHODS
        ]);
    }

    public function store(Request $request, DeliveryRequest $delivery)
    {
        $validated = $request->validate([
            'method' => ['required', 'in:' . implode(',', Payment::METHODS)],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) use ($delivery) {
                    if ($value < $delivery->total_price * 0.9) {
                        $fail('Amount must be at least 90% of the total price');
                    }
                }
            ],
            'receipt_image' => ['nullable', 'image', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'in:prepaid,postpaid'], // type validation
        ]);

        // Only allow creation for prepaid payments
        if ($validated['type'] !== 'prepaid') {
            return back()->with('error', 'Postpaid payments must be collected by collectors.');
        }

        DB::transaction(function () use ($delivery, $validated, $request) {
            $receiptPath = null;
            if ($request->hasFile('receipt_image')) {
                $receiptPath = $request->file('receipt_image')->store('payment-receipts', 'public');
            }

            $paymentData = [
                'type' => $validated['type'],
                'method' => $validated['method'],
                'amount' => $validated['amount'],
                'receipt_image' => $receiptPath,
                'notes' => $validated['notes'] ?? null,
                'collected_by' => auth()->id(),
                'collected_at' => now(),
                'verified_by' => auth()->id(),
                'paid_at' => now(),
            ];

            // Create payment record
            $payment = $delivery->payment()->create($paymentData);

            // Mark delivery as paid (prepaid only)
            $delivery->markAsPaid($payment);

            // Set delivery order status to 'ready' if it was 'pending_payment'
            $deliveryOrder = $delivery->deliveryOrder;
            if ($deliveryOrder && $deliveryOrder->status === 'pending_payment') {
                $deliveryOrder->update(['status' => 'ready']);
            }

            // Send notification to customer
            NotificationService::send(
                $delivery->sender->user,
                'Payment Received ✅',
                "Payment for request #{$delivery->reference_number} has been confirmed. Your delivery will be processed shortly.",
                'payment'
            );
        });

        return redirect()->route('staff.payments.index')
            ->with('success', 'Payment recorded successfully!');
    }

    public function verify(Payment $payment)
    {
        // Authorization is handled by middleware

        // Validation - can't verify already verified payments
        if ($payment->verified_by) {
            return back()->with('error', 'Payment already verified');
        }

        DB::transaction(function () use ($payment) {
            $payment->update([
                'verified_by' => auth()->id(),
                'paid_at' => $payment->paid_at ?? now(), // Use existing paid_at if set
            ]);

            // For prepaid, ensure delivery order is ready (existing logic)
            if ($payment->type === 'prepaid' && $payment->deliveryRequest->deliveryOrder) {
                $payment->deliveryRequest->deliveryOrder->update([
                    'status' => 'ready'
                ]);
            }

            // For postpaid, mark the delivery request as paid
            if ($payment->type === 'postpaid') {
                $payment->deliveryRequest->update([
                    'payment_status' => 'paid',
                    'payment_verified' => true,
                ]);
                // Also update DeliveryOrder payment_status and payment_verified_at
                if ($payment->deliveryRequest->deliveryOrder) {
                    $payment->deliveryRequest->deliveryOrder->update([
                        'payment_status' => 'paid',
                        'payment_verified_at' => now(),
                    ]);
                }
            }

            // Send verification notification
            NotificationService::send(
                $payment->deliveryRequest->sender->user,
                'Payment Verified ✅',
                "Payment for request #{$payment->deliveryRequest->reference_number} has been verified.",
                'payment'
            );
        });

        return back()->with('success', 'Payment verified successfully');
    }

    // Show the verification page for a postpaid payment
    public function verifyView(Payment $payment)
    {
        $payment->load([
            'deliveryRequest.sender',
            'deliveryRequest.receiver',
            'collectedBy',
            'verifiedBy'
        ]);
        return Inertia::render('Admin/Payments/Verify', [
            'payment' => $payment
        ]);
    }
}