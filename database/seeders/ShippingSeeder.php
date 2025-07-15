<?php

namespace Database\Seeders;

use App\Models\Shipping;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shippings = [
            [
                'title' => 'پست',
                'price' => 100000,
                'passive_price' => 0,
                'shipping_times' => [],
                'image' => 'uploads/post.png'
            ],
            [
                'title' => 'پیک(فقط تهران)',
                'price' => 0,
                'passive_price' => 1,
                'shipping_times' => [],
                'image' => 'uploads/peyk.png'
            ],
            [
                'title' => 'دریافت حضوری',
                'price' => 0,
                'passive_price' => 0,
                'shipping_times' => [
                    'بازه ۱۳ - ۱۵',
                    'بازه ۱۵ - ۱۷',
                    'بازه ۱۷ - ۲۰',
                ],
                'image' => 'uploads/hozoori.png'
            ],
        ];

        foreach ($shippings as $shippingData) {
            $shipping = Shipping::query()->updateOrCreate(
                ['title' => $shippingData['title']],
                [
                    'price' => $shippingData['price'],
                    'passive_price' => $shippingData['passive_price'] ?? 0,
                ]
            );

            foreach ($shippingData['shipping_times'] as $timeTitle) {
                $shipping->times()->firstOrCreate(['title' => $timeTitle]);
            }
            // Add image using Spatie Media Library
            $imagePath = public_path($shippingData['image']);
            if (file_exists($imagePath)) {
                // Optional: clear previous media
                $shipping->clearMediaCollection('image');

                $shipping->addMedia($imagePath)
                    ->preservingOriginal()
                    ->toMediaCollection('image');
            }
        }
    }
}
