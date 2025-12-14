<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buyer
        User::factory()->create([
            'name' => 'Buyer Demo',
            'email' => 'buyer@test.com',
            'password' => Hash::make('password'),
            'balance' => 10000,
        ]);

        // Seller
        $seller = User::factory()->create([
            'name' => 'Seller Demo',
            'email' => 'seller@test.com',
            'password' => Hash::make('password'),
            'balance' => 10000,
        ]);
        Asset::create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => 1,
            'locked_amount' => 0,
        ]);

        // Trader (optional, empty balance)
        User::factory()->create([
            'name' => 'Trader Demo',
            'email' => 'trader@test.com',
            'password' => Hash::make('password'),
            'balance' => 5000,
        ]);
    }
}
