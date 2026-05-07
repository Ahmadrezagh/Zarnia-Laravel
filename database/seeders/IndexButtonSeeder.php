<?php

namespace Database\Seeders;

use App\Models\IndexButton;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IndexButtonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buttons = [
            [
                'title' => 'تا ۲ میلیون تومان',
                'query' => 'maxPrice=2000000',
            ],
            [
                'title' => 'تا ۵ میلیون تومان',
                'query' => 'maxPrice=5000000',
            ],
            [
                'title' => 'تا ۱۰ میلیون تومان',
                'query' => 'maxPrice=10000000',
            ],
            [
                'title' => 'بالاتر از ۱۰ میلیون تومان',
                'query' => 'minPrice=10000000',
            ],
        ];

        foreach ($buttons as $button) {
            IndexButton::updateOrCreate(
                ['query' => $button['query']], // unique field(s) to check
                $button // values to update or insert
            );
        }
    }
}
