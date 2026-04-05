<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@wallet.com',
            'password' => Hash::make('secret123'),
            'role'     => 'admin',
        ]);

        Wallet::create(['user_id' => $admin->id, 'balance' => 0]);
    }
}
