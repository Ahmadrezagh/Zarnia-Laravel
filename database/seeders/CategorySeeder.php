<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'title' => 'نیم ست',
                'image' => 'img/categories/image8.png'
            ],
            [
                'title' => 'گردنبند',
                'image' => 'img/categories/image7.png'
            ],
            [
                'title' => 'انگشتر',
                'image' => 'img/categories/image6.png'
            ],
            [
                'title' => 'گردنبند2',
                'image' => 'img/categories/image5.png'
            ],
            [
                'title' => 'آویز',
                'image' => 'img/categories/image20.png'
            ],
            [
                'title' => 'گردنبند3',
                'image' => 'img/categories/image21.png'
            ],
            [
                'title' => 'زنجیر',
                'image' => 'img/categories/image11.png'
            ],
            [
                'title' => 'گوشواره',
                'image' => 'img/categories/image10.png'
            ],
        ];

        foreach ($categories as $data) {
            $category = Category::firstOrCreate(
                ['title' => $data['title']]
            );

            // Check if it already has media in the 'images' collection
            if ($category->getMedia('images')->isEmpty()) {
                $category->assignImageFromPublicPath(public_path($data['image']));
            }
        }
    }
}
