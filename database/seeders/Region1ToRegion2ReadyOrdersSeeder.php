<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\Region;
use App\Models\DeliveryRequest;
use App\Models\Package;
use App\Models\PriceMatrix;
use App\Models\Payment;
use App\Models\Waybill;
use App\Models\DeliveryOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Region1ToRegion2ReadyOrdersSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            // 1. Ensure we have a price matrix
            $priceMatrix = PriceMatrix::firstOrCreate([], [
                'base_fee' => 50.00,
                'volume_rate' => 10.00,
                'weight_rate' => 5.00,
                'package_rate' => 2.00,
            ]);

            // 2. Get regions 1 and 2 specifically
            $region1 = Region::find(1);
            $region2 = Region::find(2);
            
            if (!$region1 || !$region2) {
                $this->command->error('❌ Need both region 1 and region 2 to exist');
                return;
            }

            // 3. Create admin user if doesn't exist
            $admin = User::firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'System Admin',
                    'password' => Hash::make('password123'),
                    'role' => 'admin',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // 4. Create multiple customers with complete profiles
            $customers = [];
            for ($i = 1; $i <= 20; $i++) {
                $user = User::create([
                    'name' => "Customer User {$i}",
                    'email' => "customer{$i}@example.com",
                    'password' => Hash::make('password123'),
                    'role' => 'customer',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);

                $customer = Customer::create([
                    'user_id' => $user->id,
                    'first_name' => "Customer{$i}",
                    'last_name' => "Last{$i}",
                    'email' => "customer{$i}@example.com",
                    'mobile' => '09' . rand(100000000, 999999999),
                    'building_number' => rand(100, 999),
                    'street' => 'Sample Street',
                    'barangay' => 'Sample Barangay',
                    'city' => 'Sample City',
                    'province' => 'Sample Province',
                    'zip_code' => '1000',
                    'customer_category' => 'individual',
                    'is_profile_complete' => true,
                ]);

                $customers[] = $customer;
            }

            // 5. Create 20 delivery requests - ALL from region 1 to region 2 with READY status
            $prepaidCount = 0;
            $postpaidCount = 0;
            $totalRequests = 20;
            
            foreach ($customers as $index => $customer) {
                // Alternate between prepaid and postpaid for even distribution
                $paymentType = ($index % 2 === 0) ? 'prepaid' : 'postpaid';
                
                $this->createRegion1ToRegion2DeliveryRequest($customer, $region1, $region2, $priceMatrix, $admin, $paymentType);
                
                // Increment counters
                if ($paymentType === 'prepaid') {
                    $prepaidCount++;
                } else {
                    $postpaidCount++;
                }
            }

            $this->command->info('✅ Region 1 to Region 2 ready orders seeded successfully!');
            $this->command->info('👤 Created admin user: admin@example.com / password123');
            $this->command->info('📦 Created: 20 customers with delivery requests');
            $this->command->info('🚚 Delivery Orders: ALL from Region 1 to Region 2 with READY status');
            $this->command->info('💰 Prepaid deliveries: ' . $prepaidCount);
            $this->command->info('📄 Postpaid deliveries: ' . $postpaidCount);
            $this->command->info('📦 Packages: Each delivery has 2-3 packages');
        });
    }

    protected function createRegion1ToRegion2DeliveryRequest($customer, $region1, $region2, $priceMatrix, $admin, $paymentType)
    {
        // Create packages data - ALWAYS 2-3 packages per delivery
        $packages = [];
        $packageCount = rand(2, 3); // Always at least 2 packages
        
        for ($k = 0; $k < $packageCount; $k++) {
            $packages[] = [
                'item_name' => "Item " . Str::random(8),
                'category' => ['piece', 'carton', 'sack', 'bundle'][rand(0, 3)],
                'height' => rand(10, 100),
                'width' => rand(10, 100),
                'length' => rand(10, 100),
                'weight' => rand(1, 20),
                'description' => 'Sample package description',
                'value' => rand(100, 1000),
            ];
        }

        // Calculate price
        $priceData = $this->calculatePrice($packages, $priceMatrix);

        // Set payment method based on type
        $paymentMethod = $paymentType === 'prepaid' ? ['cash', 'gcash', 'bank'][rand(0, 2)] : 'postpaid';
        $paymentTerms = $paymentType === 'postpaid' ? ['net_7', 'net_15', 'net_30'][rand(0, 2)] : null;

        // Create or find receiver
        $receiverUser = User::firstOrCreate(
            ['email' => 'receiver@example.com'],
            [
                'name' => 'Receiver Customer',
                'role' => 'customer',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );

        $receiver = Customer::firstOrCreate(
            ['user_id' => $receiverUser->id],
            [
                'first_name' => 'Receiver',
                'last_name' => 'Customer',
                'email' => 'receiver@example.com',
                'mobile' => '09123456789',
                'building_number' => '123',
                'street' => 'Receiver Street',
                'barangay' => 'Receiver Barangay',
                'city' => 'Receiver City',
                'province' => 'Receiver Province',
                'zip_code' => '2000',
                'customer_category' => 'individual',
                'is_profile_complete' => true,
            ]
        );

        // Create delivery request - ALWAYS from region 1 to region 2
        $deliveryRequest = DeliveryRequest::create([
            'sender_id' => $customer->id,
            'receiver_id' => $receiver->id,
            'pick_up_region_id' => $region1->id, // ALWAYS region 1
            'drop_off_region_id' => $region2->id, // ALWAYS region 2
            'payment_type' => $paymentType,
            'payment_method' => $paymentMethod,
            'payment_terms' => $paymentTerms,
            'payment_due_date' => $paymentType === 'postpaid' ? now()->addDays(rand(7, 30)) : null,
            'total_price' => $priceData['total_price'],
            'base_fee' => $priceData['breakdown']['base_fee'],
            'volume_fee' => $priceData['breakdown']['volume_fee'],
            'weight_fee' => $priceData['breakdown']['weight_fee'],
            'package_fee' => $priceData['breakdown']['package_fee'],
            'price_breakdown' => $priceData['breakdown'],
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'created_by' => $customer->user_id,
            'payment_status' => $paymentType === 'prepaid' ? 'paid' : 'unpaid',
            'payment_verified' => $paymentType === 'prepaid',
        ]);

        // Generate reference number
        $deliveryRequest->update([
            'reference_number' => 'INF-' . now()->format('Y') . '-' . str_pad($deliveryRequest->id, 6, '0', STR_PAD_LEFT)
        ]);

        // Create packages - ALWAYS 2-3 packages
        foreach ($packages as $pkg) {
            $volume = ($pkg['height'] / 100) * ($pkg['width'] / 100) * ($pkg['length'] / 100);

            Package::create([
                'item_code' => 'PKG-' . now()->format('mdHi') . '-' . strtoupper(Str::random(6)),
                'item_name' => $pkg['item_name'],
                'category' => $pkg['category'],
                'description' => $pkg['description'],
                'value' => $pkg['value'],
                'height' => $pkg['height'],
                'width' => $pkg['width'],
                'length' => $pkg['length'],
                'weight' => $pkg['weight'],
                'volume' => $volume,
                'current_region_id' => $region1->id, // Start at region 1
                'delivery_request_id' => $deliveryRequest->id,
                'status' => 'preparing',
            ]);
        }

        // Create payment if prepaid
        if ($paymentType === 'prepaid') {
            Payment::create([
                'delivery_request_id' => $deliveryRequest->id,
                'type' => 'prepaid',
                'method' => $paymentMethod,
                'reference_number' => $paymentMethod === 'cash' ? null : 'REF-' . Str::random(10),
                'source' => 'branch_staff',
                'amount' => $deliveryRequest->total_price,
                'paid_at' => now(),
                'verified_by' => $admin->id,
                'verified_at' => now(),
                'status' => 'verified',
                'collected_by' => $admin->id,
                'collected_at' => now(),
            ]);
        }

        // Create delivery order with READY status
        $deliveryOrder = DeliveryOrder::create([
            'delivery_request_id' => $deliveryRequest->id,
            'status' => 'ready', // ALWAYS READY STATUS
            'payment_type' => $paymentType,
            'payment_status' => $paymentType === 'prepaid' ? 'paid' : 'unpaid',
        ]);

        // Create waybill
        $waybillNumber = 'WB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        
        Waybill::create([
            'delivery_request_id' => $deliveryRequest->id,
            'generated_by' => $admin->id,
            'waybill_number' => $waybillNumber,
            'notes' => "Region {$region1->id} to Region {$region2->id} - Payment: {$paymentType} - Status: READY",
            'file_path' => 'waybills/' . $waybillNumber . '.pdf',
        ]);

        // Update packages with sticker printed status
        $deliveryRequest->packages()->update([
            'sticker_printed_at' => now(),
            'sticker_printed_by' => $admin->id,
        ]);

        return $deliveryRequest;
    }

    protected function calculatePrice($packages, $priceMatrix)
    {
        $totalPrice = $priceMatrix->base_fee;
        $packageCount = count($packages);

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

        foreach ($packages as $package) {
            $heightMeters = $package['height'] / 100;
            $widthMeters = $package['width'] / 100;
            $lengthMeters = $package['length'] / 100;
            
            $volume = $heightMeters * $widthMeters * $lengthMeters;
            $breakdown['volume_fee'] += $volume * (float) $priceMatrix->volume_rate;
            $breakdown['weight_fee'] += $package['weight'] * (float) $priceMatrix->weight_rate;

            $breakdown['metrics']['total_volume'] += $volume;
            $breakdown['metrics']['total_weight'] += $package['weight'];
        }

        // Round all values
        $breakdown['volume_fee'] = round($breakdown['volume_fee'], 2);
        $breakdown['weight_fee'] = round($breakdown['weight_fee'], 2);
        $breakdown['package_fee'] = round($breakdown['package_fee'], 2);
        $breakdown['metrics']['total_volume'] = round($breakdown['metrics']['total_volume'], 3);
        $breakdown['metrics']['total_weight'] = round($breakdown['metrics']['total_weight'], 2);

        $totalPrice = $breakdown['base_fee'] + $breakdown['volume_fee'] + $breakdown['weight_fee'] + $breakdown['package_fee'];

        return [
            'total_price' => round($totalPrice, 2),
            'breakdown' => $breakdown
        ];
    }
}