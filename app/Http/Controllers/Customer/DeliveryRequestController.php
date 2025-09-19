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

    // Delivery Requests: Draft & Pending
    $requests = DeliveryRequest::with([
            'receiver', 
            'packages', 
            'pickUpRegion:id,name', 
            'dropOffRegion:id,name'
        ])
        ->where('sender_id', $customerId)
        ->whereIn('status', ['pending', 'draft'])
        ->when($request->filled('request_search'), function ($query) use ($request) {
            $query->whereHas('receiver', function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->request_search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->request_search . '%')
                  ->orWhere('company_name', 'like', '%' . $request->request_search . '%');
            });
        })
        ->latest()
        ->paginate(5, ['*'], 'requests_page')
        ->withQueryString()
        ->through(fn($req) => [
            'id' => $req->id,
            'receiver' => $req->receiver,
            'pick_up_region' => $req->pickUpRegion ? [
                'id' => $req->pickUpRegion->id,
                'name' => $req->pickUpRegion->name
            ] : ['name' => 'Not specified'],
            'drop_off_region' => $req->dropOffRegion ? [
                'id' => $req->dropOffRegion->id,
                'name' => $req->dropOffRegion->name
            ] : ['name' => 'Not specified'],
            'total_price' => $req->total_price,
            'status' => $req->status,
            'created_at' => $req->created_at->format('Y-m-d H:i'),
            'package_count' => $req->packages->count(),
        ]);

    // Approved & Delivered Transactions (show all completed/approved/delivered)
    $transactions = DeliveryRequest::with([
            'receiver', 
            'deliveryOrder', 
            'pickUpRegion:id,name', 
            'dropOffRegion:id,name',
            'payment' // Add this to load payment relationship
        ])
        ->where('sender_id', $customerId)
        ->whereIn('status', ['approved', 'delivered', 'completed'])
        ->when($request->filled('transaction_search'), function ($query) use ($request) {
            $query->whereHas('receiver', function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->transaction_search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->transaction_search . '%')
                  ->orWhere('company_name', 'like', '%' . $request->transaction_search . '%');
            });
        })
        ->latest()
        ->paginate(5, ['*'], 'transactions_page')
        ->withQueryString()
        ->through(fn($req) => [
            'id' => $req->deliveryOrder?->id ?? $req->id,
            'delivery_request' => [
                'id' => $req->id,
                'receiver' => $req->receiver,
                'pick_up_region' => $req->pickUpRegion ? [
                    'id' => $req->pickUpRegion->id,
                    'name' => $req->pickUpRegion->name
                ] : ['name' => 'Not specified'],
                'drop_off_region' => $req->dropOffRegion ? [
                    'id' => $req->dropOffRegion->id,
                    'name' => $req->dropOffRegion->name
                ] : ['name' => 'Not specified'],
                'payment_status' => $req->payment_status, // Add payment_status
                'payment' => $req->payment ? [ // Add payment data
                    'id' => $req->payment->id,
                    'status' => $req->payment->status
                ] : null
            ],
            // Use actual_arrival as the delivery date if available, else null
            'delivery_date' => $req->deliveryOrder?->actual_arrival,
            // Set status to 'completed' if deliveryOrder status is 'completed'
            'status' => $req->deliveryOrder?->status === 'completed'
                ? 'completed'
                : ($req->deliveryOrder?->status ?? $req->status ?? 'pending'),
            'total_amount' => $req->total_price,
            'payment_status' => $req->payment_status, // Add payment_status at root level too
            'payment' => $req->payment ? [ // Add payment at root level too
                'id' => $req->payment->id,
                'status' => $req->payment->status
            ] : null
        ]);

    return Inertia::render('Customer/DeliveryRequests/DeliveryDashboard', [
        'requests' => $requests,
        'transactions' => $transactions,
        'filters' => $request->only(['request_search', 'transaction_search']),
        'status' => session('status'),
        'success' => session('success'),
        'error' => session('error'),
    ]);
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
        'payment'
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
