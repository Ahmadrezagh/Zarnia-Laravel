<?php

namespace Database\Seeders;

use App\Models\Blog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 50 blogs using factory
        $blogs = Blog::factory()->count(50)->create();

        foreach ($blogs as $blog) {
            // Define a sample image path
            $imagePath = public_path('uploads/blog.png');

            // Make sure the image exists
            if (file_exists($imagePath)) {
                // Optional: clear existing media if re-running seeder
                $blog->clearMediaCollection('cover_image');

                // Add the image to the media collection
                $blog->addMedia($imagePath)
                    ->preservingOriginal()
                    ->toMediaCollection('cover_image');
            }
        }
    }
}
