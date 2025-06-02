<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = Category::pluck('id')->toArray();

        Product::all()->each(function ($product) use ($categoryIds) {
            $randomCategoryIds = collect($categoryIds)->random(2);
            $product->categories()->sync($randomCategoryIds); // use attach() to allow multiple
        });
    }
}
