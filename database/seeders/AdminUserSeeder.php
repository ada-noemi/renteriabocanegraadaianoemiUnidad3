<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador de demostración',
                'password' => Hash::make('Admin123!'),
            ]
        );
        $user->forceFill(['role' => User::ROLE_ADMIN])->save();
    }
}
