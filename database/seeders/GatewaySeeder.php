<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gateways = [
            [
                'title' => 'قسطی',
                'sub_title' => 'اسنپ پی',
                'image' => 'uploads/snapp.png',
            ],
            [
                'title' => 'نقدی',
                'sub_title' => 'درگاه پرداخت',
                'image' => 'uploads/sep.png'
            ]
        ];
        foreach ($gateways as $gatewayData) {
            $gateway = Gateway::updateOrCreate(
                ['title' => $gatewayData['title']],
                [
                    'title' => $gatewayData['title'],
                    'sub_title' => $gatewayData['sub_title'],
                ]
            );

            // Add image using Spatie Media Library
            $imagePath = public_path($gatewayData['image']);
            if (file_exists($imagePath)) {
                $gateway->clearMediaCollection('image');
                $gateway->addMedia($imagePath)
                    ->preservingOriginal()
                    ->toMediaCollection('image');
            }
        }
    }
}
