<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductSlider;
use App\Models\ProductSliderButton;

class ProductSliderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sliders = [
            [
                'title' => 'تخفیف ها',
                'query' => 'hasDiscount=1',
                'show_more' => true,
                'buttons' => []
            ],
            [
                'title' => 'جدیدترین محصولات',
                'query' => 'per_page=15',
                'show_more' => true,
                'buttons' => [
                    [
                        'title' => 'پلاک طلا',
                        'query' => 'category_ids[1]=1'
                    ],
                    [
                        'title' => 'گوشواره طلا',
                        'query' => 'category_ids[2]=2'
                    ],
                    [
                        'title' => 'انگشتر طلا',
                        'query' => 'category_ids[3]=3'
                    ],
                    [
                        'title' => 'گردنبند طلا',
                        'query' => 'category_ids[4]=4'
                    ],
                    [
                        'title' => 'دستبند طلا',
                        'query' => 'category_ids[5]=5'
                    ],
                    [
                        'title' => 'نیم ست طلا',
                        'query' => 'category_ids[6]=6'
                    ],
                    [
                        'title' => 'زنجیر طلا',
                        'query' => 'category_ids[7]=7'
                    ],
                    [
                        'title' => 'کادویی',
                        'query' => 'category_ids[8]=8'
                    ],
                ]
            ]
        ];

        foreach ($sliders as $sliderData) {
            // create slider
            $buttons = $sliderData['buttons'] ?? [];
            unset($sliderData['buttons']);

            $slider = ProductSlider::create($sliderData);

            // create related buttons
            foreach ($buttons as $btn) {
                ProductSliderButton::create([
                    'product_slider_id' => $slider->id,
                    'title' => $btn['title'],
                    'query' => $btn['query'],
                ]);
            }
        }
    }
}
