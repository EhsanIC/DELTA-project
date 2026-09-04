<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the application's products.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Product 100',
                'base_price' => 1250.00,
                'unit_cost' => 760.00,
                'physical_inventory' => 34,
                'reserved_inventory' => 0,
                'safety_stock' => 10,
                'install_minutes_per_unit' => 25,
            ],
            [
                'name' => 'Product 200',
                'base_price' => 2100.00,
                'unit_cost' => 1390.00,
                'physical_inventory' => 27,
                'reserved_inventory' => 0,
                'safety_stock' => 8,
                'install_minutes_per_unit' => 50,
            ],
            [
                'name' => 'Product 300',
                'base_price' => 3700.00,
                'unit_cost' => 2680.00,
                'physical_inventory' => 11,
                'reserved_inventory' => 0,
                'safety_stock' => 5,
                'install_minutes_per_unit' => 90,
            ],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['name' => $product['name']],
                $product,
            );
        }
    }
}
