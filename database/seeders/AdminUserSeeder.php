<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 Admin
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('123456'),
                'role' => 'admin',
                'is_active' => true,
                'birth_date' => '1995-01-01',
                'address' => 'HCM City',
            ]
        );

        // 🔹 Customer test (vào trang /shop)
        User::updateOrCreate(
            ['email' => 'customer@gmail.com'],
            [
                'name' => 'Customer Test',
                'password' => Hash::make('123456'),
                'role' => 'customer',
                'is_active' => true,
                'birth_date' => '2000-05-10',
                'address' => 'Ha Noi',
            ]
        );

        // 🔹 Customer thứ 2 (để test thêm)
        User::updateOrCreate(
            ['email' => 'user@gmail.com'],
            [
                'name' => 'User Demo',
                'password' => Hash::make('123456'),
                'role' => 'customer',
                'is_active' => true,
                'birth_date' => '1998-09-09',
                'address' => 'Da Nang',
            ]
        );
    }
}
