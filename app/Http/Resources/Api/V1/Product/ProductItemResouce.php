<?php

namespace App\Http\Resources\Api\V1\Product;

use App\Http\Resources\Api\V1\Categories\CategoryResource;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class ProductItemResouce extends JsonResource
{
    protected $user;
    public function __construct($resource,$user = null)
    {
        parent::__construct($resource);

        $this->user = $user;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $is_favorite = false;
        $sampleProducts = new ProductListCollection(
            Product::query()->inRandomOrder()->take(15)->get(),
            $this->user
        );
        if($this->user){
            $is_favorite = Favorite::query()->where([
                'user_id' => $this->user->id,
                'product_id' => $this->id
            ])->exists();
        }
        $product = Product::query()
            ->with([
                // Load all etikets; filter below (real: is_mojood only; comprehensive: all related must be mojood).
                'etikets.attributeValues.attribute',
                'etikets.comprehensiveEtikets.relatedEtiket',
                'children.etikets.attributeValues.attribute',
                'children.etikets.comprehensiveEtikets.relatedEtiket',
            ])
            ->find($this->id);
        $coverImage = $product->getFirstMedia('cover_image');
        $galleryImages = $product->getMedia('gallery');
        $galleryUrls = $galleryImages->map(function ($media) {
            return [
                'xlarge' => $media->getUrl('xlarge'),
                'large' => $media->getUrl('large'),
                'medium' => $media->getUrl('medium'),
                'small' => $media->getUrl('small'),
            ];
        })->toArray();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'weight' => $this->minimum_available_weight,
            // Strip HTML tags from description for API output
            'description' => $this->description ? trim(strip_tags($this->description)) : null,
            'image' => $this->image,
            'cover_image' => $this->CoverImageResponsive,
            'gallery_images' => $galleryUrls,
            'gallery' => array_merge(
                $this->getMedia('gallery')->map(function ($media, $index) {
                    $url = $media->getUrl();
                    return $url;
                })->toArray(),
                $this->getGalleryEndImages()
            ),
            'slug' => $this->slug,
            'price' => number_format($this->minimum_available_price),
            'price_without_discount' => number_format($this->price_without_discount_minimum_available_product  ?? 0),
            'price_range_title' => $this->price_range_title,
            'minimum_available_price' => $this->minimum_available_price,
            'minimum_available_weight' => $this->minimum_available_weight,
            'discount_percentage' => $this->discount_percentage,
            'snapp_pay_each_installment' => number_format($this->price/4),
            'children' => new ProductListCollection($this->children, $this->user),
            'categories' => CategoryResource::collection($this->categories),
            'is_favorite' => $is_favorite,
            'purity' => '18',
            'gold_price' => get_gold_price()/10,
            'attribute_values' => $product->etikets->first()?->attributeValues
                ->map(fn($av) => [
                    'attribute_id'   => $av->attribute_id,
                    'attribute_name' => $av->attribute?->name,
                    'value'          => trim(($av->attribute?->prefix_sentence ?? '') . ' ' . $av->value . ' ' . ($av->attribute?->postfix_sentence ?? '')),
                ]) ?? collect(),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'canonical_url' => $this->canonical_url,
            'weights' => collect([$product])
                ->merge($product?->children ?? collect())
                ->map(function ($productItem) {
                    $etiketsQuery = $productItem->etikets()->with([
                        'attributeValues.attribute',
                        'comprehensiveEtikets.relatedEtiket',
                    ]);
                    $allEtikets = $productItem->relationLoaded('etikets')
                        ? $productItem->etikets
                        : $etiketsQuery->get();

                    $availableEtikets = $allEtikets->filter(function ($etiket) {
                        return $this->etiketShouldAppearInWeightsList($etiket);
                    });

                    if ($availableEtikets->isEmpty()) {
                        return null;
                    }

                    return [
                        'id' => $productItem->id,
                        'weight' => $productItem->weight,
                        'name' => $productItem->name,
                        'slug' => $productItem->slug,
                        'price' => number_format($productItem->price),
                        'etikets' => $availableEtikets->map(function ($etiket) {
                            $isReserved = $etiket->isReserved();
                            $reservedByUserId = $isReserved ? Cache::get('reserved_etiket_' . $etiket->code) : null;
                            $reservedForCurrentUser = $reservedByUserId === $this->user?->id || $reservedByUserId === true;
                            // Real: stock flag only. Comprehensive: already filtered so all related are mojood.
                            $baseAvailable = ($etiket->type ?? '') === 'comprehensive'
                                ? true
                                : (int) $etiket->getRawOriginal('is_mojood') === 1;
                            $available = $baseAvailable && (!$isReserved || $reservedForCurrentUser);

                            return [
                                'id'                            => $etiket->id,
                                'code'                          => $etiket->code,
                                'weight'                        => $etiket->weight,
                                'price'                         => $etiket->price / 10,
                                'original_price'                => $etiket->original_price / 10,
                                'orderable_after_out_of_stock'  => $etiket->orderable_after_out_of_stock ?? false,
                                'is_reserved'                   => $isReserved,
                                'available'                     => $available,
                                'attribute_values'              => $etiket->relationLoaded('attributeValues')
                                    ? $etiket->attributeValues->map(fn($av) => [
                                        'attribute_id'   => $av->attribute_id,
                                        'attribute_name' => $av->attribute?->name,
                                        'value'          => trim(($av->attribute?->prefix_sentence ?? '') . ' ' . $av->value . ' ' . ($av->attribute?->postfix_sentence ?? '')),
                                    ])
                                    : [],
                            ];
                        })->values(),
                    ];
                })
                ->filter()
                ->sortBy('weight')
                ->values(),
               

        ];
    }

    /**
     * Get gallery end images from settings
     *
     * @return array
     */
    /**
     * Real etikets: include only when DB is_mojood === 1.
     * Comprehensive etikets: include only when every related etiket has is_mojood === 1 (otherwise omit from list).
     *
     * @param  \App\Models\Etiket  $etiket
     */
    private function etiketShouldAppearInWeightsList($etiket): bool
    {
        if (($etiket->type ?? '') === 'comprehensive') {
            $links = $etiket->relationLoaded('comprehensiveEtikets')
                ? $etiket->comprehensiveEtikets
                : $etiket->comprehensiveEtikets()->with('relatedEtiket')->get();

            if ($links->isEmpty()) {
                return false;
            }

            return $links->every(function ($link) {
                $related = $link->relatedEtiket;

                return $related && (int) $related->getRawOriginal('is_mojood') === 1;
            });
        }

        return (int) $etiket->getRawOriginal('is_mojood') === 1;
    }

    private function getGalleryEndImages(): array
    {
        $galleryEndImagesJson = Setting::getValue('gallery_end_images');
        
        if (empty($galleryEndImagesJson)) {
            return [];
        }
        
        $galleryEndImages = json_decode($galleryEndImagesJson, true);
        
        if (!is_array($galleryEndImages)) {
            return [];
        }
        
        // Convert image paths to full URLs
        return array_map(function ($imagePath) {
            return asset($imagePath);
        }, $galleryEndImages);
    }
}
