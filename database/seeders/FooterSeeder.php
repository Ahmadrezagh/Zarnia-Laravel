<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\SettingGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FooterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $setting_group = SettingGroup::updateOrCreate(['title' => 'فوتر'],['title' => 'فوتر']);
        $settings = [
            [   'key' =>'footer_text',
                'type'=>'string',
                'title'=>'متن فوتر',
                'value'=>'ما در گالری زرنیا به دنبال ایجاد یک تجربه خرید لذت‌بخش و خاطره‌انگیز برای شما هستیم.همچنین، با ارائه ضمانت اصالت و کیفیت، اطمینان خاطر شما را فراهم می‌کنیم.',
                'setting_group_id'=>$setting_group->id
            ],
            [   'key' =>'address',
                'type'=>'string',
                'title'=>'آدرس',
                'value'=>'تهران، صادقیه، پاساژ زرینا | شنبه تا پنجشنبه',
                'setting_group_id'=>$setting_group->id
            ],
            [   'key' =>'open_time',
                'type'=>'string',
                'title'=>'ساعات کاری',
                'value'=>' ۱۱ - ۲۱',
                'setting_group_id'=>$setting_group->id
            ],
            [   'key' =>'phone_1',
                'type'=>'string',
                'title'=>'شماره تماس ۱',
                'value'=>'0919-304-6488',
                'setting_group_id'=>$setting_group->id
            ],
            [   'key' =>'phone_2',
                'type'=>'string',
                'title'=>'شماره تماس ۲',
                'value'=>'0912-712-7053',
                'setting_group_id'=>$setting_group->id
            ],
            [   'key' =>'phone_3',
                'type'=>'string',
                'title'=>'شماره تماس ۳',
                'value'=>'021-44242074',
                'setting_group_id'=>$setting_group->id
            ],
        ];
        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
