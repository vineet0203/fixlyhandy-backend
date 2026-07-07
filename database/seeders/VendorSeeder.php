<?php

namespace Database\Seeders;

use App\Models\Vendor;
use App\Models\User;
use App\Models\Role;
use App\Models\Employee;
use App\Models\Customer;
use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VendorSeeder extends Seeder
{
    public function run()
    {
        // Get vendor owner role
        $vendorRole = Role::where('slug', 'vendor_owner')->first();
        if (!$vendorRole) {
            $vendorRole = Role::create([
                'name' => 'Vendor Owner',
                'slug' => 'vendor_owner',
                'description' => 'Vendor Owner user'
            ]);
        }

        $vendorsData = [
            [
                'business_name' => 'Hexment IT',
                'full_name' => 'Bipul Dubey',
                'email' => 'bipuldubeyofficial@gmail.com',
                'mobile_number' => '6391345389',
                'service_category' => 'Electrical',
                'status' => 'active',
            ],
            [
                'business_name' => 'Reliance Industries Private limited',
                'full_name' => 'Vineet Anand',
                'email' => 'vineet.anand03@gmail.com',
                'mobile_number' => '09971358777',
                'service_category' => 'Electrical',
                'status' => 'active',
            ],
            [
                'business_name' => 'atkind',
                'full_name' => 'Ajinkya G Mhetre',
                'email' => 'theayushant@gmail.com',
                'mobile_number' => '9665248961',
                'service_category' => 'Home Repair',
                'status' => 'active',
            ],
        ];

        foreach ($vendorsData as $data) {
            // Create user
            $user = User::create([
                'first_name' => explode(' ', $data['full_name'])[0],
                'last_name' => explode(' ', $data['full_name'])[1] ?? '',
                'email' => $data['email'],
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ]);

            $user->roles()->attach($vendorRole->id, [
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            // Create vendor
            $vendor = Vendor::create([
                'user_id' => $user->id,
                'business_name' => $data['business_name'],
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'mobile_number' => $data['mobile_number'],
                'service_category' => $data['service_category'],
                'status' => $data['status'],
                'is_accepting_bookings' => true,
            ]);

            // Link vendor_id to user
            $user->update(['vendor_id' => $vendor->id]);

            // Create some employees
            Employee::create([
                'vendor_id' => $vendor->id,
                'employee_id' => 'EMP' . rand(1000, 9999),
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe.' . $vendor->id . '@fixlyhandy.com',
                'phone' => '1234567890',
                'mobile_number' => '1234567890',
                'designation' => 'Technician',
                'department' => 'Operations',
                'is_active' => true,
            ]);

            // Create some customer (via Client)
            $customer = Customer::create([
                'name' => 'Jane Smith',
                'email' => 'jane.smith.' . $vendor->id . '@gmail.com',
                'phone' => '9876543210',
                'status' => 'active',
            ]);

            Client::create([
                'vendor_id' => $vendor->id,
                'first_name' => 'Jane Smith',
                'email' => $customer->email,
                'mobile_number' => $customer->phone,
                'status' => 'active',
                'client_type' => 'residential',
            ]);
        }
    }
}
