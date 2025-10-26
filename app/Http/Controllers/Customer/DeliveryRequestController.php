<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRequest;
use App\Models\Region;
use App\Models\Package;
use App\Models\PriceMatrix;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryRequestController extends Controller
{
    public function index(Request $request)
    {
        $customerId = auth()->user()->customer->id;

        // Get ALL delivery requests in one query - both pending and completed
        $deliveries = DeliveryRequest::with([
                'receiver', 
                'packages', 
                'pickUpRegion:id,name', 
                'dropOffRegion:id,name',
                'deliveryOrder',
                'payment'
            ])
            ->where('sender_id', $customerId)
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function($q) use ($request) {
                    $q->whereHas('receiver', function ($q) use ($request) {
                        $q->where('first_name', 'like', '%' . $request->search . '%')
                          ->orWhere('last_name', 'like', '%' . $request->search . '%')
                          ->orWhere('company_name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhere('id', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->filled('status_filter') && $request->status_filter !== 'all', function ($query) use ($request) {
                if ($request->status_filter === 'active') {
                    $query->whereIn('status', ['draft', 'pending', 'approved', 'in_transit']);
                } else {
                    $query->where('status', $request->status_filter);
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn($delivery) => [
                'id' => $delivery->id,
                'reference_number' => $delivery->reference_number,
                    'waybill_id' => $delivery->waybill?->id, // ✅ ADD THIS LINE
                'receiver' => [
                    'name' => $delivery->receiver->name ?? 
                             ($delivery->receiver->company_name ?? 'N/A'),
                    'company_name' => $delivery->receiver->company_name,
                    'first_name' => $delivery->receiver->first_name,
                    'last_name' => $delivery->receiver->last_name,
                ],
                'pick_up_region' => $delivery->pickUpRegion ? [
                    'id' => $delivery->pickUpRegion->id,
                    'name' => $delivery->pickUpRegion->name
                ] : ['name' => 'Not specified'],
                'drop_off_region' => $delivery->dropOffRegion ? [
                    'id' => $delivery->dropOffRegion->id,
                    'name' => $delivery->dropOffRegion->name
                ] : ['name' => 'Not specified'],
                'total_price' => $delivery->total_price,
                'status' => $delivery->status,
                'payment_status' => $delivery->payment_status,
                'payment_method' => $delivery->payment_method,
                'created_at' => $delivery->created_at->format('Y-m-d H:i'),
                'updated_at' => $delivery->updated_at->format('Y-m-d H:i'),
                'package_count' => $delivery->packages->count(),
                'delivery_order_id' => $delivery->deliveryOrder?->id,
                'delivery_date' => $delivery->deliveryOrder?->actual_arrival,
                'is_approved' => in_array($delivery->status, ['approved', 'in_transit', 'delivered', 'completed']),
                'is_completed' => in_array($delivery->status, ['delivered', 'completed']),
                'payment' => $delivery->payment ? [
                    'id' => $delivery->payment->id,
                    'status' => $delivery->payment->status
                ] : null
            ]);

        // Quick stats for the dashboard cards
        $stats = [
            'total' => DeliveryRequest::where('sender_id', $customerId)->count(),
            'pending' => DeliveryRequest::where('sender_id', $customerId)
                        ->whereIn('status', ['draft', 'pending'])->count(),
            'approved' => DeliveryRequest::where('sender_id', $customerId)
                         ->whereIn('status', ['approved', 'in_transit'])->count(),
            'completed' => DeliveryRequest::where('sender_id', $customerId)
                          ->whereIn('status', ['delivered', 'completed'])->count(),
            'awaiting_payment' => DeliveryRequest::where('sender_id', $customerId)
                                ->where('payment_status', 'unpaid')
                                ->whereIn('status', ['approved', 'in_transit', 'delivered'])
                                ->count(),
        ];

        return Inertia::render('Customer/DeliveryRequests/DeliveryDashboard', [
            'deliveries' => $deliveries,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status_filter']),
            'status' => session('status'),
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function previewByDelivery(DeliveryRequest $deliveryRequest)
{
    $user = auth()->user();
    
    // Check ownership
    if ($user->hasRole('customer') && $deliveryRequest->sender_id !== $user->customer->id) {
        abort(403, 'Unauthorized');
    }
    
    // Only allow for completed deliveries
    if ($deliveryRequest->status !== 'completed') {
        abort(403, 'Waybill not available for this delivery status');
    }
    
    // Find the waybill for this delivery request
    $waybill = Waybill::where('delivery_request_id', $deliveryRequest->id)->first();
    
    if (!$waybill) {
        abort(404, 'Waybill not found for this delivery');
    }
    
    // Use your existing preview logic
    if (!Storage::exists($waybill->file_path)) {
        $this->generatePdf($waybill);
    }

    return response()->file(Storage::path($waybill->file_path));
}

    public function show(DeliveryRequest $deliveryRequest)
    {
        // Simple loading of relationships
        $deliveryRequest->load([
            'sender',
            'receiver',
            'packages',
            'pickUpRegion', 
            'dropOffRegion',
            'payment',
            'deliveryOrder', // Make sure this is included!
                'waybill' // ✅ ADD THIS
        ]);

        return Inertia::render('Customer/DeliveryRequests/Show', [
            'delivery' => $deliveryRequest,
        ]);
    }

    public function edit(DeliveryRequest $deliveryRequest)
    {
        $this->authorizeRequestOwner($deliveryRequest, ['pending', 'draft']);

        $deliveryRequest->load([
            'receiver',
            'packages',
            'pickUpRegion',
            'dropOffRegion',
        ]);

        $regions = Region::where('is_active', true)->get();

        return Inertia::render('Customer/DeliveryRequests/Edit', [
            'deliveryRequest' => $deliveryRequest,
            'regions' => $regions,
        ]);
    }

    public function update(Request $request, DeliveryRequest $deliveryRequest)
    {
        $this->authorizeRequestOwner($deliveryRequest, ['pending', 'draft']);

        $validated = $request->validate([
            'receiver.first_name' => 'nullable|string|max:255',
            'receiver.last_name' => 'nullable|string|max:255',
            'receiver.company_name' => 'nullable|string|max:255',
            'pick_up_branch_id' => 'required|exists:regions,id',
            'drop_off_branch_id' => 'required|exists:regions,id',
            'packages' => 'required|array|min:1',
            'packages.*.item_code' => 'required|string|max:255',
            'packages.*.item_name' => 'required|string|max:255',
        ]);

        // Update receiver info
        $deliveryRequest->receiver->update([
            'first_name' => $validated['receiver']['first_name'] ?? null,
            'last_name' => $validated['receiver']['last_name'] ?? null,
            'company_name' => $validated['receiver']['company_name'] ?? null,
        ]);

        // Update delivery request info
        $deliveryRequest->update([
            'pick_up_branch_id' => $validated['pick_up_branch_id'],
            'drop_off_branch_id' => $validated['drop_off_branch_id'],
        ]);

        // Update packages
        $deliveryRequest->packages()->delete();
        foreach ($validated['packages'] as $pkg) {
            $deliveryRequest->packages()->create([
                'item_code' => $pkg['item_code'],
                'item_name' => $pkg['item_name'],
                'current_region_id' => $validated['pick_up_branch_id'],
            ]);
        }

        return redirect()->route('customer.delivery-requests.index')->with('success', 'Delivery request updated.');
    }

    public function calculatePrice(Request $request)
    {
        $request->validate([
            'packages' => 'required|array|min:1',
            'packages.*.height' => 'required|numeric|min:0.1',
            'packages.*.width' => 'required|numeric|min:0.1',
            'packages.*.length' => 'required|numeric|min:0.1',
            'packages.*.weight' => 'required|numeric|min:0.1',
            'pick_up_branch' => 'required|exists:regions,id',
            'drop_off_branch' => 'required|exists:regions,id',
        ]);

        $priceMatrix = PriceMatrix::first();
        if (!$priceMatrix) {
            return response()->json(['error' => 'Price matrix not configured'], 400);
        }

        $totalVolume = 0;
        $totalWeight = 0;
        $packageCount = count($request->packages);

        foreach ($request->packages as $package) {
            $volume = ($package['height'] / 100) * ($package['width'] / 100) * ($package['length'] / 100);
            $totalVolume += $volume;
            $totalWeight += $package['weight'];
        }

        $baseFee = $priceMatrix->base_fee;
        $volumeFee = $totalVolume * $priceMatrix->volume_rate;
        $weightFee = $totalWeight * $priceMatrix->weight_rate;
        $packageFee = $packageCount * $priceMatrix->package_rate;

        $totalPrice = $baseFee + $volumeFee + $weightFee + $packageFee;

        return response()->json([
            'total_price' => round($totalPrice, 2),
            'breakdown' => [
                'base_fee' => $baseFee,
                'volume_fee' => round($volumeFee, 2),
                'weight_fee' => round($weightFee, 2),
                'package_fee' => round($packageFee, 2),
            ],
            'metrics' => [
                'total_volume' => round($totalVolume, 3),
                'total_weight' => round($totalWeight, 2),
                'package_count' => $packageCount,
            ]
        ]);
    }

    private function authorizeRequestOwner(DeliveryRequest $deliveryRequest, $statuses = null)
    {
        $user = auth()->user();
        if ($deliveryRequest->sender_id !== $user->customer->id) {
            abort(403, 'Unauthorized');
        }
        if ($statuses && !in_array($deliveryRequest->status, (array)$statuses)) {
            abort(403, 'Invalid status for this operation.');
        }
    }
}