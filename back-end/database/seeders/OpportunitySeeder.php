<?php

namespace Database\Seeders;

use App\Models\Opportunity;
use App\Models\Product;
use Illuminate\Database\Seeder;
use RuntimeException;

class OpportunitySeeder extends Seeder
{
    /**
     * Seed the application's sales opportunities.
     */
    public function run(): void
    {
        $products = Product::query()->pluck('id', 'name');

        $opportunities = [
            [
                'product' => 'Product 100',
                'qty' => 4,
                'unit_price' => 1200.00,
                'due_date' => '2026-10-01',
                'stage' => 'New',
            ],
            [
                'product' => 'Product 100',
                'qty' => 8,
                'unit_price' => 1250.00,
                'due_date' => '2026-10-15',
                'stage' => 'Quoted',
            ],
            [
                'product' => 'Product 200',
                'qty' => 3,
                'unit_price' => 2050.00,
                'due_date' => '2026-11-01',
                'stage' => 'Quoted',
            ],
            [
                'product' => 'Product 300',
                'qty' => 2,
                'unit_price' => 3600.00,
                'due_date' => '2026-11-15',
                'stage' => 'Lost',
            ],
            [
                'product' => 'Product 200',
                'qty' => 5,
                'unit_price' => 2100.00,
                'due_date' => '2026-12-01',
                'stage' => 'New',
            ],
        ];

        foreach ($opportunities as $opportunity) {
            $productId = $products->get($opportunity['product']);

            if ($productId === null) {
                throw new RuntimeException("Cannot seed opportunity: {$opportunity['product']} does not exist.");
            }

            Opportunity::query()->updateOrCreate(
                [
                    'product_id' => $productId,
                    'due_date' => $opportunity['due_date'],
                ],
                [
                    'qty' => $opportunity['qty'],
                    'unit_price' => $opportunity['unit_price'],
                    'stage' => $opportunity['stage'],
                ],
            );
        }
    }
}
