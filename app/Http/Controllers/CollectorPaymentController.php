<?php

namespace App\Http\Controllers;

use App\Models\DeliveryRequest;
use App\Models\DeliveryOrder;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CollectorPaymentController extends Controller
{
    public function dashboard()
{
    $collectorId = auth()->id();
    
    // Stats - UPDATED to include uncollectible
    $stats = [
        'pending_collections' => DeliveryOrder::whereHas('deliveryRequest', function($q) {
            $q->whereNotNull('payment_method')
              ->whereNotIn('payment_method', ['cash', 'gcash', 'bank'])
              ->whereIn('status', ['approved', 'completed'])
              ->where(function($q2) {
                  $q2->whereNull('payment_status')
                     ->orWhereIn('payment_status', ['pending', 'pending_payment', 'unpaid', 'uncollectible']);
              });
        })->count(),
        
        'pending_verification' => Payment::where('type', 'postpaid')
            ->where('collected_by', $collectorId)
            ->where('status', 'pending_verification')
            ->count(),
            
        'verified_payments' => Payment::where('type', 'postpaid')
            ->where('collected_by', $collectorId)
            ->where('status', 'verified')
            ->count(),
            
        'rejected_payments' => Payment::where('type', 'postpaid')
            ->where('collected_by', $collectorId)
            ->where('status', 'rejected')
            ->count(),
    ];

    // Recent pending collections (last 5) - UPDATED to include uncollectible
    $recentPendingCollections = DeliveryOrder::with(['deliveryRequest.sender', 'deliveryRequest.receiver'])
        ->whereHas('deliveryRequest', function($q) {
            $q->whereNotNull('payment_method')
              ->whereNotIn('payment_method', ['cash', 'gcash', 'bank'])
              ->whereIn('status', ['approved', 'completed'])
              ->where(function($q2) {
                  $q2->whereNull('payment_status')
                     ->orWhereIn('payment_status', ['pending', 'pending_payment', 'unpaid', 'uncollectible']);
              });
        })
        ->orderByDesc('id')
        ->limit(5)
        ->get();

    // Recent payment activities (last 5)
    $recentActivities = Payment::with(['deliveryRequest.sender', 'deliveryRequest.receiver'])
        ->where('type', 'postpaid')
        ->where('collected_by', $collectorId)
        ->orderByDesc('collected_at')
        ->limit(5)
        ->get();

    // Overdue collections (payment_due_date passed) - UPDATED to include uncollectible
    $overdueCollections = DeliveryOrder::with(['deliveryRequest.sender', 'deliveryRequest.receiver'])
        ->whereHas('deliveryRequest', function($q) {
            $q->whereNotNull('payment_method')
              ->whereNotIn('payment_method', ['cash', 'gcash', 'bank'])
              ->whereIn('status', ['approved', 'completed'])
              ->where(function($q2) {
                  $q2->whereNull('payment_status')
                     ->orWhereIn('payment_status', ['pending', 'pending_payment', 'unpaid', 'uncollectible']);
              })
              ->where('payment_due_date', '<', now());
        })
        ->get();

    // Performance metrics
    $totalCollections = Payment::where('type', 'postpaid')
        ->where('collected_by', $collectorId)
        ->count();
        
    $successfulCollections = Payment::where('type', 'postpaid')
        ->where('collected_by', $collectorId)
        ->where('status', 'verified')
        ->count();

    $metrics = [
        'success_rate' => $totalCollections > 0 ? round(($successfulCollections / $totalCollections) * 100) : 0,
        'avg_collection_time' => 24, // Placeholder - would need actual calculation
        'total_collected' => Payment::where('type', 'postpaid')
            ->where('collected_by', $collectorId)
            ->where('status', 'verified')
            ->whereMonth('verified_at', now()->month)
            ->sum('amount') ?? 0,
    ];

    return inertia('Collector/Payments/Dashboard', [
        'stats' => $stats,
        'recentPendingCollections' => $recentPendingCollections,
        'recentActivities' => $recentActivities,
        'overdueCollections' => $overdueCollections,
        'metrics' => $metrics,
    ]);
}
public function pending(Request $request)
{
    $status = $request->input('status', 'pending'); // CHANGE: Set default to 'pending'
    $search = $request->input('search', '');

    $query = DeliveryOrder::query()
        ->whereHas('deliveryRequest', function ($q) use ($status) {
            $q->whereNotNull('payment_method')
              ->whereNotIn('payment_method', ['cash', 'gcash', 'bank'])
              ->whereIn('status', ['approved', 'completed']);
            
            // Filter by payment_status on DeliveryRequest
            if ($status) {
                if ($status === 'pending') {
                    // Include all pending statuses except uncollectible
                    $q->where(function($q2) {
                        $q2->whereNull('payment_status')
                           ->orWhereIn('payment_status', ['pending', 'pending_payment', 'unpaid']);
                    });
                } elseif ($status === 'uncollectible') {
                    // Only include uncollectible (extended due date)
                    $q->where('payment_status', 'uncollectible');
                }
            } else {
                // When status is empty, show ALL (both regular pending AND uncollectible)
                $q->where(function($q2) {
                    $q2->whereNull('payment_status')
                       ->orWhereIn('payment_status', ['pending', 'pending_payment', 'unpaid', 'uncollectible']);
                });
            }
        });

    // Apply search filter
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->whereHas('deliveryRequest', function($q2) use ($search) {
                $q2->where('reference_number', 'like', "%$search%")
                   ->orWhereHas('sender', function($q3) use ($search) {
                       $q3->where('name', 'like', "%$search%")
                          ->orWhere('mobile', 'like', "%$search%")
                          ->orWhere('email', 'like', "%$search%");
                   })
                   ->orWhereHas('receiver', function($q3) use ($search) {
                       $q3->where('name', 'like', "%$search%")
                          ->orWhere('mobile', 'like', "%$search%")
                          ->orWhere('email', 'like', "%$search%");
                   });
            });
        });
    }

    // Eager load relationships
    $query->with(['deliveryRequest.sender', 'deliveryRequest.receiver', 'deliveryRequest.packages', 'deliveryRequest.dropOffRegion']);

    // Changed pagination from 15 to 5
    $deliveries = $query->orderByDesc('id')->paginate(6)->withQueryString();

    return inertia('Collector/Payments/Pending', [
        'deliveries' => $deliveries,
        'filters' => $request->only(['search', 'status'])
    ]);
}

    public function index(Request $request)
{
    $query = Payment::with([
        'deliveryRequest.sender', 
        'deliveryRequest.receiver', 
        'verifiedBy.employeeProfile', // Add employee profile for verifiedBy
        'rejectedBy.employeeProfile', // Add employee profile for rejectedBy
        'collectedBy.employeeProfile' // Also include for collectedBy if needed
    ])
    ->where('type', 'postpaid')
    ->where('collected_by', auth()->id())
    ->latest();

    if ($request->search) {
        $query->whereHas('deliveryRequest', function($q) use ($request) {
            $q->where('reference_number', 'like', "%{$request->search}%");
        });
    }

    // FIXED: Filter by status instead of verified_by
    if ($request->status === 'pending') {
        $query->where('status', 'pending_verification');
    } elseif ($request->status === 'verified') {
        $query->where('status', 'verified');
    } elseif ($request->status === 'rejected') {
        $query->where('status', 'rejected');
    }

    $payments = $query->paginate(6);

    return inertia('Collector/Payments/Index', [
        'payments' => $payments,
        'filters' => $request->only(['search', 'status'])
    ]);
}

    public function create(DeliveryRequest $delivery)
    {
        logger('CollectorPaymentController@create', ['delivery' => $delivery]);

        // Check if there's a rejected payment - redirect to resubmit page
        if ($delivery->payment && $delivery->payment->rejected_by) {
            return redirect()->route('collector.payments.resubmit', [
                'delivery' => $delivery->id,
                'payment' => $delivery->payment->id
            ]);
        }

        // Ensure this is a postpaid delivery and eligible for collection
        if (in_array($delivery->payment_method, ['cash', 'gcash', 'bank'])) {
            abort(403, 'Only postpaid deliveries can be collected here');
        }
        if ($delivery->payment_status === 'collected' || $delivery->payment) {
            return redirect()->route('collector.payments.pending')
                ->with('error', 'Payment already collected for this delivery.');
        }
        // Allow 'delivered', 'completed', and 'approved' statuses
        if (!in_array($delivery->status, ['delivered', 'completed', 'approved'])) {
            return redirect()->route('collector.payments.pending')
                ->with('error', 'Payment can only be collected for delivered, completed, or approved requests.');
        }

         $delivery->load([
        'sender', 
        'receiver', 
        'packages' // Add this to load packages
    ]);

    return inertia('Collector/Payments/Create', [
        'delivery' => $delivery
    ]);
}

   public function store(Request $request, DeliveryRequest $delivery)
{
    if ($delivery->payment_status === 'collected' || $delivery->payment) {
        return back()->with('error', 'Payment already collected for this delivery.');
    }

    $validated = $request->validate([
        'method' => ['required', 'in:cash'],
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
        'receipt_image' => ['required', 'image', 'max:2048'],
        'notes' => ['nullable', 'string', 'max:500'],
    ]);

    DB::transaction(function () use ($delivery, $validated, $request) {
        $receiptPath = null;
        if ($request->hasFile('receipt_image')) {
            $receiptPath = $request->file('receipt_image')->store('payment-receipts', 'public');
        }

        // Build payment data with all required fields
        $paymentData = [
            'type' => 'postpaid',
            'method' => $validated['method'],
            'source' => 'collector',
            'amount' => $validated['amount'],
            'receipt_image' => $receiptPath,
            'notes' => $validated['notes'] ?? null,
            'collected_by' => auth()->id(),
            'collected_at' => now(),
            'paid_at' => now(),
            'verified_by' => null,
            'status' => 'pending_verification',
            // Ensure these critical fields are always set
            'submitted_by_type' => get_class(auth()->user()),
            'submitted_by_id' => auth()->id(),
            'reference_number' => 'CASH-' . now()->format('Ymd-His'),
        ];

        // Add delivery_order_id if available
        if ($delivery->deliveryOrder) {
            $paymentData['delivery_order_id'] = $delivery->deliveryOrder->id;
        }

        $payment = $delivery->payment()->create($paymentData);

        // Double-check data completeness
        $payment->ensureCompleteData();

        $delivery->update([
            'payment_status' => 'pending_verification',
            'non_payment_reason' => null,
        ]);

        if ($delivery->deliveryOrder) {
            $delivery->deliveryOrder->update([
                'payment_status' => 'pending_verification'
            ]);
        }
    });

    return redirect()->route('collector.payments.index')
        ->with('success', 'Payment collection recorded successfully!');
}

    public function show(Payment $payment)
    {
        // Authorization - collector can only view their own payments
        if ($payment->collected_by !== auth()->id()) {
            abort(403, 'You can only view your own payments.');
        }

        $payment->load([
            'deliveryRequest.sender', 
            'deliveryRequest.receiver', 
            'verifiedBy', 
            'collectedBy',
            'rejectedBy',
            'deliveryRequest.pickUpRegion',
            'deliveryRequest.dropOffRegion',
            'deliveryRequest.packages'
        ]);
        
        // Add receipt image URL
        if ($payment->receipt_image) {
            $payment->receipt_image_url = Storage::url($payment->receipt_image);
        }
        
        return inertia('Collector/Payments/Show', [
            'payment' => $payment
        ]);
    }

  public function resubmit(DeliveryRequest $delivery, Payment $payment)
{
    // Authorization
    if ($payment->collected_by !== auth()->id()) {
        abort(403, 'You can only resubmit your own payments.');
    }
    
    if ($payment->delivery_request_id !== $delivery->id) {
        abort(404, 'Payment not found for this delivery.');
    }
    
    if (!$payment->rejected_by) {
        return redirect()->route('collector.payments.create', $delivery->id)
            ->with('info', 'This payment is not rejected.');
    }

    // FIXED: Load packages along with sender and receiver
    $delivery->load(['sender', 'receiver', 'packages']);

    return inertia('Collector/Payments/Resubmit', [
        'delivery' => $delivery,
        'existingPayment' => $payment
    ]);
}

   public function update(Request $request, DeliveryRequest $delivery, Payment $payment)
{
    // Authorization
    if ($payment->collected_by !== auth()->id()) {
        abort(403, 'You can only update your own payments.');
    }
    
    if ($payment->delivery_request_id !== $delivery->id) {
        abort(404, 'Payment not found for this delivery.');
    }

    if (!$payment->rejected_by) {
        return back()->with('error', 'This payment cannot be resubmitted.');
    }

    $validated = $request->validate([
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
        'receipt_image' => ['required', 'image', 'max:2048'],
        'notes' => ['nullable', 'string', 'max:500'],
    ]);

    DB::transaction(function () use ($delivery, $payment, $validated, $request) {
        $receiptPath = $request->file('receipt_image')->store('payment-receipts', 'public');

        $payment->update([
            'amount' => $validated['amount'],
            'receipt_image' => $receiptPath,
            'notes' => $validated['notes'] ?? null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'collected_at' => now(),
            'paid_at' => now(),
            'status' => 'pending_verification',
            // Ensure these fields are set on update too
            'submitted_by_type' => get_class(auth()->user()),
            'submitted_by_id' => auth()->id(),
        ]);

        // Ensure delivery_order_id is set if missing
        if (!$payment->delivery_order_id && $delivery->deliveryOrder) {
            $payment->delivery_order_id = $delivery->deliveryOrder->id;
            $payment->save();
        }

        // Double-check data completeness
        $payment->ensureCompleteData();

        $delivery->update([
            'payment_status' => 'pending_verification'
        ]);

        if ($delivery->deliveryOrder) {
            $delivery->deliveryOrder->update([
                'payment_status' => 'pending_verification'
            ]);
        }
    });

    return redirect()->route('collector.payments.show', $payment->id)
        ->with('success', 'Payment resubmitted successfully! It will be processed after verification.');
}

    public function destroy(Payment $payment)
    {
        if ($payment->collected_by !== auth()->id()) {
            abort(403, 'You can only delete your own payments.');
        }
        if ($payment->verified_by) {
            return back()->with('error', 'Cannot delete a verified payment.');
        }

        DB::transaction(function () use ($payment) {
            $delivery = $payment->deliveryRequest;
            $payment->delete();
            if ($delivery) {
                $delivery->update(['payment_status' => null]);
                // Also reset DeliveryOrder payment_status
                if ($delivery->deliveryOrder) {
                    $delivery->deliveryOrder->update(['payment_status' => null]);
                }
            }
        });

        return back()->with('success', 'Payment deleted and collection reverted.');
    }

    public function edit(Payment $payment)
    {
        if ($payment->collected_by !== auth()->id() || $payment->verified_by) {
            abort(403, 'You can only edit your own unverified payments.');
        }
        $payment->load(['deliveryRequest.sender', 'deliveryRequest.receiver']);
        return inertia('Collector/Payments/Edit', [
            'payment' => $payment
        ]);
    }

    public function updatePayment(Request $request, Payment $payment)
    {
        if ($payment->collected_by !== auth()->id() || $payment->verified_by) {
            abort(403, 'You can only update your own unverified payments.');
        }

        $validated = $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) use ($payment) {
                    if ($value < $payment->deliveryRequest->total_price * 0.9) {
                        $fail('Amount must be at least 90% of the total price');
                    }
                }
            ],
            'receipt_image' => ['nullable', 'image', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($payment, $validated, $request) {
            $receiptPath = $payment->receipt_image;
            if ($request->hasFile('receipt_image')) {
                $receiptPath = $request->file('receipt_image')->store('payment-receipts', 'public');
            }

            $payment->update([
                'amount' => $validated['amount'],
                'receipt_image' => $receiptPath,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()->route('collector.payments.show', $payment->id)
            ->with('success', 'Payment updated successfully!');
    }

    /**
     * Mark a delivery as uncollectible (customer refused to pay).
     * Only for delivered, postpaid, not yet collected/uncollectible deliveries.
     */
    public function markUncollectible(Request $request, DeliveryRequest $delivery)
{
    $validated = $request->validate([
        'non_payment_reason' => ['required', 'string', 'max:500']
    ]);

    DB::transaction(function () use ($delivery, $validated) {
        // Use 'uncollectible' status to clearly indicate extended due date
        $delivery->update([
            'payment_status' => 'uncollectible', // CLEARLY INDICATES EXTENDED DUE DATE
            'non_payment_reason' => $validated['non_payment_reason'],
            'payment_due_date' => now()->addDays(7), // Extend due date by 7 days
        ]);

        // Update DeliveryOrder status
        if ($delivery->deliveryOrder) {
            $delivery->deliveryOrder->update([
                'payment_status' => 'uncollectible'
            ]);
        }

        // Notify customer
        NotificationService::send(
            $delivery->sender->user,
            'Payment Collection Rescheduled',
            "We were unable to collect payment for your delivery #{$delivery->reference_number}. \n\nReason: {$validated['non_payment_reason']} \n\nNew Due Date: " . now()->addDays(7)->format('M j, Y') . " \n\nOur collector will reattempt within this period, or you can contact us to arrange payment.",
            'info'
        );
    });

    return back()->with('success', 'Due date extended by 7 days. Delivery marked as uncollectible.');
}
}