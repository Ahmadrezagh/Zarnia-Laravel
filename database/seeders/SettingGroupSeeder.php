<?php

namespace Database\Seeders;

use App\Models\SettingGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            'وبسایت',
            'Tahesab API',
        ];
        foreach ($groups as $group)
        {
            SettingGroup::create([
                'title' => $group
            ]);
        }
    }
}
