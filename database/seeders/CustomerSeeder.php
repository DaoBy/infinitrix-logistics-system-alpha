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
        $faker = Faker::create('en_PH'); // Use Philippine-specific data
        // Predefined customers
        $predefinedCustomers = [
            [
                'name' => 'Engel Chiquillo',
                'email' => 'echi.021001@gmail.com',
                'category' => 'individual',
            ],
            [
                'name' => 'Chi Chi',
                'email' => 'babypatatas00@gmail.com',
                'category' => 'individual',
            ],
            [
                'name' => 'Berkeley Castor',
                'email' => 'berkeleycastor@gmail.com',
                'category' => 'individual',
            ],
            [
                'name' => 'Vien Kendrick Morfe',
                'email' => 'vienkendrickmorfe@gmail.com',
                'category' => 'individual',
            ],
            [
                'name' => 'Andrei Barlaan',
                'email' => 'andreibarlaan123@gmail.com',
                'category' => 'individual',
            ],
            [
                'name' => 'John Patrick Narido',
                'email' => 'trickypanda123@gmail.com',
                'category' => 'individual',
            ],
            [
                'name' => 'Jeremiah Alejo',
                'email' => 'j34935114@gmail.com',
                'category' => 'individual',
            ],
            [
                'name' => 'Elexia Elexis',
                'email' => 'elexiaelexis@gmail.com',
                'category' => 'individual',
            ],
            [
                'name' => 'Jeremiah Morales',
                'email' => 'moralesjeremiah832@gmail.com',
                'category' => 'individual',
            ],
        ];

        // Create predefined customers
        foreach ($predefinedCustomers as $customerData) {
            $user = User::create([
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'password' => Hash::make('password'),
                'role' => 'customer',
                'is_active' => true,
                'email_verified_at' => Carbon::now(), // Mark email as verified
            ]);

            // Common Philippine addresses
            $addresses = [
                [
                    'building_number' => '123',
                    'street' => 'Quezon Avenue',
                    'barangay' => 'Barangay Central',
                    'city' => 'Quezon City',
                    'province' => 'Metro Manila',
                    'zip_code' => '1100',
                ],
                [
                    'building_number' => '456',
                    'street' => 'EDSA',
                    'barangay' => 'Barangay Wack-Wack',
                    'city' => 'Mandaluyong City',
                    'province' => 'Metro Manila',
                    'zip_code' => '1550',
                ],
                [
                    'building_number' => '789',
                    'street' => 'Rizal Street',
                    'barangay' => 'Barangay Poblacion',
                    'city' => 'Makati City',
                    'province' => 'Metro Manila',
                    'zip_code' => '1200',
                ],
                [
                    'building_number' => '101',
                    'street' => 'Bonifacio Drive',
                    'barangay' => 'Barangay 659',
                    'city' => 'Manila',
                    'province' => 'Metro Manila',
                    'zip_code' => '1000',
                ],
            ];

            $address = $faker->randomElement($addresses);

            Customer::create([
                'user_id' => $user->id,
                'first_name' => explode(' ', $customerData['name'])[0],
                'last_name' => explode(' ', $customerData['name'])[count(explode(' ', $customerData['name'])) - 1],
                'middle_name' => count(explode(' ', $customerData['name'])) > 2 ? explode(' ', $customerData['name'])[1] : null,
                'company_name' => null,
                'email' => $customerData['email'],
                'mobile' => $faker->mobileNumber(),
                'phone' => null,
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
            ]);
        }
    }
}