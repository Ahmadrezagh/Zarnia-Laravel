<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SnappTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staticToken = 'aa20984c-eaff-485a-9548-2b734abb43b8';
        
        // Check if user with this phone already exists
        $user = User::where('phone', '09920435523')->first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Snapp Test User',
                'phone' => '09920435523',
                'type' => User::$TYPES[2], // USER
                'password' => '1234567890',
            ]);
            
            $this->command->info('Snapp test user created successfully with phone: 09920435523');
        } else {
            $this->command->warn('User with phone 09920435523 already exists.');
        }

        // Delete existing token if exists
        DB::table('personal_access_tokens')
            ->where('tokenable_id', $user->id)
            ->where('tokenable_type', User::class)
            ->where('name', 'snapp_test_token')
            ->delete();

        // Insert token record first to get the ID
        $tokenId = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'snapp_test_token',
            'token' => '', // Temporary, will update
            'abilities' => json_encode(['*']), // All abilities
            'expires_at' => null, // No expiration - token never expires
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Now create the full token format: {id}|{staticToken}
        $fullToken = $tokenId . '|' . $staticToken;
        $hashedToken = hash('sha256', $fullToken);

        // Update with the correct hash
        DB::table('personal_access_tokens')
            ->where('id', $tokenId)
            ->update([
                'token' => substr($hashedToken, 0, 64), // First 64 chars of hash
                'expires_at' => null, // Ensure no expiration
            ]);

        $this->command->info("Static token created successfully!");
        $this->command->info("Full token: {$fullToken}");
    }
}
