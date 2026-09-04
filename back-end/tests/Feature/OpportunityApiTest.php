<?php

use App\Models\Opportunity;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function salesUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('sales');

    return $user;
}

function product(array $attributes = []): Product
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

test('opportunity routes require the sales role', function () {
    $user = User::factory()->create();
    $user->assignRole('operations');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/opportunities')
        ->assertForbidden();
});

test('a sales user can log in and complete the opportunity flow', function () {
    $user = salesUser();
    $product = product();

    $loginResponse = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSuccessful();
    $headers = [
        'Authorization' => 'Bearer '.$loginResponse->json('token'),
    ];

    $this->withHeaders($headers)
        ->getJson('/api/opportunities')
        ->assertSuccessful()
        ->assertJsonCount(0, 'opportunities');

    $createResponse = $this->withHeaders($headers)
        ->postJson('/api/opportunities', [
            'product_id' => $product->id,
            'qty' => 2,
            'unit_price' => 1300.00,
            'due_date' => '2026-10-20',
            'stage' => 'Quoted',
        ])
        ->assertCreated();
    $opportunityId = $createResponse->json('opportunity.id');

    $this->withHeaders($headers)
        ->patchJson("/api/opportunities/{$opportunityId}", [
            'stage' => 'Won',
        ])
        ->assertSuccessful()
        ->assertJsonPath('opportunity.stage', 'Won');
});

test('sales users can list seeded opportunities', function () {
    $user = salesUser();
    $product = product();

    Opportunity::query()->create([
        'product_id' => $product->id,
        'qty' => 4,
        'unit_price' => 1200.00,
        'due_date' => '2026-10-01',
        'stage' => 'New',
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/opportunities')
        ->assertSuccessful()
        ->assertJsonCount(1, 'opportunities')
        ->assertJsonPath('opportunities.0.product.name', 'Product 100')
        ->assertJsonPath('opportunities.0.revenue', '4800.00')
        ->assertJsonPath('opportunities.0.cost_of_goods', '3040.00')
        ->assertJsonPath('opportunities.0.operating_profit', '1760.00')
        ->assertJsonPath('opportunities.0.margin_percent', '36.67');
});

test('sales users can create opportunities with calculated values', function () {
    $user = salesUser();
    $product = product();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/opportunities', [
            'product_id' => $product->id,
            'qty' => 2,
            'unit_price' => 1300.00,
            'due_date' => '2026-10-20',
            'stage' => 'Quoted',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('opportunity.stage', 'Quoted')
        ->assertJsonPath('opportunity.revenue', '2600.00')
        ->assertJsonPath('opportunity.cost_of_goods', '1520.00')
        ->assertJsonPath('opportunity.operating_profit', '1080.00')
        ->assertJsonPath('opportunity.margin_percent', '41.54');

    $this->assertDatabaseHas('opportunities', [
        'product_id' => $product->id,
        'qty' => 2,
        'stage' => 'Quoted',
    ]);
});

test('opportunity creation validates required fields, quantity, date, and stage', function () {
    $user = salesUser();
    $product = product();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/opportunities', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'product_id',
            'qty',
            'unit_price',
            'due_date',
            'stage',
        ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/opportunities', [
            'product_id' => $product->id,
            'qty' => -1,
            'unit_price' => 1000,
            'due_date' => 'not-a-date',
            'stage' => 'Pending',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['qty', 'due_date', 'stage']);
});

test('sales users can update an opportunity and its stage', function () {
    $user = salesUser();
    $product = product();
    $opportunity = Opportunity::query()->create([
        'product_id' => $product->id,
        'qty' => 3,
        'unit_price' => 1200.00,
        'due_date' => '2026-10-01',
        'stage' => 'New',
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/opportunities/{$opportunity->id}", [
            'qty' => 5,
            'stage' => 'Quoted',
        ])
        ->assertSuccessful()
        ->assertJsonPath('opportunity.qty', 5)
        ->assertJsonPath('opportunity.stage', 'Quoted');

    $this->assertDatabaseHas('opportunities', [
        'id' => $opportunity->id,
        'qty' => 5,
        'stage' => 'Quoted',
    ]);
});

test('won opportunities reserve inventory and changing stage reverses the reservation', function () {
    $user = salesUser();
    $product = product(['physical_inventory' => 10]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/opportunities', [
            'product_id' => $product->id,
            'qty' => 4,
            'unit_price' => 1250.00,
            'due_date' => '2026-10-30',
            'stage' => 'Won',
        ])
        ->assertCreated()
        ->assertJsonPath('opportunity.product.reserved_inventory', 4);

    $opportunityId = $response->json('opportunity.id');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'reserved_inventory' => 4,
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/opportunities/{$opportunityId}", [
            'qty' => 6,
            'stage' => 'Won',
        ])
        ->assertSuccessful()
        ->assertJsonPath('opportunity.product.reserved_inventory', 6);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'reserved_inventory' => 6,
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/opportunities/{$opportunityId}", [
            'stage' => 'Lost',
        ])
        ->assertSuccessful()
        ->assertJsonPath('opportunity.stage', 'Lost');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'reserved_inventory' => 0,
    ]);
});
