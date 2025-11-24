<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $baseUrl = rtrim(setting('url') ?? config('app.url'), '/');
        $urls = [];

        // Add home page
        $urls[] = [
            'loc' => $baseUrl,
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0'
        ];

        // Add products (only main products, not children)
        $products = Product::query()
            ->whereNull('parent_id')
            ->whereNotNull('slug')
            ->get();

        foreach ($products as $product) {
            $urls[] = [
                'loc' => $baseUrl . '/products/' . $product->slug,
                'lastmod' => $product->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ];
        }

        // Add categories
        $categories = Category::query()
            ->whereNotNull('slug')
            ->get();

        foreach ($categories as $category) {
            $urls[] = [
                'loc' => $baseUrl . '/categories/' . $category->slug,
                'lastmod' => $category->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7'
            ];
        }

        // Add blog posts
        $blogs = Blog::query()
            ->whereNotNull('slug')
            ->get();

        foreach ($blogs as $blog) {
            $urls[] = [
                'loc' => $baseUrl . '/blogs/' . $blog->slug,
                'lastmod' => $blog->updated_at->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.6'
            ];
        }

        // Add pages
        $pages = Page::query()
            ->whereNotNull('slug')
            ->where('published', true)
            ->get();

        foreach ($pages as $page) {
            $urls[] = [
                'loc' => $baseUrl . '/pages/' . $page->slug,
                'lastmod' => $page->updated_at->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5'
            ];
        }

        // Generate XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . '</loc>' . "\n";
            $xml .= '    <lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1, 'UTF-8') . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . htmlspecialchars($url['changefreq'], ENT_XML1, 'UTF-8') . '</changefreq>' . "\n";
            $xml .= '    <priority>' . htmlspecialchars($url['priority'], ENT_XML1, 'UTF-8') . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}

