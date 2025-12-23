<?php

namespace App\Http\Resources\Admin\Table;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class AdminProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Cache view_count per product, expiring at the end of the current day (23:59:59)
        $view_count = Cache::remember(
            "product_view_count_{$this->id}",
            Carbon::now()->endOfDay(), // Expires at midnight (next day 00:00:00)
            function () {
                return $this->ViewCount; // Assuming ViewCount is an attribute or accessor on the model
            }
        );
        return [
            'id' => $this->id,
            'nameUrl' => $this->nameUrl,
            'slug' => $this->slug,
            'image' => $this->image,
            'weight' => $this->weight,
            'ojrat' => $this->ojrat,
            'darsad_kharid' => $this->darsad_kharid,
            'price' => number_format($this->price)." تومان",
            'categories_title' => $this->categories_title,
            'categories_title_truncated' => $this->getTruncatedCategoriesTitle(30),
            'discount_percentage' => $this->discount_percentage,
            'count' => $this->count,
            'etiketsCodeAsArray' => [],
            'parent_id' => $this->parent_id,
            'view_count' => $view_count,
            'visits' => $this->visits ?? 0,
            'frontend_url' => $this->frontend_url
        ];
    }
    protected function getTruncatedCategoriesTitle(int $limit = 30): string
    {
        $title = $this->categories_title ?? '';
        $plain = strip_tags($title);

        if (mb_strlen($plain, 'UTF-8') <= $limit) {
            return $plain;
        }

        return mb_substr($plain, 0, $limit, 'UTF-8') . '...';
    }
}
