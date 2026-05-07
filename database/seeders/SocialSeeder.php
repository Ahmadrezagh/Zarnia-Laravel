<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\SettingGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SocialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $setting_group = SettingGroup::query()->updateOrCreate(['title' => 'سوشال مدیا'],['title' => 'سوشال مدیا']);
        $settings = [
            [   'key' =>'instagram',
                'type'=>'string',
                'title'=>'آدرس اینستاگرام',
                'value'=>'#',
                'setting_group_id'=>$setting_group->id
            ],
            [   'key' =>'whatsapp',
                'type'=>'string',
                'title'=>'شماره تماس واتس اپ',
                'value'=>'#',
                'setting_group_id'=>$setting_group->id
            ],
            [   'key' =>'telegram',
                'type'=>'string',
                'title'=>'لینک تلگرام',
                'value'=>'#',
                'setting_group_id'=>$setting_group->id
            ],
        ];
        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
