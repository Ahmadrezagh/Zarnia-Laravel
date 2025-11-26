<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\SettingGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PanelIpAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find or create Security settings group
        $securityGroup = SettingGroup::query()->updateOrCreate(
            ['title' => 'امنیت'],
            ['title' => 'امنیت']
        );

        // Create or update the panel_allowed_ips setting
        Setting::query()->updateOrCreate(
            ['key' => 'panel_allowed_ips'],
            [
                'key' => 'panel_allowed_ips',
                'type' => 'textarea',
                'title' => 'آی‌پی‌های مجاز پنل (برای دسترسی به همه از # استفاده کنید)',
                'value' => '#',
                'setting_group_id' => $securityGroup->id
            ]
        );
    }
}

