<?php

namespace App\Http\Controllers;

use App\Models\DeliveryRequest;
use App\Models\DeliveryOrder;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectorPaymentController extends Controller
{
    // 1. Show uncollected postpaid deliveries that need collection
    public function pending(Request $request)
    {
        $status = $request->input('status', '');
        $search = $request->input('search', '');

        $query = DeliveryOrder::query()
            ->whereHas('deliveryRequest', function ($q) use ($status) {
                $q->whereNotNull('payment_method')
                  ->whereNotIn('payment_method', ['cash', 'gcash', 'bank'])
                  ->whereIn('status', ['approved', 'completed']);
                // Filter by payment_status on DeliveryRequest
                if ($status) {
                    $q->where('payment_status', $status);
                } else {
                    $q->where(function($q2) {
                        $q2->whereNull('payment_status')
                           ->orWhereIn('payment_status', ['pending', 'pending_payment', 'unpaid']);
                    });
                }
            });

        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('deliveryRequest', function($q2) use ($search) {
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

        // Eager load relationships
        $query->with(['deliveryRequest.sender', 'deliveryRequest.receiver', 'deliveryRequest.packages', 'deliveryRequest.dropOffRegion']);

        $deliveries = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return inertia('Collector/Payments/Pending', [
            'deliveries' => $deliveries,
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    // 2. Show history of collected postpaid payments by the logged-in collector
    public function index(Request $request)
    {
        $query = Payment::with(['deliveryRequest.sender', 'deliveryRequest.receiver', 'verifiedBy'])
            ->where('type', 'postpaid')
            ->where('collected_by', auth()->id())
            ->latest();

        if ($request->search) {
            $query->whereHas('deliveryRequest', function($q) use ($request) {
                $q->where('reference_number', 'like', "%{$request->search}%");
            });
        }

        if ($request->status === 'pending') {
            $query->whereNull('verified_by');
        } elseif ($request->status === 'verified') {
            $query->whereNotNull('verified_by');
        }

        $payments = $query->paginate(15);

        return inertia('Collector/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    // 3. Show the form to record a new payment for a specific delivery
    public function create(DeliveryRequest $delivery)
    {
        logger('CollectorPaymentController@create', ['delivery' => $delivery]);
        // dd($delivery); // Uncomment to halt and inspect

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

        $delivery->load(['sender', 'receiver']);

        return inertia('Collector/Payments/Create', [
            'delivery' => $delivery
        ]);
    }

    // 4. Save the payment collected
    public function store(Request $request, DeliveryRequest $delivery)
    {
        if ($delivery->payment_status === 'collected' || $delivery->payment) {
            return back()->with('error', 'Payment already collected for this delivery.');
        }

        $validated = $request->validate([
            'method' => ['required', 'in:cash,gcash,bank'],
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
        ]);

        DB::transaction(function () use ($delivery, $validated, $request) {
            $receiptPath = null;
            if ($request->hasFile('receipt_image')) {
                $receiptPath = $request->file('receipt_image')->store('payment-receipts', 'public');
            }

            $payment = $delivery->payment()->create([
                'type' => 'postpaid',
                'method' => $validated['method'],
                'amount' => $validated['amount'],
                'receipt_image' => $receiptPath,
                'notes' => $validated['notes'] ?? null,
                'collected_by' => auth()->id(),
                'collected_at' => now(),
                'paid_at' => now(),
                'verified_by' => null,
            ]);

            $delivery->update([
                'payment_status' => 'collected'
            ]);

            // Also update the related DeliveryOrder's payment_status
            if ($delivery->deliveryOrder) {
                $delivery->deliveryOrder->update([
                    'payment_status' => 'collected'
                ]);
            }
        });

        return redirect()->route('collector.payments.index')
            ->with('success', 'Payment collection recorded successfully!');
    }

    // 5. View full details of a payment
    public function show(Payment $payment)
    {
        $payment->load(['deliveryRequest.sender', 'deliveryRequest.receiver', 'verifiedBy', 'collectedBy']);
        return inertia('Collector/Payments/Show', [
            'payment' => $payment
        ]);
    }

    // 6. Allows collector to delete their own unverified payments
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

    // 7. (Optional) Edit an unverified payment collection
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

    public function update(Request $request, Payment $payment)
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
        if (
            !in_array($delivery->payment_method, ['postpaid']) ||
            !in_array($delivery->status, ['delivered', 'completed', 'approved']) ||
            $delivery->payment_status === 'collected' ||
            $delivery->payment_status === 'uncollectible'
        ) {
            return back()->with('error', 'This delivery cannot be marked as uncollectible.');
        }

        $validated = $request->validate([
            'non_payment_reason' => 'required|string|max:500',
        ]);

        $delivery->update([
            'payment_status' => 'uncollectible',
            'non_payment_reason' => $validated['non_payment_reason'],
            'payment_due_date' => null,
        ]);
        // Also update DeliveryOrder payment_status
        if ($delivery->deliveryOrder) {
            $delivery->deliveryOrder->update([
                'payment_status' => 'uncollectible'
            ]);
        }

        return back()->with('success', 'Marked as uncollectible. Reason recorded.');
    }
}