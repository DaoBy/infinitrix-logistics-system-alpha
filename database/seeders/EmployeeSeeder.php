<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\EmployeeProfile;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        $roles = ['admin', 'staff', 'driver', 'collector'];

        // Let's make 40 employees in total, evenly distributed by role
        $countPerRole = 1;

        foreach ($roles as $role) {
            for ($i = 0; $i < $countPerRole; $i++) {
                $user = User::create([
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail,
                    'password' => Hash::make('password'), // default password for employees
                    'role' => $role,
                    'is_active' => $faker->boolean(90), // mostly active
                ]);

                $profile = new EmployeeProfile([
                    'phone' => $faker->optional()->phoneNumber,
                    'mobile' => $faker->phoneNumber,
                    'building_number' => $faker->buildingNumber,
                    'street' => $faker->streetName,
                    'barangay' => $faker->citySuffix, // placeholder for barangay
                    'city' => $faker->city,
                    'province' => $faker->state,
                    'zip_code' => $faker->postcode,
                    'hire_date' => $faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
'termination_date' => $faker->optional(0.1)->dateTimeBetween('now', '+1 year')?->format('Y-m-d'),
                    'archived_at' => null,
                    'notes' => $faker->optional()->sentence,
                    'region_id' => $faker->numberBetween(1, 3), // Adjust to your regions count!
                ]);

                $user->employeeProfile()->save($profile);
            }
        }

        // Add one easy-access user for each role
        $demoUsers = [
            'admin' => 'admin@gmail.com',
            'staff' => 'staff@gmail.com',
            'driver' => 'driver@gmail.com',
            'collector' => 'collector@gmail.com',
        ];

        foreach ($demoUsers as $role => $email) {
            $user = User::create([
                'name' => ucfirst($role).' Demo',
                'email' => $email,
                'password' => Hash::make('password123'),
                'role' => $role,
                'is_active' => true,
            ]);

            $profile = new EmployeeProfile([
                'phone' => $faker->optional()->phoneNumber,
                'mobile' => $faker->phoneNumber,
                'building_number' => $faker->buildingNumber,
                'street' => $faker->streetName,
                'barangay' => $faker->citySuffix,
                'city' => 'Naga City',
                'province' => 'Camarines Sur',
                'zip_code' => $faker->postcode,
                'hire_date' => now()->format('Y-m-d'),
                'termination_date' => null,
                'archived_at' => null,
                'notes' => 'Demo account for testing.',
                'region_id' => 1, // Default region (adjust if needed)
            ]);

            $user->employeeProfile()->save($profile);
        }

        $this->command->info('40 Employees + 4 demo users seeded successfully!');
    }
}
