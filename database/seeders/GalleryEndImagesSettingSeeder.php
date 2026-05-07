<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GalleryEndImagesSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            [
                'key' => 'gallery_end_images',
            ],
            [
                'type' => 'image_array',
                'title' => 'عکس های انتهای گالری',
                'value' => '[]',
                'setting_group_id' => 1
            ]
        );
    }
}

