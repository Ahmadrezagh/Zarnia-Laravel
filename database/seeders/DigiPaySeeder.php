<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Seeder;

class DigiPaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Gateway::updateOrCreate(
            ['key' => 'digipay'],
            [
                'title' => 'دیجی پی',
                'sub_title' => 'پرداخت از طریق دیجی پی',
                'color' => '#4CAF50',
                'admin_only' => true,
            ]
        );
    }
}

