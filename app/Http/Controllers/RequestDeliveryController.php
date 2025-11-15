<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DeliveryRequest;
use App\Models\Package;
use App\Models\PriceMatrix;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class RequestDeliveryController extends Controller
{
public function create()
{
    $user = auth()->user();
    $customer = $user->customer ?? null;

    // Check for incomplete profile
    if ($user->isCustomer() && (!$customer || !$customer->is_profile_complete)) {
        return redirect()->route('profile.complete')
            ->with('show_modal', true)
            ->with('warning', 'Please complete your profile to request a delivery.');
    }

    $regions = Region::where('is_active', true)->get()->map(function ($region) {
        return [
            'id' => $region->id,
            'name' => $region->name,
            'warehouse_address' => $region->warehouse_address,
            'latitude' => $region->latitude,
            'longitude' => $region->longitude,
            'is_active' => $region->is_active,
            'color_hex' => $region->color_hex
        ];
    });
    
    $priceMatrix = PriceMatrix::first();
    
    // Get package categories for the form
    $packageCategories = \App\Models\PackageCategory::active()
        ->ordered()
        ->get()
        ->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
                'description' => $category->description,
                'dimensions' => $category->dimensions,
                'image_url' => $category->image_url,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order
            ];
        });

    if ($customer) {
        $customer->completed_deliveries_count = \App\Models\DeliveryRequest::where('sender_id', $customer->id)
            ->where('status', 'completed')
            ->count();
    }

    return Inertia::render('Customer/RequestDelivery', [
        'regions' => $regions,
        'priceMatrix' => $priceMatrix,
        'authCustomer' => $customer,
        'packageCategories' => $packageCategories, // Add this
    ]);
}

// Add this method to your RequestDeliveryController
public function uploadDownpaymentReceipt(Request $request)
{
    $request->validate([
        'receipt' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
    ]);

    try {
        $path = $request->file('receipt')->store('downpayment-receipts', 'public');
        
        \Log::debug('📄 Downpayment receipt uploaded:', [
            'original_name' => $request->file('receipt')->getClientOriginalName(),
            'stored_path' => $path,
            'size' => $request->file('receipt')->getSize()
        ]);

        return response()->json([
            'success' => true,
            'file_path' => $path,
            'url' => Storage::url($path)
        ]);
    } catch (\Exception $e) {
        \Log::error('❌ Failed to upload downpayment receipt:', [
            'error' => $e->getMessage(),
            'file' => $request->file('receipt')->getClientOriginalName()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to upload receipt: ' . $e->getMessage()
        ], 500);
    }
}


   public function store(Request $request)
{
    // DEBUG: See what packages are being received
    \Log::debug('📦 PACKAGES RECEIVED FROM FRONTEND:', [
        'all_request_data' => $request->all(),
        'packages_count' => count($request->input('packages', [])),
        'packages_data' => $request->input('packages', []),
    ]);

    // Also log files
    \Log::debug('📁 FILES RECEIVED:', [
        'files_count' => count($request->allFiles()),
        'files_data' => array_keys($request->allFiles()),
    ]);

    // BULLETPROOF package reconstruction
    $packages = [];
    $packageKeys = [];

    // First, collect all package keys
    foreach ($request->all() as $key => $value) {
        if (preg_match('/^packages\[(\d+)\]/', $key, $matches)) {
            $packageKeys[$matches[1]] = true;
        }
    }

    // Then build the packages array properly
    foreach (array_keys($packageKeys) as $index) {
        $package = [];
        
        // Get all fields for this package
        foreach ($request->all() as $key => $value) {
            if (preg_match('/^packages\['.$index.'\]\[(.+)\]$/', $key, $matches)) {
                $field = $matches[1];
                $package[$field] = $value;
            }
        }
        
        // Handle files for this package
        foreach ($request->allFiles() as $key => $files) {
            if (preg_match('/^packages\['.$index.'\]\[photos\]\[(\d+)\]$/', $key, $matches)) {
                $photoIndex = $matches[1];
                if (!isset($package['photos'])) {
                    $package['photos'] = [];
                }
                $package['photos'][$photoIndex] = $files;
            }
        }
        
        if (!empty($package)) {
            $packages[] = $package;
        }
    }

    if (!empty($packages)) {
        $request->merge(['packages' => $packages]);
        
        // DEBUG: Log the reconstructed packages
        \Log::debug('🔄 RECONSTRUCTED PACKAGES:', [
            'count' => count($packages),
            'packages' => $packages
        ]);
    }

    // Cast region IDs to integer
    $request->merge([
        'pick_up_region_id' => (int) $request->input('pick_up_region_id'),
        'drop_off_region_id' => (int) $request->input('drop_off_region_id')
    ]);

    // Validate request - INCLUDING DOWNPAYMENT FIELDS
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
        'packages.*.photo_path' => 'nullable|array',
        'packages.*.photo_path.*' => 'nullable|string',
        'packages.*.photos' => 'nullable|array',
        'packages.*.photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        'payment_type' => ['required', Rule::in(['prepaid', 'postpaid'])],
        'payment_method' => ['required_if:payment_type,prepaid'],
        'payment_terms' => [
            'nullable',
            Rule::in(['net_7', 'net_15', 'net_30', 'cnd']),
        ],
        // DOWNPAYMENT VALIDATION
        'downpayment_method' => 'required|in:gcash,bank',
        'downpayment_reference' => 'required|string|max:255',
        'downpayment_receipt' => 'required|string',
    ]);

    // Debug what's in the validated data vs original request
    \Log::debug('🔍 COMPARING VALIDATED VS ORIGINAL DATA:', [
        'validated_package_0_photo_path' => $validated['packages'][0]['photo_path'] ?? 'NOT FOUND',
        'original_package_0_photo_path' => $request->input('packages.0.photo_path', 'NOT FOUND'),
        'validated_package_count' => count($validated['packages']),
        'original_package_count' => count($request->input('packages', [])),
    ]);

    $deliveryRequest = null;

    DB::transaction(function () use ($validated, $request, &$deliveryRequest) {
        // Find or create receiver user
        $receiverUser = User::firstOrCreate(
            ['email' => $validated['receiver']['email']],
            [
                'name' => trim(($validated['receiver']['first_name'] ?? '') . ' ' . ($validated['receiver']['last_name'] ?? '')),
                'role' => 'customer',
                'password' => bcrypt(Str::random(12)),
                'is_active' => true,
            ]
        );

        // Update or create receiver customer with all fields
        $receiverData = [
            'first_name' => $validated['receiver']['first_name'] ?? null,
            'middle_name' => $validated['receiver']['middle_name'] ?? null,
            'last_name' => $validated['receiver']['last_name'] ?? null,
            'company_name' => $validated['receiver']['company_name'] ?? null,
            'email' => $validated['receiver']['email'],
            'mobile' => $validated['receiver']['mobile'] ?? null,
            'phone' => $validated['receiver']['phone'] ?? null,
            'building_number' => $validated['receiver']['building_number'] ?? null,
            'street' => $validated['receiver']['street'] ?? null,
            'barangay' => $validated['receiver']['barangay'] ?? null,
            'city' => $validated['receiver']['city'] ?? null,
            'province' => $validated['receiver']['province'] ?? null,
            'zip_code' => $validated['receiver']['zip_code'] ?? null,
            'notes' => $validated['receiver']['notes'] ?? null,
        ];

        $receiver = Customer::updateOrCreate(
            ['user_id' => $receiverUser->id],
            $receiverData
        );

        $priceResponse = $this->calculatePrice(new Request([
            'packages' => $validated['packages'],
            'pick_up_region_id' => $validated['pick_up_region_id'],
            'drop_off_region_id' => $validated['drop_off_region_id'],
        ]));

        $priceData = $priceResponse->getData();

        // Determine payment method and type
        $paymentType = $validated['payment_type'];
        $paymentMethod = $paymentType === 'prepaid'
            ? $validated['payment_method']
            : 'postpaid';

        // Handle payment terms and due date
        $paymentTerms = $validated['payment_terms'] ?? null;
        $paymentDueDate = null;

        // DEDUCT PROCESSING FEE FROM TOTAL PRICE
        $processingFee = 200.00;
        $finalTotalPrice = $priceData->total_price - $processingFee;

        // Create delivery request with processing fee deducted from total
        $deliveryRequest = DeliveryRequest::create([
            'sender_id' => auth()->user()->customer->id,
            'receiver_id' => $receiver->id,
            'pick_up_region_id' => $validated['pick_up_region_id'],
            'drop_off_region_id' => $validated['drop_off_region_id'],
            'payment_type' => $paymentType,
            'payment_method' => $paymentMethod,
            'payment_terms' => $paymentTerms,
            'payment_due_date' => $paymentDueDate,
            'total_price' => $finalTotalPrice, // Use the deducted amount
            'processing_fee_paid' => true, // Mark as paid
            'processing_fee_amount' => $processingFee, // Store fee amount for reference
            'base_fee' => $priceData->breakdown->base_fee,
            'volume_fee' => $priceData->breakdown->volume_fee,
            'weight_fee' => $priceData->breakdown->weight_fee,
            'package_fee' => $priceData->breakdown->package_fee,
            'price_breakdown' => $priceData->breakdown,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        // Create downpayment record (no verification needed)
        $downpayment = \App\Models\Downpayment::create([
            'delivery_request_id' => $deliveryRequest->id,
            'amount' => $processingFee,
            'method' => $validated['downpayment_method'],
            'reference_number' => $validated['downpayment_reference'],
            'receipt_image' => $this->storeDownpaymentReceipt($validated['downpayment_receipt']),
            'paid_at' => now(),
            'status' => 'paid', // Auto-verified, no admin approval
            'submitted_by_type' => get_class(auth()->user()),
            'submitted_by_id' => auth()->id(),
        ]);

        // DEBUG: Log before creating packages
        \Log::debug('🎯 CREATING PACKAGES IN DATABASE:', [
            'delivery_request_id' => $deliveryRequest->id,
            'packages_to_create' => count($validated['packages'])
        ]);

        // 🎯 FIXED: Create packages with proper photo path handling
        foreach ($validated['packages'] as $index => $pkg) {
            $volume = ($pkg['height'] / 100) * ($pkg['width'] / 100) * ($pkg['length'] / 100);

            // 🎯 CRITICAL FIX: Get photo paths from the ORIGINAL request data, not validated data
            $photoPaths = [];
            
            // Get the original package data from the request (before validation)
            $originalPackageData = $request->input("packages.{$index}");
            
            if (isset($originalPackageData['photo_path']) && is_array($originalPackageData['photo_path'])) {
                $photoPaths = $originalPackageData['photo_path'];
                \Log::debug("✅ USING PHOTO PATHS FROM REQUEST", [
                    'package_index' => $index,
                    'item_name' => $pkg['item_name'],
                    'photo_count' => count($photoPaths),
                    'photo_paths' => $photoPaths
                ]);
            } else {
                \Log::warning("❌ NO PHOTO PATHS FOUND FOR PACKAGE", [
                    'package_index' => $index,
                    'item_name' => $pkg['item_name'],
                    'available_fields' => array_keys($originalPackageData ?? [])
                ]);
                
                // Fallback: Check if we have file uploads instead
                if ($request->hasFile("packages.{$index}.photos")) {
                    foreach ($request->file("packages.{$index}.photos") as $photo) {
                        $path = $photo->store('package-photos', 'public');
                        $photoPaths[] = $path;
                    }
                    \Log::debug("📸 UPLOADED NEW PHOTOS AS FALLBACK", [
                        'package_index' => $index,
                        'photo_count' => count($photoPaths)
                    ]);
                }
            }

            $package = $deliveryRequest->packages()->create([
                'item_code' => $this->generateItemCode(),
                'item_name' => $pkg['item_name'],
                'category' => $pkg['category'],
                'description' => $pkg['description'] ?? null,
                'value' => $pkg['value'] ?? 0,
                'height' => $pkg['height'],
                'width' => $pkg['width'],
                'length' => $pkg['length'],
                'weight' => $pkg['weight'],
                'volume' => $volume,
                'current_region_id' => $validated['pick_up_region_id'],
                'photo_path' => !empty($photoPaths) ? $photoPaths : null,
            ]);

            \Log::debug("✅ PACKAGE CREATED WITH PHOTOS", [
                'package_id' => $package->id,
                'item_code' => $package->item_code,
                'photo_path_stored' => $package->photo_path,
                'photo_count_stored' => is_array($package->photo_path) ? count($package->photo_path) : 0
            ]);
        }

        // Final debug
        $finalPackageCount = $deliveryRequest->packages()->count();
        \Log::info('🎉 DELIVERY REQUEST COMPLETED', [
            'id' => $deliveryRequest->id,
            'sender_id' => $deliveryRequest->sender_id,
            'receiver_id' => $deliveryRequest->receiver_id,
            'original_calculated_price' => $priceData->total_price,
            'final_total_price' => $deliveryRequest->total_price, // After deduction
            'processing_fee_deducted' => $processingFee,
            'package_count' => $finalPackageCount,
            'payment_type' => $deliveryRequest->payment_type,
            'payment_terms' => $deliveryRequest->payment_terms,
            'downpayment_id' => $downpayment->id
        ]);
    });

    // ✅ FIXED: Return JSON response to stay on the same page
    return response()->json([
        'success' => true,
        'message' => 'Delivery request created successfully!',
        'delivery_request_id' => $deliveryRequest->id,
        'should_not_redirect' => true
    ], 200, [
        'Content-Type' => 'application/json',
        'X-Inertia' => 'false' // This tells Inertia not to handle this response
    ]);
}

// Add this helper method to store downpayment receipt
private function storeDownpaymentReceipt($receiptData)
{
    // If it's a base64 image, decode and store it
    if (preg_match('/^data:image\/(\w+);base64,/', $receiptData, $type)) {
        $data = substr($receiptData, strpos($receiptData, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, gif
        
        if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
            throw new \Exception('Invalid image type');
        }
        
        $data = base64_decode($data);
        if ($data === false) {
            throw new \Exception('base64_decode failed');
        }
    } else {
        // It's already a file path
        return $receiptData;
    }
    
    $fileName = 'downpayment-receipt-' . time() . '.' . $type;
    $filePath = 'downpayment-receipts/' . $fileName;
    
    Storage::disk('public')->put($filePath, $data);
    
    return $filePath;
}


public function uploadPhotos(Request $request)
{
    $request->validate([
        'photos.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    $uploadedPaths = [];

    if ($request->hasFile('photos')) {
        foreach ($request->file('photos') as $photo) {
            $path = $photo->store('package-photos', 'public');
            $uploadedPaths[] = $path;
            
            \Log::debug("📸 Photo uploaded", [
                'original_name' => $photo->getClientOriginalName(),
                'stored_path' => $path,
                'size' => $photo->getSize()
            ]);
        }
    }

    \Log::info("✅ Photos uploaded successfully", [
        'count' => count($uploadedPaths),
        'paths' => $uploadedPaths
    ]);

    return response()->json([
        'success' => true,
        'photo_paths' => $uploadedPaths
    ]);
}


public function getPastReceivers()
{
    $customer = auth()->user()->customer;
    
    if (!$customer) {
        return response()->json([]);
    }

    $pastReceivers = DeliveryRequest::where('sender_id', $customer->id)
        ->with('receiver')
        ->whereHas('receiver')
        ->orderBy('created_at', 'desc')
        ->limit(50)
        ->get()
        ->map(function ($deliveryRequest) {
            $receiver = $deliveryRequest->receiver;
            return [
                'id' => $receiver->id,
                'delivery_request_id' => $deliveryRequest->id,
                'first_name' => $receiver->first_name,
                'middle_name' => $receiver->middle_name,
                'last_name' => $receiver->last_name,
                'company_name' => $receiver->company_name,
                'email' => $receiver->email,
                'mobile' => $receiver->mobile,
                'phone' => $receiver->phone,
                'building_number' => $receiver->building_number,
                'street' => $receiver->street,
                'barangay' => $receiver->barangay,
                'city' => $receiver->city,
                'province' => $receiver->province,
                'zip_code' => $receiver->zip_code,
                'customer_category' => $receiver->customer_category,
                'created_at' => $deliveryRequest->created_at->format('M d, Y'),
                'display_name' => $this->getDisplayName($receiver)
            ];
        })
        ->unique('email') 
        ->values();

    return response()->json($pastReceivers);
}

private function getDisplayName($receiver)
{
    if ($receiver->company_name) {
        return $receiver->company_name . ' (Company)';
    }
    
    $name = trim($receiver->first_name . ' ' . $receiver->last_name);
    if ($receiver->customer_category === 'company') {
        return $name . ' (Company)';
    }
    
    return $name . ' (Individual)';
}


    public function calculatePrice(Request $request)
{
    $request->validate([
        'packages' => 'required|array|min:1',
        'packages.*.height' => 'required|numeric|min:0.1',
        'packages.*.width' => 'required|numeric|min:0.1',
        'packages.*.length' => 'required|numeric|min:0.1',
        'packages.*.weight' => 'required|numeric|min:0.1',
        'pick_up_region_id' => 'required|exists:regions,id',
        'drop_off_region_id' => 'required|exists:regions,id',
    ]);

    $priceMatrix = PriceMatrix::firstOrFail();
    $totalPrice = $priceMatrix->base_fee;
    $packageCount = count($request->packages);

    $breakdown = [
        'base_fee' => (float) $priceMatrix->base_fee,
        'volume_fee' => 0,
        'weight_fee' => 0,
        'package_fee' => $packageCount * (float) $priceMatrix->package_rate,
        'metrics' => [
            'total_volume' => 0,
            'total_weight' => 0,
        ]
    ];

    foreach ($request->packages as $package) {
        // Convert cm to meters for volume calculation
        $heightMeters = $package['height'] / 100;
        $widthMeters = $package['width'] / 100;
        $lengthMeters = $package['length'] / 100;
        
        $volume = $heightMeters * $widthMeters * $lengthMeters;
        $breakdown['volume_fee'] += $volume * (float) $priceMatrix->volume_rate;
        $breakdown['weight_fee'] += $package['weight'] * (float) $priceMatrix->weight_rate;

        $breakdown['metrics']['total_volume'] += $volume;
        $breakdown['metrics']['total_weight'] += $package['weight'];
    }

    // Round all values to 2 decimal places
    $breakdown['volume_fee'] = round($breakdown['volume_fee'], 2);
    $breakdown['weight_fee'] = round($breakdown['weight_fee'], 2);
    $breakdown['package_fee'] = round($breakdown['package_fee'], 2);
    $breakdown['metrics']['total_volume'] = round($breakdown['metrics']['total_volume'], 3);
    $breakdown['metrics']['total_weight'] = round($breakdown['metrics']['total_weight'], 2);

    $totalPrice = $breakdown['base_fee'] + $breakdown['volume_fee'] + $breakdown['weight_fee'] + $breakdown['package_fee'];

    return response()->json([
        'total_price' => round($totalPrice, 2),
        'breakdown' => (object) $breakdown
    ]);
}

    private function generateItemCode()
    {
        do {
            $code = 'PKG-' . now()->format('mdHi') . '-' . strtoupper(Str::random(6));
        } while (\App\Models\Package::where('item_code', $code)->exists());

        return $code;
    }
}