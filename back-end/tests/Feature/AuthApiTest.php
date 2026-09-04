<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
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
