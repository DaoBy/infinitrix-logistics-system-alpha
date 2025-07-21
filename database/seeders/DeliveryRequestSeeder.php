<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeliveryRequest;
use App\Models\DeliveryOrder;
use App\Models\Customer;
use App\Models\User;
use App\Models\Truck;
use App\Models\Package;
use App\Models\Region; 
use Illuminate\Support\Arr;
use Faker\Factory as Faker;

class DeliveryRequestSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        $customers = Customer::all();
        $users = User::all();
        $trucks = Truck::all();
        $regions = Region::all();

        if ($customers->count() < 2 || $users->count() < 1) {
            $this->command->error('Please seed Customers, Users, and Trucks first!');
            return;
        }

        for ($i = 0; $i < 20; $i++) {
            $sender = $customers->random();
            do {
                $receiver = $customers->random();
            } while ($receiver->id === $sender->id);

            $pickUpBranch = $faker->city;
            $dropOffBranch = $faker->city;
            $paymentMethod = Arr::random(['cash', 'card']);
            $status = Arr::random(['pending', 'approved', 'rejected', 'cancelled']);

            $createdBy = $users->random()->id;
            $approvedBy = $status === 'approved' ? $users->random()->id : null;
            $approvedAt = $status === 'approved' ? $faker->dateTimeBetween('-1 month', 'now') : null;
            $rejectedBy = $status === 'rejected' ? $users->random()->id : null;
            $rejectedAt = $status === 'rejected' ? $faker->dateTimeBetween('-1 month', 'now') : null;
            $rejectionReason = $status === 'rejected' ? $faker->sentence() : null;

            $deliveryRequest = DeliveryRequest::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'pick_up_branch' => $pickUpBranch,
                'drop_off_branch' => $dropOffBranch,
                'payment_method' => $paymentMethod,
                'total_price' => $faker->randomFloat(2, 100, 5000),
                'status' => $status,
                'rejection_reason' => $rejectionReason,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
                'rejected_by' => $rejectedBy,
                'rejected_at' => $rejectedAt,
                'created_by' => $createdBy,
            ]);

            // Create linked delivery order only if approved
            if ($status === 'approved') {
                $driver = $users->where('role', 'driver')->random() ?? $users->random();
                $truck = $trucks->random();

                DeliveryOrder::create([
                    'delivery_request_id' => $deliveryRequest->id,
                    'driver_id' => $driver->id,
                    'truck_id' => $truck->id,
                    'assigned_by' => $users->random()->id,
                    'dispatched_by' => $users->random()->id,
                    'status' => Arr::random([
                        'pending', 'ready', 'assigned', 'dispatched', 
                        'in_transit', 'delivered', 'completed'
                    ]),
                    'dispatched_at' => $faker->dateTimeBetween('-10 days', 'now'),
                    'estimated_departure' => $faker->dateTimeBetween('now', '+2 days'),
                    'estimated_arrival' => $faker->dateTimeBetween('+2 days', '+5 days'),
                    'actual_departure' => $faker->dateTimeBetween('-5 days', 'now'),
                    'actual_arrival' => null,
                    'notes' => $faker->optional()->sentence(),
                ]);
            }

            // **Create 1-4 packages PER delivery request (INSIDE the loop!)**
            for ($p = 0; $p < rand(1, 4); $p++) {
                $height = $faker->numberBetween(10, 100);
                $width = $faker->numberBetween(10, 100);
                $length = $faker->numberBetween(10, 100);
                $volume = ($height / 100) * ($width / 100) * ($length / 100);

                $categories = ['piece', 'carton', 'sack', 'bundle', 'roll', 'B/R', 'C/S'];

                Package::create([
                    'item_code' => strtoupper($faker->bothify('PKG-###??')),
                    'item_name' => $faker->word(),
                    'category' => Arr::random($categories),
                    'height' => $height,
                    'width' => $width,
                    'length' => $length,
                    'volume' => $volume,
                    'weight' => $faker->randomFloat(2, 0.5, 50),
                    'quantity' => $faker->numberBetween(1, 10),
                    'value' => $faker->randomFloat(2, 100, 5000),
                    'description' => $faker->sentence(),
                    'photo_path' => null,
                    'delivery_request_id' => $deliveryRequest->id,
                    'current_region_id' => $regions->random()->id ?? null,
                    'status' => Arr::random(array_keys(Package::getStatuses())),
                ]);
            }
        }

        $this->command->info('DeliveryRequests, DeliveryOrders, and Packages seeded successfully!');
    }
}
