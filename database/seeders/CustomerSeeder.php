<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        // Predefined customers
        $predefinedCustomers = [
            [
                'name' => 'Engel Chiquillo',
                'email' => 'echi.021001@gmail.com',
                'category' => 'individual',
                'mobile' => '09171234567',
                'address' => [
                    'building_number' => '123',
                    'street' => 'Quezon Avenue',
                    'barangay' => 'Barangay Central',
                    'city' => 'Quezon City',
                    'province' => 'Metro Manila',
                    'zip_code' => '1100',
                    'phone' => '0287654321',
                ]
            ],
            [
                'name' => 'Chi Chi',
                'email' => 'babypatatas00@gmail.com',
                'category' => 'individual',
                'mobile' => '09172345678',
                'address' => [
                    'building_number' => '456',
                    'street' => 'EDSA',
                    'barangay' => 'Barangay Wack-Wack',
                    'city' => 'Mandaluyong City',
                    'province' => 'Metro Manila',
                    'zip_code' => '1550',
                    'phone' => '0287654322',
                ]
            ],
            [
                'name' => 'Berkeley Castor',
                'email' => 'berkeleycastor@gmail.com',
                'category' => 'individual',
                'mobile' => '09173456789',
                'address' => [
                    'building_number' => '789',
                    'street' => 'Rizal Street',
                    'barangay' => 'Barangay Poblacion',
                    'city' => 'Makati City',
                    'province' => 'Metro Manila',
                    'zip_code' => '1200',
                    'phone' => '0287654323',
                ]
            ],
            [
                'name' => 'Vien Kendrick Morfe',
                'email' => 'vienkendrickmorfe@gmail.com',
                'category' => 'individual',
                'mobile' => '09174567890',
                'address' => [
                    'building_number' => '101',
                    'street' => 'Bonifacio Drive',
                    'barangay' => 'Barangay 659',
                    'city' => 'Manila',
                    'province' => 'Metro Manila',
                    'zip_code' => '1000',
                    'phone' => '0287654324',
                ]
            ],
            [
                'name' => 'Andrei Barlaan',
                'email' => 'andreibarlaan123@gmail.com',
                'category' => 'individual',
                'mobile' => '09175678901',
                'address' => [
                    'building_number' => '202',
                    'street' => 'Magsaysay Avenue',
                    'barangay' => 'Barangay 12',
                    'city' => 'Naga City',
                    'province' => 'Camarines Sur',
                    'zip_code' => '4400',
                    'phone' => '0541234567',
                ]
            ],
            [
                'name' => 'John Patrick Narido',
                'email' => 'trickypanda123@gmail.com',
                'category' => 'individual',
                'mobile' => '09176789012',
                'address' => [
                    'building_number' => '303',
                    'street' => 'Penafrancia Avenue',
                    'barangay' => 'Barangay Dinaga',
                    'city' => 'Legazpi City',
                    'province' => 'Albay',
                    'zip_code' => '4500',
                    'phone' => '0521234567',
                ]
            ],
            [
                'name' => 'Jeremiah Alejo',
                'email' => 'j34935114@gmail.com',
                'category' => 'individual',
                'mobile' => '09177890123',
                'address' => [
                    'building_number' => '404',
                    'street' => 'Quezon Avenue',
                    'barangay' => 'Barangay Central',
                    'city' => 'Quezon City',
                    'province' => 'Metro Manila',
                    'zip_code' => '1100',
                    'phone' => '0287654325',
                ]
            ],
            [
                'name' => 'Elexia Elexis',
                'email' => 'elexiaelexis@gmail.com',
                'category' => 'individual',
                'mobile' => '09178901234',
                'address' => [
                    'building_number' => '505',
                    'street' => 'EDSA',
                    'barangay' => 'Barangay Wack-Wack',
                    'city' => 'Mandaluyong City',
                    'province' => 'Metro Manila',
                    'zip_code' => '1550',
                    'phone' => '0287654326',
                ]
            ],
            [
                'name' => 'Jeremiah Morales',
                'email' => 'moralesjeremiah832@gmail.com',
                'category' => 'individual',
                'mobile' => '09179012345',
                'address' => [
                    'building_number' => '606',
                    'street' => 'Rizal Street',
                    'barangay' => 'Barangay Poblacion',
                    'city' => 'Makati City',
                    'province' => 'Metro Manila',
                    'zip_code' => '1200',
                    'phone' => '0287654327',
                ]
            ],
        ];

        foreach ($predefinedCustomers as $customerData) {
            $user = User::updateOrCreate(
                ['email' => $customerData['email']],
                [
                    'name' => $customerData['name'],
                    'email' => $customerData['email'],
                    'password' => Hash::make('password123'),
                    'role' => 'customer',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $nameParts = explode(' ', $customerData['name']);
            $firstName = $nameParts[0];
            $lastName = end($nameParts);
            $middleName = count($nameParts) > 2 ? $nameParts[1] : null;

            $customerProfile = [
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'middle_name' => $middleName,
                'company_name' => null,
                'email' => $customerData['email'],
                'mobile' => $customerData['mobile'],
                'phone' => $customerData['address']['phone'],
                'building_number' => $customerData['address']['building_number'],
                'street' => $customerData['address']['street'],
                'barangay' => $customerData['address']['barangay'],
                'city' => $customerData['address']['city'],
                'province' => $customerData['address']['province'],
                'zip_code' => $customerData['address']['zip_code'],
                'customer_category' => $customerData['category'],
                'frequency_type' => 'regular',
                'notes' => 'Predefined customer account',
                'archived_at' => null,
            ];

            if (!$user->customer) {
                Customer::create($customerProfile);
            } else {
                $user->customer()->update($customerProfile);
            }
        }

        $this->command->info(count($predefinedCustomers) . ' customers seeded successfully!');
    }
}