<?php

use App\Models\CapacityAdjustment;
use App\Models\InventoryAdjustment;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function operationsTestUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('operations');

    return $user;
}

function operationsTestProduct(array $attributes = []): Product
{
    return Product::query()->create(array_merge([
        'name' => 'Product 100',
        'base_price' => 1250.00,
        'unit_cost' => 760.00,
        'physical_inventory' => 34,
        'reserved_inventory' => 0,
        'safety_stock' => 10,
        'install_minutes_per_unit' => 25,
    ], $attributes));
}

test('operations endpoints require the operations role', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');

    $this->actingAs($sales, 'sanctum')
        ->postJson('/api/inventory-adjustments', [])
        ->assertForbidden();

    $this->actingAs($sales, 'sanctum')
        ->postJson('/api/capacity-adjustments', [])
        ->assertForbidden();
});

test('operations users can adjust inventory and see affected opportunities', function () {
    $user = operationsTestUser();
    $product = operationsTestProduct(['physical_inventory' => 34]);

    $affected = Opportunity::query()->create([
        'product_id' => $product->id,
        'qty' => 8,
        'unit_price' => 1250.00,
        'due_date' => '2026-10-15',
        'stage' => 'Quoted',
    ]);
    Opportunity::query()->create([
        'product_id' => $product->id,
        'qty' => 2,
        'unit_price' => 1250.00,
        'due_date' => '2026-10-20',
        'stage' => 'Lost',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/inventory-adjustments', [
            'product_id' => $product->id,
            'new_quantity' => 20,
            'reason' => 'Cycle count correction',
        ])
        ->assertCreated()
        ->assertJsonPath('inventory_adjustment.product_id', $product->id)
        ->assertJsonPath('inventory_adjustment.new_quantity', 20)
        ->assertJsonPath('inventory_adjustment.user_id', $user->id)
        ->assertJsonCount(1, 'affected_opportunities')
        ->assertJsonPath('affected_opportunities.0.id', $affected->id);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'physical_inventory' => 20,
    ]);
    $this->assertDatabaseHas('inventory_adjustments', [
        'product_id' => $product->id,
        'new_quantity' => 20,
        'user_id' => $user->id,
    ]);
});

test('inventory adjustment validation rejects missing and negative quantities', function () {
    $user = operationsTestUser();
    $product = operationsTestProduct();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/inventory-adjustments', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['product_id', 'new_quantity', 'reason']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/inventory-adjustments', [
            'product_id' => $product->id,
            'new_quantity' => -1,
            'reason' => 'Invalid correction',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['new_quantity']);
});

test('operations users can adjust capacity and see opportunities due on that date', function () {
    $user = operationsTestUser();
    $product = operationsTestProduct();

    $affected = Opportunity::query()->create([
        'product_id' => $product->id,
        'qty' => 4,
        'unit_price' => 1250.00,
        'due_date' => '2026-11-01',
        'stage' => 'New',
    ]);
    Opportunity::query()->create([
        'product_id' => $product->id,
        'qty' => 4,
        'unit_price' => 1250.00,
        'due_date' => '2026-11-02',
        'stage' => 'Quoted',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/capacity-adjustments', [
            'date' => '2026-11-01',
            'available_hours' => 16.5,
            'reason' => 'Maintenance day capacity update',
        ])
        ->assertCreated()
        ->assertJsonPath('capacity_adjustment.date', '2026-11-01')
        ->assertJsonPath('capacity_adjustment.available_hours', '16.50')
        ->assertJsonPath('capacity_adjustment.user_id', $user->id)
        ->assertJsonCount(1, 'affected_opportunities')
        ->assertJsonPath('affected_opportunities.0.id', $affected->id);

    $this->assertDatabaseHas('capacity_adjustments', [
        'date' => '2026-11-01',
        'available_hours' => 16.5,
        'user_id' => $user->id,
    ]);
});

test('capacity adjustment validation rejects missing, invalid dates, and negative hours', function () {
    $user = operationsTestUser();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/capacity-adjustments', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date', 'available_hours', 'reason']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/capacity-adjustments', [
            'date' => 'not-a-date',
            'available_hours' => -1,
            'reason' => 'Invalid adjustment',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date', 'available_hours']);
});
