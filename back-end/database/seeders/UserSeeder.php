<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's fixed users.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Normal User',
                'email' => 'user@example.com',
                'role' => null,
            ],
            [
                'name' => 'Sales User',
                'email' => 'sales@example.com',
                'role' => 'sales',
            ],
            [
                'name' => 'Operations User',
                'email' => 'operations@example.com',
                'role' => 'operations',
            ],
            [
                'name' => 'Finance User',
                'email' => 'finance@example.com',
                'role' => 'finance',
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => 'password',
                ],
            );

            $user->syncRoles($userData['role'] === null ? [] : [$userData['role']]);
        }
    }
}
