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
        $faker = Faker::create('en_PH'); // Set to Philippines locale

        $regionIds = \App\Models\Region::pluck('id')->toArray();

           if (empty($regionIds)) {
            $this->command->error('No regions found! Please seed regions first.');
            return;
        }

        // Specific employees data
        $specificEmployees = [
            [
                'name' => 'Blase Gabriel Mariano',
                'email' => 'marianoblasegabriel@gmail.com',
                'role' => 'admin',
                'mobile' => '09197319858',
                'city' => 'Naga City',
                'province' => 'Camarines Sur'
            ],
            [
                'name' => 'Ymmyll Manuel',
                'email' => 'ymmylljan@gmail.com',
                'role' => 'driver',
                'mobile' => '09123456789',
                'city' => 'Legazpi City',
                'province' => 'Albay'
            ],
            [
                'name' => 'Rose Garcia',
                'email' => 'rosegarcia01769@gmail.com',
                'role' => 'staff',
                'mobile' => '09187654321',
                'city' => 'Daet',
                'province' => 'Camarines Norte'
            ],
            [
                'name' => 'Wensdee Garcia',
                'email' => 'garcia.wensdeea@gmail.com',
                'role' => 'staff',
                'mobile' => '09176543218',
                'city' => 'Iriga City',
                'province' => 'Camarines Sur'
            ],
            [
                'name' => 'Shaina Bilches',
                'email' => 'sbilches49@gmail.com',
                'role' => 'collector',
                'mobile' => '09165544332',
                'city' => 'Tabaco City',
                'province' => 'Albay'
            ],
            [
                'name' => 'Marlyn Fenequito',
                'email' => 'fenequitomarlyn3@gmail.com',
                'role' => 'collector',
                'mobile' => '09178889991',
                'city' => 'Sorsogon City',
                'province' => 'Sorsogon'
            ]
        ];

        foreach ($specificEmployees as $employee) {
            $user = User::updateOrCreate(
                ['email' => $employee['email']],
                [
                    'name' => $employee['name'],
                    'password' => Hash::make('password123'),
                    'role' => $employee['role'],
                    'is_active' => true,
                ]
            );

            $profileData = [
                'phone' => $faker->optional()->phoneNumber,
                'mobile' => $employee['mobile'],
                'building_number' => $faker->buildingNumber,
                'street' => $faker->streetName,
                'barangay' => $faker->barangay(),
                'city' => $employee['city'],
                'province' => $employee['province'],
                'zip_code' => $faker->postcode,
                'hire_date' => $faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
                'termination_date' => null,
                'archived_at' => null,
                'notes' => 'Specific employee account',
                'region_id' => $faker->randomElement($regionIds), // Use valid region ID
             'employee_id' => $employee['employee_id'] ?? 'EMP-' . strtoupper(substr($employee['role'], 0, 3)) . '-' . str_pad($faker->unique()->numberBetween(1, 999), 4, '0', STR_PAD_LEFT),
            ];

             if (!$user->employeeProfile) {
                $profile = new EmployeeProfile($profileData);
                $user->employeeProfile()->save($profile);
            } else {
                $user->employeeProfile()->update($profileData);
            }
        }

        $this->command->info(count($specificEmployees) . ' employees seeded successfully!');

        // Seed random employees if needed
        $roles = ['admin', 'staff', 'driver', 'collector'];
        $countPerRole = 0; 

        foreach ($roles as $role) {
            for ($i = 0; $i < $countPerRole; $i++) {
                $cityProvince = $this->getRandomPhilippineLocation();
                
                User::create([
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail,
                    'password' => Hash::make('password'),
                    'role' => $role,
                    'is_active' => $faker->boolean(90),
                ])->employeeProfile()->save(new EmployeeProfile([
                    'phone' => $faker->optional()->phoneNumber,
                    'mobile' => '09' . $faker->numerify('########'),
                    'building_number' => $faker->buildingNumber,
                    'street' => $faker->streetName,
                    'barangay' => $faker->barangay(),
                    'city' => $cityProvince['city'],
                    'province' => $cityProvince['province'],
                    'zip_code' => $faker->postcode,
                    'hire_date' => $faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
                    'termination_date' => $faker->optional(0.1)->dateTimeBetween('now', '+1 year')?->format('Y-m-d'),
                    'archived_at' => null,
                    'notes' => $faker->optional()->sentence,
                    'region_id' => $faker->numberBetween(1, 10),
                ]));
            }
        }
    }

    private function getRandomPhilippineLocation()
    {
        $locations = [
            ['city' => 'Naga City', 'province' => 'Camarines Sur'],
            ['city' => 'Legazpi City', 'province' => 'Albay'],
            ['city' => 'Sorsogon City', 'province' => 'Sorsogon'],
            ['city' => 'Daet', 'province' => 'Camarines Norte'],
            ['city' => 'Iriga City', 'province' => 'Camarines Sur'],
            ['city' => 'Tabaco City', 'province' => 'Albay'],
            ['city' => 'Labo', 'province' => 'Camarines Norte'],
            ['city' => 'Milaor', 'province' => 'Camarines Sur'],
            ['city' => 'Malabon', 'province' => 'Metro Manila'],
        ];

        return $locations[array_rand($locations)];
    }
}
