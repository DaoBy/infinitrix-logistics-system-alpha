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

        $regions = Region::where('is_active', true)->get();
        $priceMatrix = PriceMatrix::first();
        $customer = auth()->user()->customer ?? null;

        // Only set completed_deliveries_count for frontend eligibility check
        if ($customer) {
            $customer->completed_deliveries_count = \App\Models\DeliveryRequest::where('sender_id', $customer->id)
                ->where('status', 'completed')
                ->count();
        }

        return Inertia::render('Customer/RequestDelivery', [
            'regions' => $regions,
            'priceMatrix' => $priceMatrix,
            'authCustomer' => $customer,
        ]);
    }

    public function store(Request $request)
    {
        // Debugging: log request data and stack trace
        try {
            \Log::debug('RequestDeliveryController@store called', [
                'request_data' => $request->all(),
                'auth_user_id' => auth()->id(),
                'auth_customer' => auth()->user()->customer ?? null,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
            ]);
            // Force log flush (for Monolog)
            if (method_exists(\Log::getLogger(), 'flush')) {
                \Log::getLogger()->flush();
            }
        } catch (\Exception $e) {
            error_log('Laravel logging failed: ' . $e->getMessage());
        }

        \Log::info('Delivery request received', $request->all());

        // Reconstruct packages array from FormData if necessary
        $packages = [];
        foreach ($request->all() as $key => $value) {
            if (preg_match('/^packages\[(\d+)\]\[(.+)\]$/', $key, $matches)) {
                $idx = $matches[1];
                $field = $matches[2];
                $packages[$idx][$field] = $value;
            }
        }
        if (!empty($packages)) {
            $request->merge(['packages' => array_values($packages)]);
        }

        // Cast region IDs to integer
        $request->merge([
            'pick_up_region_id' => (int) $request->input('pick_up_region_id'),
            'drop_off_region_id' => (int) $request->input('drop_off_region_id')
        ]);

        // Validate request
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
            'payment_type' => ['required', Rule::in(['prepaid', 'postpaid'])],
            'payment_method' => ['required_if:payment_type,prepaid'],
            'payment_terms' => [
                'nullable',
                Rule::in(['net_7', 'net_15', 'net_30', 'cnd']),
            ],
        ]);

        DB::transaction(function () use ($validated) {
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
            $paymentDueDate = null; // Always null on creation

            // Create delivery request with locked-in pricing
            $deliveryRequest = DeliveryRequest::create([
                'sender_id' => auth()->user()->customer->id,
                'receiver_id' => $receiver->id,
                'pick_up_region_id' => $validated['pick_up_region_id'],
                'drop_off_region_id' => $validated['drop_off_region_id'],
                'payment_type' => $paymentType,
                'payment_method' => $paymentMethod,
                'payment_terms' => $paymentTerms,
                'payment_due_date' => $paymentDueDate,
                'total_price' => $priceData->total_price,
                'base_fee' => $priceData->breakdown->base_fee,
                'volume_fee' => $priceData->breakdown->volume_fee,
                'weight_fee' => $priceData->breakdown->weight_fee,
                'package_fee' => $priceData->breakdown->package_fee,
                'price_breakdown' => $priceData->breakdown,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            // Create packages
            foreach ($validated['packages'] as $pkg) {
                $volume = ($pkg['height'] / 100) * ($pkg['width'] / 100) * ($pkg['length'] / 100);

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
                ]);
                
                $package->updateStatus('preparing', auth()->user(), 'Package created');
            }

            \Log::info('Delivery request created', [
                'id' => $deliveryRequest->id,
                'sender_id' => $deliveryRequest->sender_id,
                'receiver_id' => $deliveryRequest->receiver_id,
                'total_price' => $deliveryRequest->total_price,
                'payment_type' => $deliveryRequest->payment_type,
                'payment_terms' => $deliveryRequest->payment_terms
            ]);
        });

        return redirect()->back()->with('deliveryRequest', [
            'id' => $deliveryRequest->id ?? null
        ]);
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