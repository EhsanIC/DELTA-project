<?php

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function financeTestUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('finance');

    return $user;
}

test('finance endpoints require the finance role', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');

    $this->actingAs($sales, 'sanctum')
        ->postJson('/api/receipts', [])
        ->assertForbidden();

    $this->actingAs($sales, 'sanctum')
        ->getJson('/api/cash-summary')
        ->assertForbidden();
});

test('finance users can create a receipt, payment, and expense', function () {
    $user = financeTestUser();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/receipts', [
            'amount' => 2500.00,
            'date' => '2026-09-04',
        ])
        ->assertCreated()
        ->assertJsonPath('receipt.amount', '2500.00')
        ->assertJsonPath('receipt.date', '2026-09-04')
        ->assertJsonPath('receipt.user_id', $user->id);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/payments', [
            'amount' => 800.00,
            'date' => '2026-09-05',
        ])
        ->assertCreated()
        ->assertJsonPath('payment.amount', '800.00')
        ->assertJsonPath('payment.date', '2026-09-05')
        ->assertJsonPath('payment.user_id', $user->id);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/expenses', [
            'amount' => 125.50,
            'date' => '2026-09-06',
            'description' => 'Office supplies',
        ])
        ->assertCreated()
        ->assertJsonPath('expense.amount', '125.50')
        ->assertJsonPath('expense.description', 'Office supplies')
        ->assertJsonPath('expense.user_id', $user->id);

    $this->assertDatabaseHas('receipts', [
        'amount' => 2500.00,
        'date' => '2026-09-04',
        'user_id' => $user->id,
    ]);
    $this->assertDatabaseHas('payments', [
        'amount' => 800.00,
        'date' => '2026-09-05',
        'user_id' => $user->id,
    ]);
    $this->assertDatabaseHas('expenses', [
        'amount' => 125.50,
        'date' => '2026-09-06',
        'description' => 'Office supplies',
        'user_id' => $user->id,
    ]);
});

test('finance entry validation rejects missing fields, negative amounts, and invalid dates', function () {
    $user = financeTestUser();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/receipts', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount', 'date']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/payments', [
            'amount' => -1,
            'date' => 'not-a-date',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount', 'date']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/expenses', [
            'amount' => 50,
            'date' => '2026-09-04',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['description']);
});

test('cash summary returns receipts minus payments and expenses', function () {
    $user = financeTestUser();

    Receipt::query()->create([
        'amount' => 5000.00,
        'date' => '2026-09-01',
        'user_id' => $user->id,
    ]);
    Payment::query()->create([
        'amount' => 1200.00,
        'date' => '2026-09-02',
        'user_id' => $user->id,
    ]);
    Expense::query()->create([
        'amount' => 350.75,
        'date' => '2026-09-03',
        'description' => 'Utilities',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/cash-summary')
        ->assertSuccessful()
        ->assertJsonPath('cash_summary.receipts', '5000.00')
        ->assertJsonPath('cash_summary.payments', '1200.00')
        ->assertJsonPath('cash_summary.expenses', '350.75')
        ->assertJsonPath('cash_summary.current_balance', '3449.25');
});
