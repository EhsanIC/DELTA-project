<?php

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function customerSalesUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('sales');

    return $user;
}

function customerTestProduct(): Product
{
    return Product::query()->create([
        'name' => 'Customer Product',
        'base_price' => 1000.00,
        'unit_cost' => 600.00,
        'physical_inventory' => 10,
        'reserved_inventory' => 0,
        'safety_stock' => 2,
        'install_minutes_per_unit' => 30,
    ]);
}

test('the customer list requires the sales role', function () {
    $this->getJson('/api/customers')->assertUnauthorized();

    $operations = User::factory()->create();
    $operations->assignRole('operations');

    $this->actingAs($operations, 'sanctum')
        ->getJson('/api/customers')
        ->assertForbidden();
});

test('sales users can list customers in name order', function () {
    $user = customerSalesUser();

    Customer::query()->create([
        'name' => 'Zeta Customer',
        'email' => 'zeta@example.com',
        'phone' => '+1-555-0202',
    ]);
    Customer::query()->create([
        'name' => 'Alpha Customer',
        'email' => 'alpha@example.com',
        'phone' => '+1-555-0201',
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/customers')
        ->assertSuccessful()
        ->assertJsonCount(2, 'customers')
        ->assertJsonPath('customers.0.name', 'Alpha Customer')
        ->assertJsonPath('customers.1.name', 'Zeta Customer')
        ->assertJsonPath('customers.0.email', 'alpha@example.com');
});

test('sales users can create and update an opportunity with a customer', function () {
    $user = customerSalesUser();
    $product = customerTestProduct();
    $customer = Customer::query()->create([
        'name' => 'Linked Customer',
        'email' => 'linked@example.com',
        'phone' => '+1-555-0301',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/opportunities', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'qty' => 2,
            'unit_price' => 1300.00,
            'due_date' => '2026-10-20',
            'stage' => 'Quoted',
        ])
        ->assertCreated()
        ->assertJsonPath('opportunity.customer_id', $customer->id)
        ->assertJsonPath('opportunity.customer.name', 'Linked Customer');

    $opportunityId = $response->json('opportunity.id');

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/opportunities/'.$opportunityId, [
            'customer_id' => null,
        ])
        ->assertSuccessful()
        ->assertJsonPath('opportunity.customer_id', null)
        ->assertJsonPath('opportunity.customer', null);

    $this->assertDatabaseHas('opportunities', [
        'id' => $opportunityId,
        'customer_id' => null,
    ]);

    expect($customer->fresh()->opportunities)->toHaveCount(0);
});

test('opportunity customer validation rejects an unknown customer', function () {
    $user = customerSalesUser();
    $product = customerTestProduct();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/opportunities', [
            'customer_id' => 999999,
            'product_id' => $product->id,
            'qty' => 1,
            'unit_price' => 1000.00,
            'due_date' => '2026-10-20',
            'stage' => 'New',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['customer_id']);
});
