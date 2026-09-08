<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Laravel\Sanctum\PersonalAccessToken;

class DemoTokensSeeder extends Seeder
{
    public function run(): void
    {
        // Demo user (ID 4) ke liye token generate
        $user = User::where('email', 'demo.user@hamqadam.test')->first();
        if ($user) {
            $tokenResult = $user->createToken('demo-test-token');
            echo "Demo user token: " . $tokenResult->plainTextToken . "\n";
            echo "Tokenable ID: " . $user->id . "\n";
        }

        // User 2 ke liye bhi token (agar chahiye toh)
        $user2 = User::where('id', 2)->first();
        if ($user2) {
            $tokenResult2 = $user2->createToken('test-token-2');
            echo "User 2 token: " . $tokenResult2->plainTextToken . "\n";
        }
    }
}
