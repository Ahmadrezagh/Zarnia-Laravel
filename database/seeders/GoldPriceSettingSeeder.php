<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GoldPriceSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $setting = [
            'key' => 'gold_price',
            'type' => 'numeric',
            'title' => 'قیمت طلا (گرم 18 عیار)',
            'value' => '0',
            'setting_group_id' => '1' // وبسایت group
        ];

        Setting::updateOrCreate(
            ['key' => $setting['key']],
            $setting
        );
    }
}

