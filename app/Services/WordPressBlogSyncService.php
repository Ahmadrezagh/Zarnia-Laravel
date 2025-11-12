<?php

namespace App\Services;

use App\Models\Blog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WordPressBlogSyncService
{
    protected string $apiUrl;
    protected int $perPage;

    public function __construct(string $apiUrl = 'https://delfigoldgallery.ir/wp-posts-api.php', int $perPage = 100)
    {
        $this->apiUrl = $apiUrl;
        $this->perPage = $perPage;
    }

    /**
     * Sync all blog posts from WordPress API
     *
     * @param bool $updateExisting Whether to update existing posts
     * @return array Statistics about the sync process
     */
    public function syncAll(bool $updateExisting = true): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $page = 1;
        $lastPage = null;

        do {
            try {
                $response = $this->fetchPage($page);
                
                if (!$response || !isset($response['success']) || !$response['success']) {
                    Log::warning("WordPress API: Failed to fetch page {$page}", ['response' => $response]);
                    $stats['errors']++;
                    break;
                }

                // Set last page from meta if not set
                if ($lastPage === null && isset($response['meta']['last_page'])) {
                    $lastPage = $response['meta']['last_page'];
                }

                // Process each blog post
                if (isset($response['data']) && is_array($response['data'])) {
                    foreach ($response['data'] as $postData) {
                        try {
                            $result = $this->syncPost($postData, $updateExisting);
                            $stats[$result]++;
                        } catch (\Exception $e) {
                            Log::error("WordPress API: Error syncing post ID {$postData['id']}", [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                            $stats['errors']++;
                        }
                    }
                }

                $page++;
            } catch (\Exception $e) {
                Log::error("WordPress API: Error fetching page {$page}", [
                    'error' => $e->getMessage(),
                ]);
                $stats['errors']++;
                break;
            }
        } while ($lastPage === null || $page <= $lastPage);

        return $stats;
    }

    /**
     * Fetch a single page from the WordPress API
     *
     * @param int $page
     * @return array|null
     */
    protected function fetchPage(int $page): ?array
    {
        try {
            $response = Http::timeout(30)->get($this->apiUrl, [
                'page' => $page,
                'per_page' => $this->perPage,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("WordPress API: HTTP error for page {$page}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("WordPress API: Exception fetching page {$page}", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sync a single blog post
     *
     * @param array $postData
     * @param bool $updateExisting
     * @return string 'created', 'updated', or 'skipped'
     */
    protected function syncPost(array $postData, bool $updateExisting): string
    {
        $wpPostId = $postData['id'] ?? null;
        
        if (!$wpPostId) {
            throw new \Exception('Post ID is missing');
        }

        // Decode URL-encoded slug
        $slug = urldecode($postData['slug'] ?? '');
        
        // If slug is empty or still URL-encoded, generate from title
        if (empty($slug) || $slug === $postData['slug']) {
            $slug = Str::slug($postData['title'] ?? '');
        }
        
        // Truncate slug to 255 characters (database limit)
        $slug = mb_substr($slug, 0, 255);

        // Find existing blog by WordPress post ID (if column exists)
        $blog = null;
        $hasWpPostIdColumn = Schema::hasColumn('blogs', 'wp_post_id');
        
        if ($hasWpPostIdColumn) {
            $blog = Blog::where('wp_post_id', $wpPostId)->first();
        } else {
            Log::warning("WordPress API: wp_post_id column not found. Please run migration: php artisan migrate");
        }

        // If not found by wp_post_id, try to find by slug
        if (!$blog && !empty($slug)) {
            $blog = Blog::where('slug', $slug)->first();
        }

        // Prepare blog data - replace delfigoldgallery.ir with zarniagoldgallery.ir in all fields
        // Also truncate fields to fit database constraints
        $blogData = [
            'title' => $this->truncateString($this->replaceDomain($postData['title'] ?? ''), 255),
            'slug' => $slug,
            'description' => $this->replaceDomain($postData['description'] ?? ''),
            'meta_title' => $this->truncateString($this->cleanMetaTitle($this->replaceDomain($postData['meta_title'] ?? '')), 255),
            'meta_description' => $this->replaceDomain($postData['meta_description'] ?? ''),
            'meta_keywords' => $this->replaceDomain($postData['meta_keywords'] ?? ''),
            'canonical_url' => $this->truncateString($this->replaceDomain($postData['canonical_url'] ?? ''), 255),
        ];
        
        // Only add wp_post_id if column exists
        if ($hasWpPostIdColumn) {
            $blogData['wp_post_id'] = $wpPostId;
        }

        // Handle created_at and updated_at if provided
        if (isset($postData['created_at'])) {
            try {
                $blogData['created_at'] = \Carbon\Carbon::parse($postData['created_at']);
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        if (isset($postData['updated_at'])) {
            try {
                $blogData['updated_at'] = \Carbon\Carbon::parse($postData['updated_at']);
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        if ($blog) {
            if (!$updateExisting) {
                return 'skipped';
            }
            
            try {
                $blog->update($blogData);
                $result = 'updated';
            } catch (\Illuminate\Database\QueryException $e) {
                Log::error("WordPress API: SQL error updating blog post ID {$wpPostId}", [
                    'error' => $e->getMessage(),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'blog_data' => $blogData,
                ]);
                throw $e;
            }
        } else {
            try {
                $blog = Blog::create($blogData);
                $result = 'created';
            } catch (\Illuminate\Database\QueryException $e) {
                Log::error("WordPress API: SQL error creating blog post ID {$wpPostId}", [
                    'error' => $e->getMessage(),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'blog_data' => $blogData,
                ]);
                throw $e;
            }
        }

        // Handle cover image - try new domain first, fallback to old domain
        if (!empty($postData['cover_image'])) {
            try {
                $originalUrl = $postData['cover_image'];
                $newDomainUrl = $this->replaceDomain($originalUrl);
                
                // Try new domain first, fallback to original if it fails
                try {
                    $this->syncCoverImage($blog, $newDomainUrl);
                } catch (\Exception $e) {
                    // If new domain fails, try original domain
                    Log::info("WordPress API: Trying original domain for image", [
                        'new_url' => $newDomainUrl,
                        'original_url' => $originalUrl,
                    ]);
                    $this->syncCoverImage($blog, $originalUrl);
                }
            } catch (\Exception $e) {
                Log::warning("WordPress API: Failed to sync cover image for blog ID {$blog->id}", [
                    'image_url' => $postData['cover_image'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * Download and sync cover image from URL
     *
     * @param Blog $blog
     * @param string $imageUrl
     * @return void
     */
    protected function syncCoverImage(Blog $blog, string $imageUrl): void
    {
        try {
            // Download image
            $response = Http::timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                throw new \Exception("Failed to download image: HTTP {$response->status()}");
            }

            // Get file extension from URL or Content-Type
            $extension = $this->getImageExtension($imageUrl, $response->header('Content-Type'));
            
            // Generate unique filename
            $filename = 'wp_' . $blog->wp_post_id . '_' . time() . '.' . $extension;
            $tempPath = storage_path('app/temp/' . $filename);

            // Ensure temp directory exists
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            // Save to temp file
            file_put_contents($tempPath, $response->body());

            // Clear existing cover image
            $blog->clearMediaCollection('cover_image');

            // Add to media library
            $blog->addMedia($tempPath)
                ->preservingOriginal()
                ->toMediaCollection('cover_image');

            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        } catch (\Exception $e) {
            // Clean up temp file on error
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            throw $e;
        }
    }

    /**
     * Get image extension from URL or Content-Type
     *
     * @param string $url
     * @param string|null $contentType
     * @return string
     */
    protected function getImageExtension(string $url, ?string $contentType = null): string
    {
        // Try to get extension from URL
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if (!empty($extension)) {
                return strtolower($extension);
            }
        }

        // Try to get from Content-Type
        if ($contentType) {
            $mimeToExt = [
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];
            if (isset($mimeToExt[$contentType])) {
                return $mimeToExt[$contentType];
            }
        }

        // Default to jpg
        return 'jpg';
    }

    /**
     * Truncate string to specified length (handles multibyte characters)
     *
     * @param string $text
     * @param int $length
     * @return string
     */
    protected function truncateString(string $text, int $length): string
    {
        if (empty($text)) {
            return $text;
        }
        
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }
        
        return mb_substr($text, 0, $length, 'UTF-8');
    }

    /**
     * Replace delfigoldgallery.ir with zarniagoldgallery.ir in URLs and text
     *
     * @param string $text
     * @return string
     */
    protected function replaceDomain(string $text): string
    {
        if (empty($text)) {
            return $text;
        }
        
        // Replace delfigoldgallery.ir with zarniagoldgallery.ir
        // Handle both http and https, with and without www
        $text = str_replace('https://delfigoldgallery.ir', 'https://zarniagoldgallery.ir', $text);
        $text = str_replace('http://delfigoldgallery.ir', 'http://zarniagoldgallery.ir', $text);
        $text = str_replace('https://www.delfigoldgallery.ir', 'https://www.zarniagoldgallery.ir', $text);
        $text = str_replace('http://www.delfigoldgallery.ir', 'http://www.zarniagoldgallery.ir', $text);
        $text = str_replace('delfigoldgallery.ir', 'zarniagoldgallery.ir', $text);
        
        return $text;
    }

    /**
     * Clean meta title by removing WordPress template tags
     *
     * @param string $metaTitle
     * @return string
     */
    protected function cleanMetaTitle(string $metaTitle): string
    {
        // Remove WordPress template tags like %%title%%, %%page%%, %%sep%%, %%sitename%%
        $metaTitle = preg_replace('/%%[^%]+%%/', '', $metaTitle);
        $metaTitle = trim($metaTitle);
        
        // Remove extra spaces and dashes
        $metaTitle = preg_replace('/\s*-\s*/', ' - ', $metaTitle);
        $metaTitle = preg_replace('/\s+/', ' ', $metaTitle);
        
        return trim($metaTitle);
    }
}

