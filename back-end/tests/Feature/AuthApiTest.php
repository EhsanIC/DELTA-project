<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});
test('users can register without a role and receive a sanctum token', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.email', 'new@example.com')
        ->assertJsonPath('user.roles', []);

    $this->assertDatabaseHas('users', [
        'email' => 'new@example.com',
        'name' => 'New User',
    ]);
});

test('users can register with a non-admin role', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Operations User',
        'email' => 'new-operations@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'operations',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('user.roles.0', 'operations');

    expect(User::query()->where('email', 'new-operations@example.com')->first()->hasRole('operations'))->toBeTrue();
});

test('public registration cannot assign the admin role', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Unauthorized Admin',
        'email' => 'unauthorized-admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});

test('seeded users have the expected roles', function () {
    $this->seed(UserSeeder::class);

    expect(User::query()->count())->toBe(5)
        ->and(User::query()->where('email', 'user@example.com')->first()->roles)->toHaveCount(0)
        ->and(User::query()->where('email', 'sales@example.com')->first()->hasRole('sales'))->toBeTrue()
        ->and(User::query()->where('email', 'operations@example.com')->first()->hasRole('operations'))->toBeTrue()
        ->and(User::query()->where('email', 'finance@example.com')->first()->hasRole('finance'))->toBeTrue()
        ->and(User::query()->where('email', 'admin@example.com')->first()->hasRole('admin'))->toBeTrue();
});

test('users can log in and receive a sanctum token', function () {
    $user = User::factory()->create([
        'email' => 'sales@example.com',
        'password' => 'password',
    ]);
    $user->assignRole('sales');

    $response = $this->postJson('/api/auth/login', [
        'email' => 'sales@example.com',
        'password' => 'password',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.email', 'sales@example.com')
        ->assertJsonPath('user.roles.0', 'sales');

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

test('invalid credentials are rejected', function () {
    User::factory()->create([
        'email' => 'sales@example.com',
        'password' => 'password',
    ]);

    $this->postJson('/api/auth/login', [
        'email' => 'sales@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(422);
});

test('sanctum protects the current user endpoint', function () {
    $this->getJson('/api/user')->assertUnauthorized();

    $user = User::factory()->create();
    $user->assignRole('sales');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/user')
        ->assertSuccessful()
        ->assertJsonPath('user.roles.0', 'sales');
});

test('role middleware denies non-admin users and permits admins', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');

    $this->actingAs($sales, 'sanctum')
        ->getJson('/api/admin/access-check')
        ->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/access-check')
        ->assertSuccessful();
});

test('cors allows the configured frontend origin', function () {
    $this->options('/api/auth/login', [], [
        'Origin' => 'http://localhost:3000',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'Content-Type',
    ])->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
});
