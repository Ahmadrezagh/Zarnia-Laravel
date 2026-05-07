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
            
            if ($firstAddress && $firstAddress->receiver_name) {
                $user->name = $firstAddress->receiver_name;
                $user->save();
                
                $this->command->info("Updated name for user {$user->id} to {$firstAddress->receiver_name}");
            }
        }
        
        $this->command->info('Finished updating user names from addresses.');
    }
}
