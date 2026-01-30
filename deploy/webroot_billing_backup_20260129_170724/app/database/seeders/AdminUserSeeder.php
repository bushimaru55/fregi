<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存の管理者アカウント
        User::updateOrCreate(
            ['email' => 'dsbrand@example.com'],
            [
                'name' => 'dsbrand',
                'email' => 'dsbrand@example.com',
                'password' => Hash::make('cs20051101'),
                'email_verified_at' => now(),
            ]
        );

        // 新しい管理者アカウント
        User::updateOrCreate(
            ['email' => 'kanri@dschatbot.ai'],
            [
                'name' => 'kanri',
                'email' => 'kanri@dschatbot.ai',
                'password' => Hash::make('cs20051101'),
                'email_verified_at' => now(),
            ]
        );
    }
}
