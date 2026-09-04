<?php

use App\Models\Expense;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingSeeder::class);
});

function adminTestUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

test('admin endpoints require the admin role', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');

    $this->actingAs($sales, 'sanctum')
        ->getJson('/api/settings')
        ->assertForbidden();

    $this->actingAs($sales, 'sanctum')
        ->patchJson('/api/settings', [
            'settings' => ['target_margin' => 25],
        ])
        ->assertForbidden();

    $this->actingAs($sales, 'sanctum')
        ->getJson('/api/dashboard')
        ->assertForbidden();
});

test('admins can read seeded settings and update supported values', function () {
    $admin = adminTestUser();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/settings')
        ->assertSuccessful()
        ->assertJsonPath('settings.target_margin', '20.00')
        ->assertJsonPath('settings.minimum_operating_cash', '10000.00')
        ->assertJsonPath('settings.alerts_cash_enabled', true);

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/settings', [
            'settings' => [
                'target_margin' => 25,
                'minimum_operating_cash' => 5000.50,
                'alerts_cash_enabled' => false,
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('settings.target_margin', '25.00')
        ->assertJsonPath('settings.minimum_operating_cash', '5000.50')
        ->assertJsonPath('settings.alerts_cash_enabled', false);

    $this->assertDatabaseHas('settings', [
        'key' => 'target_margin',
        'value' => '25.00',
        'type' => 'decimal',
    ]);
});

test('settings updates reject unknown keys and invalid values', function () {
    $admin = adminTestUser();

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/settings', [
            'settings' => [
                'unknown_setting' => 1,
                'target_margin' => 101,
                'alerts_cash_enabled' => 'not-a-boolean',
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'settings.unknown_setting',
            'settings.target_margin',
            'settings.alerts_cash_enabled',
        ]);
});

test('admins can view dashboard totals from opportunities, cash, and inventory', function () {
    $admin = adminTestUser();
    Setting::setValues(['minimum_operating_cash' => 0]);

    $product = Product::query()->create([
        'name' => 'Dashboard Product',
        'base_price' => 1000.00,
        'unit_cost' => 600.00,
        'physical_inventory' => 10,
        'reserved_inventory' => 2,
        'safety_stock' => 3,
        'install_minutes_per_unit' => 30,
    ]);

    Opportunity::query()->create([
        'product_id' => $product->id,
        'qty' => 2,
        'unit_price' => 1000.00,
        'due_date' => '2026-10-01',
        'stage' => 'Won',
    ]);
    Opportunity::query()->create([
        'product_id' => $product->id,
        'qty' => 9,
        'unit_price' => 700.00,
        'due_date' => '2026-10-15',
        'stage' => 'Quoted',
    ]);
    Opportunity::query()->create([
        'product_id' => $product->id,
        'qty' => 1,
        'unit_price' => 1000.00,
        'due_date' => '2026-10-20',
        'stage' => 'Lost',
    ]);

    Receipt::query()->create([
        'amount' => 5000.00,
        'date' => '2026-09-01',
        'user_id' => $admin->id,
    ]);
    Payment::query()->create([
        'amount' => 1200.00,
        'date' => '2026-09-02',
        'user_id' => $admin->id,
    ]);
    Expense::query()->create([
        'amount' => 300.00,
        'date' => '2026-09-03',
        'description' => 'Dashboard test expense',
        'user_id' => $admin->id,
    ]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/dashboard')
        ->assertSuccessful()
        ->assertJsonPath('dashboard.firm_revenue', '2000.00')
        ->assertJsonPath('dashboard.operating_profit', '800.00')
        ->assertJsonPath('dashboard.cash_balance', '3500.00')
        ->assertJsonPath('dashboard.open_opportunities', 1)
        ->assertJsonPath('dashboard.won_opportunities', 1)
        ->assertJsonPath('dashboard.at_risk_opportunities', 1)
        ->assertJsonPath('dashboard.critical_alerts', 1)
        ->assertJsonPath('dashboard.alert_counts.risk', 1)
        ->assertJsonPath('dashboard.capacity.required_install_hours', '1.00')
        ->assertJsonPath('dashboard.capacity.utilization_percent', '2.50')
        ->assertJsonPath('dashboard.inventory.0.free_inventory', 8)
        ->assertJsonPath('dashboard.at_risk.0.risks', ['inventory', 'margin']);
});
