<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Seed the application's customers.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Acme Corporation',
                'email' => 'purchasing@acme.example',
                'phone' => '+1-555-0101',
            ],
            [
                'name' => 'Northwind Traders',
                'email' => 'orders@northwind.example',
                'phone' => '+1-555-0102',
            ],
            [
                'name' => 'Globex Industries',
                'email' => 'procurement@globex.example',
                'phone' => '+1-555-0103',
            ],
            [
                'name' => 'Soylent Manufacturing',
                'email' => 'sales@soylent.example',
                'phone' => '+1-555-0104',
            ],
            [
                'name' => 'Initech Solutions',
                'email' => 'accounts@initech.example',
                'phone' => '+1-555-0105',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::query()->updateOrCreate(
                ['email' => $customer['email']],
                $customer,
            );
        }
    }
}
