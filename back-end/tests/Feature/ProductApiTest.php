<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

test('the product list requires authentication', function () {
    $this->getJson('/api/products')->assertUnauthorized();
});

test('authenticated users can list products in id order', function () {
    $user = User::factory()->create();
    $user->assignRole('sales');

    Product::query()->create([
        'name' => 'Product 200',
        'base_price' => 2100.00,
        'unit_cost' => 1390.00,
        'physical_inventory' => 27,
        'reserved_inventory' => 0,
        'safety_stock' => 8,
        'install_minutes_per_unit' => 50,
    ]);
    Product::query()->create([
        'name' => 'Product 100',
        'base_price' => 1250.00,
        'unit_cost' => 760.00,
        'physical_inventory' => 34,
        'reserved_inventory' => 0,
        'safety_stock' => 10,
        'install_minutes_per_unit' => 25,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/products')
        ->assertSuccessful()
        ->assertJsonCount(2, 'products')
        ->assertJsonPath('products.0.name', 'Product 200')
        ->assertJsonPath('products.1.name', 'Product 100')
        ->assertJsonPath('products.0.base_price', '2100.00')
        ->assertJsonPath('products.1.install_minutes_per_unit', 25);
});
