<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use Illuminate\Support\Carbon;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('en_PH'); 

        // Predefined customers
        $predefinedCustomers = [
            [
                'name' => 'Engel Chiquillo',
                'email' => 'echi.021001@gmail.com',
                'category' => 'individual',
                'mobile' => '09171234567',
            ],
            [
                'name' => 'Chi Chi',
                'email' => 'babypatatas00@gmail.com',
                'category' => 'individual',
                'mobile' => '09172345678',
            ],
            [
                'name' => 'Berkeley Castor',
                'email' => 'berkeleycastor@gmail.com',
                'category' => 'individual',
                'mobile' => '09173456789',
            ],
            [
                'name' => 'Vien Kendrick Morfe',
                'email' => 'vienkendrickmorfe@gmail.com',
                'category' => 'individual',
                'mobile' => '09174567890',
            ],
            [
                'name' => 'Andrei Barlaan',
                'email' => 'andreibarlaan123@gmail.com',
                'category' => 'individual',
                'mobile' => '09175678901',
            ],
            [
                'name' => 'John Patrick Narido',
                'email' => 'trickypanda123@gmail.com',
                'category' => 'individual',
                'mobile' => '09176789012',
            ],
            [
                'name' => 'Jeremiah Alejo',
                'email' => 'j34935114@gmail.com',
                'category' => 'individual',
                'mobile' => '09177890123',
            ],
            [
                'name' => 'Elexia Elexis',
                'email' => 'elexiaelexis@gmail.com',
                'category' => 'individual',
                'mobile' => '09178901234',
            ],
            [
                'name' => 'Jeremiah Morales',
                'email' => 'moralesjeremiah832@gmail.com',
                'category' => 'individual',
                'mobile' => '09179012345',
            ],
        ];

        // Create predefined customers
        foreach ($predefinedCustomers as $customerData) {
            $user = User::updateOrCreate(
                ['email' => $customerData['email']],
                [
                    'name' => $customerData['name'],
                    'email' => $customerData['email'],
                    'password' => Hash::make('password123'),
                    'role' => 'customer',
                    'is_active' => true,
                    'email_verified_at' => $faker->dateTimeBetween('2025-06-03', '2025-06-16'),
                ]
            );

            // Philippine addresses with accurate locations
            $addresses = [
                [
                    'building_number' => $faker->buildingNumber,
                    'street' => 'Quezon Avenue',
                    'barangay' => 'Barangay Central',
                    'city' => 'Quezon City',
                    'province' => 'Metro Manila',
                    'zip_code' => '1100',
                    'phone' => '02' . $faker->numerify('#######'),
                ],
                [
                    'building_number' => $faker->buildingNumber,
                    'street' => 'EDSA',
                    'barangay' => 'Barangay Wack-Wack',
                    'city' => 'Mandaluyong City',
                    'province' => 'Metro Manila',
                    'zip_code' => '1550',
                    'phone' => '02' . $faker->numerify('#######'),
                ],
                [
                    'building_number' => $faker->buildingNumber,
                    'street' => 'Rizal Street',
                    'barangay' => 'Barangay Poblacion',
                    'city' => 'Makati City',
                    'province' => 'Metro Manila',
                    'zip_code' => '1200',
                    'phone' => '02' . $faker->numerify('#######'),
                ],
                [
                    'building_number' => $faker->buildingNumber,
                    'street' => 'Bonifacio Drive',
                    'barangay' => 'Barangay 659',
                    'city' => 'Manila',
                    'province' => 'Metro Manila',
                    'zip_code' => '1000',
                    'phone' => '02' . $faker->numerify('#######'),
                ],
                [
                    'building_number' => $faker->buildingNumber,
                    'street' => 'Magsaysay Avenue',
                    'barangay' => 'Barangay 12',
                    'city' => 'Naga City',
                    'province' => 'Camarines Sur',
                    'zip_code' => '4400',
                    'phone' => '054' . $faker->numerify('#######'),
                ],
                [
                    'building_number' => $faker->buildingNumber,
                    'street' => 'Penafrancia Avenue',
                    'barangay' => 'Barangay Dinaga',
                    'city' => 'Legazpi City',
                    'province' => 'Albay',
                    'zip_code' => '4500',
                    'phone' => '052' . $faker->numerify('#######'),
                ],
            ];

            $address = $faker->randomElement($addresses);

            $customerData = [
                'user_id' => $user->id,
                'first_name' => explode(' ', $customerData['name'])[0],
                'last_name' => explode(' ', $customerData['name'])[count(explode(' ', $customerData['name'])) - 1],
                'middle_name' => count(explode(' ', $customerData['name'])) > 2 ? explode(' ', $customerData['name'])[1] : null,
                'company_name' => null,
                'email' => $customerData['email'],
                'mobile' => $customerData['mobile'],
                'phone' => $address['phone'],
                'building_number' => $address['building_number'],
                'street' => $address['street'],
                'barangay' => $address['barangay'],
                'city' => $address['city'],
                'province' => $address['province'],
                'zip_code' => $address['zip_code'],
                'customer_category' => $customerData['category'],
                'frequency_type' => $faker->randomElement(['regular', 'occasional']),
                'notes' => $faker->optional()->sentence,
                'archived_at' => null,
            ];

            if (!$user->customer) {
                Customer::create($customerData);
            } else {
                $user->customer()->update($customerData);
            }
        }

        $this->command->info(count($predefinedCustomers) . ' customers seeded successfully!');
    }
}