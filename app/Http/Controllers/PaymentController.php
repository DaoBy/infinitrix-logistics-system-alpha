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
    // Main dashboard - replaces multiple index methods
   public function dashboard(Request $request)
{
    $activeTab = $request->input('tab', 'verification');
    
    // TAB 1: Verification Queue Data
    $verificationQuery = Payment::with([
        'deliveryRequest.sender', 
        'deliveryRequest.receiver', 
        'submittedBy',
        'verifiedBy',
        'rejectedBy'
    ])->where('status', 'pending_verification');
    
    // Apply search filter for verification
    if ($request->search && $activeTab === 'verification') {
        $verificationQuery->where(function($q) use ($request) {
            $q->where('reference_number', 'like', "%{$request->search}%")
              ->orWhereHas('deliveryRequest', function($q2) use ($request) {
                  $q2->where('reference_number', 'like', "%{$request->search}%")
                     ->orWhereHas('sender', function($q3) use ($request) {
                         $q3->where('name', 'like', "%{$request->search}%");
                     });
              })
              ->orWhere('amount', 'like', "%{$request->search}%");
        });
    }
    
    // Apply payment method filter
    if ($request->payment_method && $activeTab === 'verification') {
        $verificationQuery->where('method', $request->payment_method);
    }
    
    // Apply payment source filter
    if ($request->payment_source && $activeTab === 'verification') {
        $verificationQuery->where('source', $request->payment_source);
    }
    
    $verificationPayments = $verificationQuery->latest()->paginate(10, ['*'], 'verification_page');

   // TAB 2: Collection Management Data
$collectionSearch = $request->input('search', '');
$collectionType = $request->input('collection_type', 'all');

// Prepaid deliveries needing payment - EXCLUDE verified payments
$prepaidQuery = DeliveryRequest::query()
    ->with(['sender', 'receiver', 'packages', 'payment'])
    ->whereIn('payment_method', ['cash', 'gcash', 'bank'])
    ->whereIn('status', ['approved', 'pending_payment', 'completed'])
    ->where(function($q) {
        $q->whereNull('payment_status')
          ->orWhere('payment_status', 'pending')
          ->orWhere('payment_status', 'unpaid')
          ->orWhere('payment_status', '!=', 'paid');
    })
    // Exclude deliveries with verified payments
    ->whereDoesntHave('payment', function($q) {
        $q->where('status', 'verified');
    });

// Postpaid deliveries needing collection - EXCLUDE verified payments
$postpaidQuery = DeliveryOrder::query()
    ->with([
        'deliveryRequest.sender',
        'deliveryRequest.receiver', 
        'deliveryRequest.packages',
        'deliveryRequest.payment',
        'driver'
    ])
    ->whereHas('deliveryRequest', function($q) {
        $q->where('payment_method', 'postpaid')
          ->whereIn('status', ['approved', 'completed', 'delivered'])
          ->where(function($q2) {
              $q2->whereNull('payment_status')
                 ->orWhereIn('payment_status', ['pending', 'pending_payment', 'unpaid']);
          })
          // Exclude deliveries with verified payments
          ->whereDoesntHave('payment', function($q3) {
              $q3->where('status', 'verified');
          });
    });

    // Apply search to both prepaid and postpaid
    if ($collectionSearch && $activeTab === 'collection') {
        $prepaidQuery->where(function($q) use ($collectionSearch) {
            $q->where('reference_number', 'like', "%$collectionSearch%")
              ->orWhereHas('sender', function($q2) use ($collectionSearch) {
                  $q2->where('name', 'like', "%$collectionSearch%");
              })
              ->orWhere('total_price', 'like', "%$collectionSearch%");
        });
        
        $postpaidQuery->where(function($q) use ($collectionSearch) {
            $q->whereHas('deliveryRequest', function($q2) use ($collectionSearch) {
                  $q2->where('reference_number', 'like', "%$collectionSearch%")
                     ->orWhereHas('sender', function($q3) use ($collectionSearch) {
                         $q3->where('name', 'like', "%$collectionSearch%");
                     })
                     ->orWhere('total_price', 'like', "%$collectionSearch%");
              });
        });
    }

    // Filter by collection type
    if ($collectionType === 'prepaid') {
        $postpaidQuery->whereRaw('1 = 0'); // Exclude postpaid
    } elseif ($collectionType === 'postpaid') {
        $prepaidQuery->whereRaw('1 = 0'); // Exclude prepaid
    }

    $prepaidRequests = $prepaidQuery->orderByDesc('id')->paginate(10, ['*'], 'prepaid_page');
    
    $postpaidRequests = $postpaidQuery->orderByDesc('id')->paginate(10, ['*'], 'postpaid_page');

    // TAB 3: Payment History Data
    $historyQuery = Payment::with([
        'deliveryRequest.sender',
        'deliveryRequest.receiver',
        'verifiedBy',
        'rejectedBy'
    ])->whereIn('status', ['verified', 'rejected', 'cancelled']);

    // Apply search for history
    if ($request->search && $activeTab === 'history') {
        $historyQuery->where(function($q) use ($request) {
            $q->where('reference_number', 'like', "%{$request->search}%")
              ->orWhereHas('deliveryRequest', function($q2) use ($request) {
                  $q2->where('reference_number', 'like', "%{$request->search}%")
                     ->orWhereHas('sender', function($q3) use ($request) {
                         $q3->where('name', 'like', "%{$request->search}%");
                     });
              })
              ->orWhere('amount', 'like', "%{$request->search}%");
        });
    }

    // Apply history status filter
    if ($request->history_status && $activeTab === 'history') {
        $historyQuery->where('status', $request->history_status);
    }

    $historyPayments = $historyQuery->latest()->paginate(10, ['*'], 'history_page');

    // Stats for badges
    $stats = [
        'pending_verification' => Payment::where('status', 'pending_verification')->count(),
        'needs_collection' => $prepaidQuery->clone()->count() + $postpaidQuery->clone()->count(),
        'verified_today' => Payment::where('status', 'verified')
            ->whereDate('verified_at', today())->count(),
    ];

    // Return with proper pagination structure for Inertia
    return Inertia::render('Admin/Payments/Dashboard', [
        'activeTab' => $activeTab,
        'verificationPayments' => [
            'data' => $verificationPayments->items(),
            'meta' => [
                'current_page' => $verificationPayments->currentPage(),
                'last_page' => $verificationPayments->lastPage(),
                'per_page' => $verificationPayments->perPage(),
                'total' => $verificationPayments->total(),
                'from' => $verificationPayments->firstItem(),
                'to' => $verificationPayments->lastItem(),
            ]
        ],
        'prepaidRequests' => [
            'data' => $prepaidRequests->items(),
            'meta' => [
                'current_page' => $prepaidRequests->currentPage(),
                'last_page' => $prepaidRequests->lastPage(),
                'per_page' => $prepaidRequests->perPage(),
                'total' => $prepaidRequests->total(),
                'from' => $prepaidRequests->firstItem(),
                'to' => $prepaidRequests->lastItem(),
            ]
        ],
        'postpaidRequests' => [
            'data' => $postpaidRequests->items(),
            'meta' => [
                'current_page' => $postpaidRequests->currentPage(),
                'last_page' => $postpaidRequests->lastPage(),
                'per_page' => $postpaidRequests->perPage(),
                'total' => $postpaidRequests->total(),
                'from' => $postpaidRequests->firstItem(),
                'to' => $postpaidRequests->lastItem(),
            ]
        ],
        'historyPayments' => [
            'data' => $historyPayments->items(),
            'meta' => [
                'current_page' => $historyPayments->currentPage(),
                'last_page' => $historyPayments->lastPage(),
                'per_page' => $historyPayments->perPage(),
                'total' => $historyPayments->total(),
                'from' => $historyPayments->firstItem(),
                'to' => $historyPayments->lastItem(),
            ]
        ],
        'stats' => $stats,
        'filters' => $request->all(),
    ]);
}
    // Show form to record over-the-counter payment
    public function create(Request $request)
    {
        $deliveryId = $request->input('delivery_id');
        
        $selectedDelivery = null;
        
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
        }

        return Inertia::render('Admin/Payments/RecordPayment', [
            'delivery' => $selectedDelivery
        ]);
    }

    // Store over-the-counter payment
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
                'amount' => $delivery->total_price,
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

            $delivery->packages()->update(['status' => 'preparing']);
            
            if ($delivery->deliveryOrder) {
                $delivery->deliveryOrder->update([
                    'payment_status' => 'paid',
                    'payment_verified_at' => now(),
                    'status' => 'ready',
                ]);
            }

            NotificationService::send(
                $delivery->sender->user,
                'Payment Received ✅',
                "Payment for request #{$delivery->reference_number} has been confirmed. Your delivery will be processed shortly.",
                'payment'
            );
        });

        return redirect()->route('staff.payments.dashboard')
            ->with('success', 'Payment recorded successfully!');
    }

    // Show payment details
    public function show(Payment $payment)
    {
        if (!$payment || !$payment->id) {
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

        if ($payment->receipt_image) {
            $payment->receipt_image_url = Storage::url($payment->receipt_image);
        }

        return Inertia::render('Admin/Payments/Show', [
            'payment' => $payment
        ]);
    }

    // Verify payment
   public function verify(Payment $payment)
{
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

        // Update delivery request payment status for ALL payment types
        $payment->deliveryRequest->update([
            'payment_status' => 'paid',
            'payment_verified' => true,
        ]);

        // Update delivery order based on payment type
        if ($payment->deliveryRequest->deliveryOrder) {
            $payment->deliveryRequest->deliveryOrder->update([
                'status' => 'ready',
                'payment_status' => 'paid',
                'payment_verified_at' => now(),
            ]);
        }

        // Update packages status for prepaid payments
        if ($payment->type === 'prepaid') {
            $payment->deliveryRequest->packages()->update(['status' => 'preparing']);
        }

        NotificationService::send(
            $payment->deliveryRequest->sender->user,
            'Payment Verified ✅',
            "Payment for request #{$payment->deliveryRequest->reference_number} has been verified.",
            'payment'
        );
    });

    return back()->with('success', 'Payment verified successfully');
}

    // Show verification page
    public function verifyView(Payment $payment)
    {
        $payment->load([
            'deliveryRequest.sender',
            'deliveryRequest.receiver',
            'collectedBy',
            'verifiedBy'
        ]);
        
        if ($payment->receipt_image) {
            $payment->receipt_image_url = Storage::url($payment->receipt_image);
        }
        
        return Inertia::render('Admin/Payments/Verify', [
            'payment' => $payment
        ]);
    }

    // Reject payment
    public function reject(Payment $payment, Request $request)
    {
        if ($payment->verified_by) {
            return back()->with('error', 'Payment already verified');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500']
        ]);

        DB::transaction(function () use ($payment, $validated) {
            $payment->update([
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
                'status' => 'rejected',
            ]);

            $payment->deliveryRequest->update([
                'payment_status' => 'rejected'
            ]);

            if ($payment->deliveryRequest->deliveryOrder) {
                $payment->deliveryRequest->deliveryOrder->update([
                    'payment_status' => 'rejected'
                ]);
            }
        });

        return back()->with('success', 'Payment rejected successfully. Customer can resubmit with corrections.');
    }
}