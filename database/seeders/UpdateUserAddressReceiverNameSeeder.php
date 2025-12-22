<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UpdateUserAddressReceiverNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::with('addresses')->get();
        
        foreach ($users as $user) {
            $firstAddress = $user->addresses->first();
            
            if ($firstAddress && $user->name) {
                $firstAddress->receiver_name = $user->name;
                $firstAddress->save();
                
                $this->command->info("Updated receiver_name for user {$user->id} ({$user->name})");
            }
        }
        
        $this->command->info('Finished updating receiver names in addresses.');
    }
}
