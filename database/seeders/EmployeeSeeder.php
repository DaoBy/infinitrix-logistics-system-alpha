<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run()
    {
        $regionIds = \App\Models\Region::pluck('id')->toArray();

        if (empty($regionIds)) {
            $this->command->error('No regions found! Please seed regions first.');
            return;
        }

        // Specific employees data with complete details
        $specificEmployees = [
            [
                'name' => 'Blase Gabriel Mariano',
                'email' => 'marianoblasegabriel@gmail.com',
                'role' => 'admin',
                'mobile' => '09197319858',
                'employee_id' => 'EMP-ADM-0001',
                'address' => [
                    'building_number' => '123',
                    'street' => 'Magsaysay Avenue',
                    'barangay' => 'Barangay 12',
                    'city' => 'Naga City',
                    'province' => 'Camarines Sur',
                    'zip_code' => '4400',
                    'phone' => '0541234567',
                ],
                'hire_date' => '2023-01-15',
                'region_id' => 5, // Naga City region ID
            ],
            [
                'name' => 'Ymmyll Manuel',
                'email' => 'ymmylljan@gmail.com',
                'role' => 'driver',
                'mobile' => '09123456789',
                'employee_id' => 'EMP-DRI-0001',
                'address' => [
                    'building_number' => '456',
                    'street' => 'Penafrancia Avenue',
                    'barangay' => 'Barangay Dinaga',
                    'city' => 'Legazpi City',
                    'province' => 'Albay',
                    'zip_code' => '4500',
                    'phone' => '0521234567',
                ],
                'hire_date' => '2023-02-20',
                'region_id' => 8, // Legazpi City region ID
            ],
            [
                'name' => 'Rose Garcia',
                'email' => 'rosegarcia01769@gmail.com',
                'role' => 'staff',
                'mobile' => '09187654321',
                'employee_id' => 'EMP-STA-0001',
                'address' => [
                    'building_number' => '789',
                    'street' => 'Daet Street',
                    'barangay' => 'Barangay 1',
                    'city' => 'Daet',
                    'province' => 'Camarines Norte',
                    'zip_code' => '4600',
                    'phone' => '0547654321',
                ],
                'hire_date' => '2023-03-10',
                'region_id' => 3, // Daet region ID
            ],
            [
                'name' => 'Wensdee Garcia',
                'email' => 'garcia.wensdeea@gmail.com',
                'role' => 'staff',
                'mobile' => '09176543218',
                'employee_id' => 'EMP-STA-0002',
                'address' => [
                    'building_number' => '101',
                    'street' => 'Iriga Street',
                    'barangay' => 'Barangay 2',
                    'city' => 'Iriga City',
                    'province' => 'Camarines Sur',
                    'zip_code' => '4431',
                    'phone' => '0549876543',
                ],
                'hire_date' => '2023-04-05',
                'region_id' => 6, // Iriga region ID
            ],
            [
                'name' => 'Shaina Bilches',
                'email' => 'sbilches49@gmail.com',
                'role' => 'collector',
                'mobile' => '09165544332',
                'employee_id' => 'EMP-COL-0001',
                'address' => [
                    'building_number' => '202',
                    'street' => 'Tabaco Street',
                    'barangay' => 'Barangay 3',
                    'city' => 'Tabaco City',
                    'province' => 'Albay',
                    'zip_code' => '4511',
                    'phone' => '0527654321',
                ],
                'hire_date' => '2023-05-12',
                'region_id' => 7, // Tabaco City region ID
            ],
            [
                'name' => 'Marlyn Fenequito',
                'email' => 'fenequitomarlyn3@gmail.com',
                'role' => 'collector',
                'mobile' => '09178889991',
                'employee_id' => 'EMP-COL-0002',
                'address' => [
                    'building_number' => '303',
                    'street' => 'Sorsogon Street',
                    'barangay' => 'Barangay 4',
                    'city' => 'Sorsogon City',
                    'province' => 'Sorsogon',
                    'zip_code' => '4700',
                    'phone' => '0561234567',
                ],
                'hire_date' => '2023-06-18',
                'region_id' => 9, // Sorsogon region ID
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
                'employee_id' => $employee['employee_id'],
                'phone' => $employee['address']['phone'],
                'mobile' => $employee['mobile'],
                'building_number' => $employee['address']['building_number'],
                'street' => $employee['address']['street'],
                'barangay' => $employee['address']['barangay'],
                'city' => $employee['address']['city'],
                'province' => $employee['address']['province'],
                'zip_code' => $employee['address']['zip_code'],
                'hire_date' => $employee['hire_date'],
                'termination_date' => null,
                'archived_at' => null,
                'notes' => 'Specific employee account',
                'region_id' => $employee['region_id'],
            ];

            if (!$user->employeeProfile) {
                $profile = new EmployeeProfile($profileData);
                $user->employeeProfile()->save($profile);
            } else {
                $user->employeeProfile()->update($profileData);
            }
        }

        $this->command->info(count($specificEmployees) . ' employees seeded successfully!');
    }
}