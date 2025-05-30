<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [   'key' =>'name',
                'type'=>'string',
                'title'=>'نام وبسایت',
                'value'=>'پنل ادمین لاراول ۱۱',
                'setting_group_id'=>'1'
            ],
            [   'key' =>'logo',
                'type'=>'file',
                'title'=>'لوگو',
                'value'=>'/uploads/settings/logo.png',
                'setting_group_id'=>'1'
            ],
            [   'key' =>'url',
                'type'=>'string',
                'title'=>'آدرس وبسایت',
                'value'=>'http://localhost:8000',
                'setting_group_id'=>'1'
            ],

            [   'key' =>'api_endpoint',
                'type'=>'string',
                'title'=>'آدرس سرور',
                'value'=>'https://45.144.18.113:8081',
                'setting_group_id'=>'2'
            ],
            [   'key' =>'api_key',
                'type'=>'string',
                'title'=>'کلید',
                'value'=>'Q5T7D4G6D8C4T2A8O3U2F7F6K6F5D2L4E8C7S1T4R8Q3U2I4Y5X2D5Y2I2F1V6H2N4M8A5A2W1V4K3N5',
                'setting_group_id'=>'2'
            ],
            [   'key' =>'api_db',
                'type'=>'string',
                'title'=>'دیتابیس',
                'value'=>'TahesabDB',
                'setting_group_id'=>'2'
            ],
        ];
        foreach ($settings as $setting)
        {
            Setting::create($setting);
        }
    }
}
