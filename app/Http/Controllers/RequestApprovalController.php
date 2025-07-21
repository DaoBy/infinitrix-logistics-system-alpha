<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRequest;
use App\Models\PriceMatrix;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\NotificationService;

class RequestApprovalController extends Controller
{
    public function index()
    {
        // Gate::authorize('view-delivery-requests');
        
        $deliveries = DeliveryRequest::with([
                'sender', 
                'receiver', 
                'packages', 
                'pickUpRegion',
                'dropOffRegion',
                'approvedBy',
                'rejectedBy'
            ])
            ->approved()
            ->latest()
            ->get()
            ->map(function ($delivery) {
                return $this->formatDeliveryRequest($delivery, true); // <-- add true here
            });

        return Inertia::render('Admin/Deliveries/ApprovedRequest', [
            'deliveries' => $deliveries,
            'status' => session('status'),
        ]);
    }

    public function pending(Request $request)
    {
        // Gate::authorize('view-pending-requests');
        
        $query = DeliveryRequest::with(['sender', 'receiver', 'packages', 'pickUpRegion', 'dropOffRegion'])
            ->pending()
            ->latest();

        // Apply filters
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->whereHas('sender', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('company_name', 'like', "%{$request->search}%");
                })
                ->orWhereHas('receiver', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('company_name', 'like', "%{$request->search}%");
                })
                ->orWhere('id', 'like', "%{$request->search}%");
            });
        }

        if ($request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        // Apply date filters
        if ($request->date_range) {
            $query = $this->applyDateFilter($query, $request->date_range);
        }

        $requests = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Deliveries/PendingRequests', [
            'requests' => $requests->map(fn($request) => $this->formatDeliveryRequest($request)),
            'pagination' => $requests->only(['current_page', 'last_page', 'per_page', 'total']),
            'filters' => $request->only(['search', 'payment_method', 'date_range']),
            'status' => session('status'),
        ]);
    }

    public function rejected()
    {
        // Gate::authorize('view-rejected-requests');
        
        $requests = DeliveryRequest::with(['sender', 'receiver', 'packages', 'pickUpRegion', 'dropOffRegion', 'rejectedBy', 'approvedBy'])
            ->rejected()
            ->latest()
            ->get()
            ->map(fn($request) => $this->formatDeliveryRequest($request, true));

        return Inertia::render('Admin/Deliveries/RejectedRequests', [
            'requests' => $requests,
            'status' => session('status'),
        ]);
    }

    public function show(DeliveryRequest $delivery)
    {
        // Gate::authorize('view-delivery-request', $delivery);

        // Eager load all relevant relationships, including reference_number and payment_status
        $delivery->load([
            'sender', 
            'receiver', 
            'packages', 
            'pickUpRegion', 
            'dropOffRegion', 
            'approvedBy', 
            'rejectedBy', 
            'packages.statusHistory'
        ]);

        // Make sure reference_number and payment_status are included in the response
        $sender = $delivery->sender ? $delivery->sender->toArray() : null;
        $receiver = $delivery->receiver ? $delivery->receiver->toArray() : null;

        $packages = $delivery->packages->map(function ($package) {
            return [
                ...$package->toArray(),
                'status_history' => $package->statusHistory,
                'photo_path' => $package->photo_path,
                'photo_url' => $package->photo_path ? asset('storage/' . $package->photo_path) : null,
            ];
        });

        $deliveryData = $this->formatDeliveryRequest($delivery, true);
        $deliveryData['sender'] = $sender;
        $deliveryData['receiver'] = $receiver;
        $deliveryData['packages'] = $packages;
        // Add reference_number and payment_status explicitly
        $deliveryData['reference_number'] = $delivery->reference_number;
        $deliveryData['payment_status'] = $delivery->payment_status;

        return Inertia::render('Admin/Deliveries/Show', [
            'delivery' => $deliveryData,
            'status' => session('status'),
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function edit(DeliveryRequest $delivery)
    {
        // Gate::authorize('edit-delivery-request', $delivery);
        
        if ($delivery->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending delivery requests can be edited');
        }

        $priceMatrix = PriceMatrix::firstOrFail();
        $delivery->load(['sender', 'receiver', 'packages', 'pickUpRegion', 'dropOffRegion']);

        return Inertia::render('Admin/Deliveries/Edit', [
            'delivery' => [
                'id' => $delivery->id,
                'sender' => [
                    'customer_category' => $delivery->sender->customer_category,
                    'first_name' => $delivery->sender->first_name,
                    'middle_name' => $delivery->sender->middle_name,
                    'last_name' => $delivery->sender->last_name,
                    'company_name' => $delivery->sender->company_name,
                    'email' => $delivery->sender->email,
                    'mobile' => $delivery->sender->mobile,
                    'phone' => $delivery->sender->phone,
                    'building_number' => $delivery->sender->building_number,
                    'street' => $delivery->sender->street,
                    'barangay' => $delivery->sender->barangay,
                    'city' => $delivery->sender->city,
                    'province' => $delivery->sender->province,
                    'zip_code' => $delivery->sender->zip_code,
                    'notes' => $delivery->sender->notes,
                ],
                'receiver' => [
                    'customer_category' => $delivery->receiver->customer_category,
                    'first_name' => $delivery->receiver->first_name,
                    'middle_name' => $delivery->receiver->middle_name,
                    'last_name' => $delivery->receiver->last_name,
                    'company_name' => $delivery->receiver->company_name,
                    'email' => $delivery->receiver->email,
                    'mobile' => $delivery->receiver->mobile,
                    'phone' => $delivery->receiver->phone,
                    'building_number' => $delivery->receiver->building_number,
                    'street' => $delivery->receiver->street,
                    'barangay' => $delivery->receiver->barangay,
                    'city' => $delivery->receiver->city,
                    'province' => $delivery->receiver->province,
                    'zip_code' => $delivery->receiver->zip_code,
                    'notes' => $delivery->receiver->notes,
                ],
                'pick_up_region_id' => $delivery->pick_up_region_id,
                'drop_off_region_id' => $delivery->drop_off_region_id,
                'payment_method' => $delivery->payment_method,
                'total_price' => $delivery->total_price,
                'status' => $delivery->status,
                'packages' => $delivery->packages->map(function ($package) {
                    $data = [
                        'id' => $package->id,
                        'item_name' => $package->item_name,
                        'description' => $package->description,
                        'category' => $package->category,
                        'height' => $package->height,
                        'width' => $package->width,
                        'length' => $package->length,
                        'weight' => $package->weight,
                        'value' => $package->value,
                    ];
                    
                    if ($package->photo_path) {
                        $data['photo_url'] = asset('storage/' . $package->photo_path);
                        $data['photo_path'] = $package->photo_path;
                    }
                    
                    return $data;
                }),
            ],
            'regions' => Region::active()->get(),
            'categories' => ['piece', 'carton', 'sack', 'bundle', 'roll', 'B/R', 'C/S'],
            'status' => session('status'),
            'success' => session('success'),
            'error' => session('error'),
            'priceMatrix' => $priceMatrix,
        ]);
    }

    public function update(Request $request, DeliveryRequest $delivery)
    {
        // Gate::authorize('edit-delivery-request', $delivery);
        
        if ($delivery->status !== 'pending') {
            abort(403, 'Only pending delivery requests can be edited');
        }

        $validated = $request->validate([
            'receiver.first_name' => [
                'nullable',
                'required_without:receiver.company_name',
                'string',
                'max:255'
            ],
            'receiver.middle_name' => 'nullable|string|max:255',
            'receiver.last_name' => 'nullable|string|max:255',
            'receiver.company_name' => [
                'nullable',
                'required_without:receiver.first_name',
                'string',
                'max:255'
            ],
            'receiver.email' => [
                'required',
                'email',
                'max:255',
            ],
            'receiver.mobile' => 'nullable|string|max:20',
            'receiver.phone' => 'nullable|string|max:20',
            'receiver.building_number' => 'nullable|string|max:50',
            'receiver.street' => 'nullable|string|max:255',
            'receiver.barangay' => 'nullable|string|max:255',
            'receiver.city' => 'nullable|string|max:255',
            'receiver.province' => 'nullable|string|max:255',
            'receiver.zip_code' => 'nullable|string|max:10',
            'receiver.notes' => 'nullable|string',
            'pick_up_region_id' => 'required|exists:regions,id',
            'drop_off_region_id' => 'required|exists:regions,id',
            'packages' => 'required|array|min:1',
            'packages.*.item_name' => 'required|string|max:255',
            'packages.*.category' => 'required|string|max:255',
            'packages.*.height' => 'required|numeric|min:0.1',
            'packages.*.width' => 'required|numeric|min:0.1',
            'packages.*.length' => 'required|numeric|min:0.1',
            'packages.*.weight' => 'required|numeric|min:0.1',
            'packages.*.description' => 'nullable|string',
            'packages.*.value' => 'nullable|numeric|min:0',
            'payment_method' => [
                'required',
                'in:cash,online,bank',
            ],
        ]);

        DB::beginTransaction();
        try {
            // Update sender/receiver
            $delivery->sender()->update($validated['sender'] ?? []);
            $delivery->receiver()->update($validated['receiver']);

            // Recalculate price but don't change the original pricing
            $priceResponse = $this->calculatePrice(new Request([
                'packages' => $validated['packages'],
                'pick_up_region_id' => $validated['pick_up_region_id'],
                'drop_off_region_id' => $validated['drop_off_region_id'],
            ]));

            $priceData = $priceResponse->getData();

            // Update delivery request with new pricing
            $delivery->update([
                'pick_up_region_id' => $validated['pick_up_region_id'],
                'drop_off_region_id' => $validated['drop_off_region_id'],
                'payment_method' => $validated['payment_method'],
                'total_price' => $priceData->total_price,
                'base_fee' => $priceData->breakdown->base_fee,
                'volume_fee' => $priceData->breakdown->volume_fee,
                'weight_fee' => $priceData->breakdown->weight_fee,
                'package_fee' => $priceData->breakdown->package_fee,
                'price_breakdown' => $priceData->breakdown,
            ]);

            $this->updatePackages($delivery, $validated['packages'], $validated['pick_up_region_id']);

            DB::commit();

            return redirect()
                ->route('deliveries.pending')
                ->with('success', 'Delivery request updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Failed to update delivery request: ' . $e->getMessage())
                ->withInput();
        }
    }

    // Change parameter name to match route binding: $delivery
public function approve(DeliveryRequest $delivery)
{
    if ($delivery->status !== 'pending') {
        return back()->with('error', 'Only pending requests can be approved');
    }

    DB::beginTransaction();
    try {
        // Determine payment_status based on payment_method
        $isPrepaid = in_array($delivery->payment_method, ['cash', 'gcash', 'bank']);
        $paymentStatus = $isPrepaid ? 'pending' : 'unpaid';

        // Set payment_due_date for postpaid with terms
        $paymentDueDate = null;
        if (!$isPrepaid && $delivery->payment_terms) {
            switch ($delivery->payment_terms) {
                case 'net_7':
                    $paymentDueDate = now()->addDays(7);
                    break;
                case 'net_15':
                    $paymentDueDate = now()->addDays(15);
                    break;
                case 'net_30':
                    $paymentDueDate = now()->addDays(30);
                    break;
                case 'cnd':
                    $paymentDueDate = null;
                    break;
            }
        }

        // Update delivery request
        $delivery->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'payment_status' => $paymentStatus,
            'payment_due_date' => $paymentDueDate, // <-- set here
        ]);

        // Always generate reference number on approval
        $referenceNumber = $delivery->generateReferenceNumber();

        // Update all associated packages to 'preparing'
        $delivery->packages()->update(['status' => 'preparing']);

        // Create the delivery order if not exists
        if (!$delivery->deliveryOrder) {
            $delivery->deliveryOrder()->create([
                'status' => $isPrepaid ? 'pending_payment' : 'ready', // Set to 'ready' for postpaid
                'payment_type' => $delivery->payment_type,
                'payment_status' => $paymentStatus,
            ]);
        }

        // Send notification to customer
        $message = $isPrepaid
            ? "Request #{$delivery->reference_number} has been approved. Please proceed to payment at your nearest branch or via your selected method."
            : "Request #{$delivery->reference_number} has been approved. We'll assign a driver soon!";

        NotificationService::send(
            $delivery->sender->user,
            'Delivery Request Approved 🎉',
            $message,
            'approval'
        );

        DB::commit();

        return back()->with('success', 'Delivery request approved!');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Failed to approve request');
    }
}

    // Change parameter name to match route binding: $delivery
    public function reject(Request $request, DeliveryRequest $delivery)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($delivery->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be rejected');
        }

        DB::beginTransaction();
        try {
            // Update delivery request
            $delivery->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
            ]);

            // Update all packages
            $delivery->packages()->update(['status' => 'rejected']);

            // Send notification to customer with reason
            NotificationService::send(
                $delivery->sender->user,
                'Delivery Request Denied ❌',
                "Request #{$delivery->id} has been denied. Reason: {$request->rejection_reason}. Please check your details or contact support.",
                'denial'
            );

            DB::commit();

            return back()->with('success', 'Delivery request rejected!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reject request');
        }
    }

    // Helper Methods
    
    protected function formatDeliveryRequest(DeliveryRequest $delivery, bool $detailed = false): array
    {
        $data = [
            'id' => $delivery->id,
            'order_number' => 'DR-' . str_pad($delivery->id, 6, '0', STR_PAD_LEFT),
            'sender' => $delivery->sender->name ?? $delivery->sender->company_name,
            'receiver' => $delivery->receiver->name ?? $delivery->receiver->company_name,
            'pick_up_region' => $delivery->pickUpRegion->name ?? 'N/A',
            'drop_off_region' => $delivery->dropOffRegion->name ?? 'N/A',
            'status' => $delivery->status,
            'total_price' => (float) $delivery->total_price,
            'payment_method' => $delivery->payment_method,
            'payment_terms' => $delivery->payment_terms, // <-- Add this
            'payment_due_date' => $delivery->payment_due_date, // <-- Add this
            'created_at' => $delivery->created_at->format('Y-m-d H:i'),
            'package_count' => $delivery->packages->count(),
        ];

        if ($detailed) {
            $data += [
                'approved_at' => optional($delivery->approved_at)->format('Y-m-d H:i'),
                'approved_by' => $delivery->approvedBy?->name ?? null,
                'rejected_at' => optional($delivery->rejected_at)->format('Y-m-d H:i'),
                'rejected_by' => $delivery->rejectedBy?->name ?? null,
                'rejection_reason' => $delivery->rejection_reason,
            ];
        }

        return $data;
    }

    protected function applyDateFilter($query, string $dateRange)
    {
        switch ($dateRange) {
            case 'today':
                return $query->whereDate('created_at', today());
            case 'week':
                return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            case 'month':
                return $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
            case 'year':
                return $query->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()]);
            default:
                return $query;
        }
    }

    protected function validateDeliveryRequest(Request $request, DeliveryRequest $delivery): array
    {
        return $request->validate([
            // Validation rules same as before
            // ... [keep all your existing validation rules]
        ]);
    }

    protected function calculateTotalPrice(array $validated): float
    {
        $priceMatrix = PriceMatrix::firstOrFail();
        $total = $priceMatrix->base_fee;
        
        foreach ($validated['packages'] as $package) {
            $volume = ($package['height'] / 100) * ($package['width'] / 100) * ($package['length'] / 100);
            $total += ($volume * $priceMatrix->volume_rate) 
                    + ($package['weight'] * $priceMatrix->weight_rate)
                    + $priceMatrix->package_rate;
        }

        return round($total, 2);
    }

    protected function updatePackages(DeliveryRequest $delivery, array $packagesData, int $regionId): void
    {
        $packageIds = [];
        
        foreach ($packagesData as $packageData) {
            $packageDataToSave = [
                'item_name' => $packageData['item_name'],
                'description' => $packageData['description'] ?? null,
                'category' => $packageData['category'],
                'height' => $packageData['height'],
                'width' => $packageData['width'],
                'length' => $packageData['length'],
                'weight' => $packageData['weight'],
                'value' => $packageData['value'],
                'current_region_id' => $regionId,
            ];

            if (isset($packageData['id'])) {
                $package = $delivery->packages()->findOrFail($packageData['id']);
                $package->update($packageDataToSave);
                $packageIds[] = $package->id;
            } else {
                $package = $delivery->packages()->create(array_merge($packageDataToSave, [
                    'item_code' => 'PKG-' . strtoupper(Str::random(6)),
                    'status' => 'preparing'
                ]));
                $package->updateStatus('preparing', auth()->user(), 'Package created');
                $packageIds[] = $package->id;
            }
        }

        // Delete removed packages
        $this->deleteRemovedPackages($delivery, $packageIds);
    }

    protected function handlePackagePhoto(array $packageData, array &$packageDataToSave): void
    {
        if (isset($packageData['photo'])) {
            if (isset($packageData['existing_photo'])) {
                Storage::disk('public')->delete(str_replace(asset('storage/'), '', $packageData['existing_photo']));
            }
            $packageDataToSave['photo_path'] = $packageData['photo']->store('package-photos', 'public');
        } elseif (isset($packageData['existing_photo'])) {
            $packageDataToSave['photo_path'] = str_replace(asset('storage/'), '', $packageData['existing_photo']);
        }
    }

    protected function deleteRemovedPackages(DeliveryRequest $delivery, array $keepIds): void
    {
        $delivery->packages()
            ->whereNotIn('id', $keepIds)
            ->each(function($package) {
                if ($package->photo_path) {
                    Storage::disk('public')->delete($package->photo_path);
                }
                $package->delete();
            });
    }
}