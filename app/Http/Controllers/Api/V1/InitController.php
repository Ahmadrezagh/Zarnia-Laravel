<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SettingGroup;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class InitController extends Controller
{
    public function index()
    {
        $footer_setting_group = SettingGroup::query()->where('title', 'فوتر')->first();
        $footer = [];
        $footer_settings = Setting::query()->where('setting_group_id', $footer_setting_group->id)->get();
        $gold_price = get_gold_price();
        foreach ($footer_settings as $setting) {
            $footer[$setting->key] = $setting->value;
        }

        // Generate next 7 days with both Gregorian and Jalali dates
        $persian_day_names = [
            'یکشنبه',
            'دوشنبه',
            'سه‌شنبه',
            'چهارشنبه',
            'پنج‌شنبه',
            'جمعه',
            'شنبه'
        ];
        
        $next_seven_days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i);
            $jalali = Jalalian::forge($date);
            $day_of_week = $date->dayOfWeek; // 0 = Sunday, 6 = Saturday
            
            $next_seven_days[] = [
                'gregorian' => $date->format('Y-m-d'),
                'jalali' => $jalali->format('Y-m-d'),
                'day_name' => $persian_day_names[$day_of_week],
            ];
        }

        return response()->json([
            'name' => setting('name'),
            'telegram' => setting('telegram'),
            'whatsapp' => setting('whatsapp'),
            'instagram' => setting('instagram'),
            'gold_price' => number_format($gold_price/10),
            'footer' => $footer,
            'next_seven_days' => $next_seven_days
        ]);
    }
}
