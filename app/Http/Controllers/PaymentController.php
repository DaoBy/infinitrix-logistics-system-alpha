<?php

namespace App\Http\Controllers;

use App\Models\DeliveryRequest;
use App\Models\Payment;
use App\Models\DeliveryOrder;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $prepaidQuery = DeliveryRequest::query()
            ->whereIn('payment_method', ['cash', 'gcash', 'bank'])
            ->whereIn('status', ['approved', 'pending_payment', 'completed']);

        // Postpaid: payment_method not in ['cash','gcash','bank'] and not null
        $postpaidQuery = DeliveryOrder::query()
            ->whereHas('deliveryRequest', function($q) {
                $q->whereNotNull('payment_method')
                  ->whereNotIn('payment_method', ['cash', 'gcash', 'bank'])
                  ->whereIn('status', ['approved', 'completed']);
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
            'deliveryRequest.payment',
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

    // NEW: Show form to record over-the-counter payment
    // In PaymentController.php
public function create(Request $request)
{
    $deliveryId = $request->input('delivery_id');
    
    \Log::info('Payment create method called', [
        'delivery_id' => $deliveryId,
        'request_all' => $request->all()
    ]);
    
    $selectedDelivery = null;
    
    // If a specific delivery ID is provided, get that delivery with ALL necessary relationships
    if ($deliveryId) {
        $selectedDelivery = DeliveryRequest::with([
            'sender', 
            'receiver',
            'packages',
            'pickUpRegion',
            'dropOffRegion'
        ])
        ->whereIn('status', ['approved', 'completed'])
        ->where(function($query) {
            $query->whereNull('payment_status')
                  ->orWhere('payment_status', 'pending')
                  ->orWhere('payment_status', 'unpaid');
        })
        ->find($deliveryId);

        \Log::info('Delivery found', [
            'delivery' => $selectedDelivery ? $selectedDelivery->toArray() : null,
            'has_packages' => $selectedDelivery ? $selectedDelivery->packages->count() : 0
        ]);
    }

    return Inertia::render('Admin/Payments/RecordPayment', [
        'delivery' => $selectedDelivery
    ]);
}

    // NEW: Store over-the-counter payment
    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_request_id' => 'required|exists:delivery_requests,id',
            'method' => 'required|in:cash,gcash,bank',
            'amount_received' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) use ($request) {
                    $delivery = DeliveryRequest::find($request->delivery_request_id);
                    if ($delivery && $value < $delivery->total_price) {
                        $fail('Amount must be at least the total price');
                    }
                }
            ],
            'reference_number' => 'required_if:method,gcash,bank',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $delivery = DeliveryRequest::find($validated['delivery_request_id']);
            
            // UPDATE: Override the payment method to reflect actual payment
            $delivery->update([
                'payment_method' => $validated['method'],
                'payment_status' => 'paid',
                'payment_verified' => true
            ]);
            
            $payment = $delivery->payment()->create([
                'type' => $delivery->isPrepaid() ? 'prepaid' : 'postpaid',
                'method' => $validated['method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'source' => 'branch_staff',
                'amount' => $delivery->total_price, // Use the actual total, not amount received
                'notes' => $validated['notes'] ?? null,
                'collected_by' => auth()->id(),
                'collected_at' => now(),
                'submitted_by_type' => get_class(auth()->user()),
                'submitted_by_id' => auth()->id(),
                'paid_at' => now(),
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'status' => 'verified',
            ]);

            // Update all packages to 'preparing' status
            $delivery->packages()->update(['status' => 'preparing']);
            
            if ($delivery->deliveryOrder) {
                $delivery->deliveryOrder->update([
                    'payment_status' => 'paid',
                    'payment_verified_at' => now(),
                    'status' => 'ready',
                ]);
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

    // FIXED: Show payment details
   public function show(Payment $payment)
{
    if (!$payment || !$payment->id) {
        \Log::error('Payment not found or invalid in show method', [
            'payment' => $payment,
        ]);
        abort(404, 'Payment not found.');
    }
    $payment->load([
        'deliveryRequest.sender',
        'deliveryRequest.receiver', 
        'deliveryRequest.packages',
        'deliveryRequest.pickUpRegion',
        'deliveryRequest.dropOffRegion',
        'verifiedBy',
        'rejectedBy',
        'collectedBy',
        'submittedBy'
    ]);

    \Log::info('Payment show method', [
        'payment_id' => $payment->id,
        'delivery_request_loaded' => $payment->relationLoaded('deliveryRequest'),
        'delivery_request_id' => $payment->delivery_request_id
    ]);

    // Add the full URL for the receipt image as a regular property
    if ($payment->receipt_image) {
        $payment->receipt_image_url = Storage::url($payment->receipt_image);
    }

    return Inertia::render('Admin/Payments/Show', [
        'payment' => $payment
    ]);
}
    // REMOVED: The old store method that required DeliveryRequest parameter
    // This was causing conflicts with the new store method

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
                'verified_at' => now(),
                'status' => 'verified',
                'paid_at' => $payment->paid_at ?? now(),
            ]);

            // For prepaid, ensure delivery order is ready (existing logic)
            if ($payment->type === 'prepaid' && $payment->deliveryRequest->deliveryOrder) {
                $payment->deliveryRequest->deliveryOrder->update([
                    'status' => 'ready',
                    'payment_status' => 'paid',
                    'payment_verified_at' => now(),
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
                        'status' => 'ready',
                    ]);
                }
            }

            // For postpaid online, update delivery request status
            if ($payment->type === 'postpaid' && $payment->source === 'customer_online_postpaid') {
                $payment->deliveryRequest->update([
                    'payment_status' => 'paid',
                    'payment_verified' => true,
                ]);
                // Also update DeliveryOrder payment_status
                if ($payment->deliveryRequest->deliveryOrder) {
                    $payment->deliveryRequest->deliveryOrder->update([
                        'payment_status' => 'paid',
                        'payment_verified_at' => now(),
                        'status' => 'ready',
                    ]);
                }
            }

            // For prepaid online payments, update delivery request status
            if ($payment->type === 'prepaid') {
                $payment->deliveryRequest->update([
                    'payment_status' => 'paid',
                    'payment_verified' => true,
                ]);
                // Also update DeliveryOrder payment_status if exists
                if ($payment->deliveryRequest->deliveryOrder) {
                    $payment->deliveryRequest->deliveryOrder->update([
                        'payment_status' => 'paid',
                        'payment_verified_at' => now(),
                        'status' => 'ready',
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

    // Show the verification page for a payment
    public function verifyView(Payment $payment)
    {
        $payment->load([
            'deliveryRequest.sender',
            'deliveryRequest.receiver',
            'collectedBy',
            'verifiedBy'
        ]);
        
        // Add receipt image URL
        if ($payment->receipt_image) {
            $payment->receipt_image_url = Storage::url($payment->receipt_image);
        }
        
        return Inertia::render('Admin/Payments/Verify', [
            'payment' => $payment
        ]);
    }

    public function verificationIndex(Request $request)
    {
        $status = $request->input('status', 'pending');
        $search = $request->input('search', '');
        $source = $request->input('source', '');

        $query = Payment::with([
            'deliveryRequest.sender', 
            'deliveryRequest.receiver', 
            'submittedBy',
            'verifiedBy',
            'rejectedBy'
        ])->latest();

        // Filter by verification status - robust version
        if ($status === 'pending') {
            $query->where('status', 'pending_verification');
        } elseif ($status === 'verified') {
            $query->where('status', 'verified');
        } elseif ($status === 'rejected') {
            $query->where('status', 'rejected');
        }
        // Keep the legacy checks as a fallback for old data if needed
        if ($status === 'pending') {
            $query->orWhere(function($q) {
                $q->whereNull('status')
                  ->whereNull('verified_by')
                  ->whereNull('rejected_by');
            });
        }

        // Filter by payment source
        if ($source) {
            $query->where('source', $source);
        }

        // Search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('reference_number', 'like', "%$search%")
                  ->orWhereHas('deliveryRequest', function($q2) use ($search) {
                      $q2->where('reference_number', 'like', "%$search%")
                         ->orWhereHas('sender', function($q3) use ($search) {
                             $q3->where('name', 'like', "%$search%");
                         })
                         ->orWhereHas('receiver', function($q3) use ($search) {
                             $q3->where('name', 'like', "%$search%");
                         });
                  });
            });
        }

        $payments = $query->paginate(15);

        return Inertia::render('Admin/Payments/VerificationIndex', [
            'payments' => $payments,
            'filters' => [
                'status' => $status,
                'search' => $search,
                'source' => $source,
            ],
            'sources' => Payment::SOURCES
        ]);
    }

    public function reject(Payment $payment, Request $request)
    {
        // Validation - can't reject already verified payments
        if ($payment->verified_by) {
            return back()->with('error', 'Payment already verified');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500']
        ]);

        DB::transaction(function () use ($payment, $validated) {
            $updateData = [
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
                'status' => 'rejected',
            ];

            $payment->update($updateData);

            // Reset delivery payment status to allow resubmission
            $payment->deliveryRequest->update([
                'payment_status' => 'rejected'
            ]);

            // Also update DeliveryOrder payment_status if exists
            if ($payment->deliveryRequest->deliveryOrder) {
                $payment->deliveryRequest->deliveryOrder->update([
                    'payment_status' => 'rejected'
                ]);
            }

            // Send notification to the submitter
            // NotificationService::sendPaymentRejectedNotification($payment);
        });

        return back()->with('success', 'Payment rejected successfully. Customer can resubmit with corrections.');
    }
}