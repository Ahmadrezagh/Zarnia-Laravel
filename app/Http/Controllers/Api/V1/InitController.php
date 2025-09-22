<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SettingGroup;
use Illuminate\Http\Request;

class InitController extends Controller
{
    public function index()
    {
        $footer_setting_group = SettingGroup::query()->where('title', 'فوتر')->first();
        $footer = [];
        $footer_settings = Setting::query()->where('setting_group_id', $footer_setting_group->id)->get();
        $gold_price = Product::query()->whereNotNull('mazaneh')->first() ? Product::query()->whereNotNull('mazaneh')->first()->mazaneh : 0;
        foreach ($footer_settings as $setting) {
            $footer[$setting->key] = $setting->value;
        }
        return response()->json([
            'name' => setting('name'),
            'telegram' => setting('telegram'),
            'whatsapp' => setting('whatsapp'),
            'instagram' => setting('instagram'),
            'gold_price' => number_format($gold_price),
            'footer' => $footer
        ]);
    }
}
