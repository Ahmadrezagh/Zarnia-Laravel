<?php

namespace App\Http\Controllers;

use App\Http\Resources\SnappProductResource;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\Shipping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SnappController extends Controller
{
    /**
     * Get products for Snapp API (one item per available etiket)
     */
    public function getProducts(Request $request): JsonResponse
    {
        $page = max(1, (int) ($request->input('page', 1)));
        $perPage = min(100, max(1, (int) ($request->input('per_page', 50))));

        $shipping = Shipping::first();
        $shippingCost = $shipping ? $shipping->price : null;
        $deliveryTime = $shipping && $shipping->times()->exists()
            ? $shipping->times()->first()->title ?? null
            : null;

        $request->merge([
            'shipping' => [
                'cost' => $shippingCost,
                'time' => $deliveryTime,
            ],
        ]);

        $productsQuery = Product::query()
            ->main()
            ->hasCountAndImage()
            ->with(['categories', 'etikets', 'children.etikets']);

        $total = $productsQuery->count();

        $products = $productsQuery
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $etiketIds = $products->flatMap(function ($product) {
            $etikets = $product->etikets->where('is_mojood', 1);

            if ($product->children && $product->children->isNotEmpty()) {
                $childrenEtikets = $product->children->flatMap(function ($child) {
                    return $child->etikets->where('is_mojood', 1);
                });
                $etikets = $etikets->merge($childrenEtikets);
            }

            return $etikets->pluck('id');
        })->unique()->values();

        $attributeValuesByEtiket = AttributeValue::whereIn('etiket_id', $etiketIds)
            ->with('attribute')
            ->get()
            ->groupBy('etiket_id');

        $items = $products->flatMap(function ($product) use ($request, $attributeValuesByEtiket) {
            $etikets = $product->etikets->where('is_mojood', 1)->values();

            if ($product->children && $product->children->isNotEmpty()) {
                $childrenEtikets = $product->children->flatMap(function ($child) {
                    return $child->etikets->where('is_mojood', 1)->values();
                });
                $etikets = $etikets->merge($childrenEtikets);
            }

            if ($etikets->isEmpty()) {
                return [];
            }

            return $etikets->map(function ($etiket) use ($product, $request, $attributeValuesByEtiket) {
                $product->snappEtiket = $etiket;
                $product->attributeValues = $attributeValuesByEtiket->get($etiket->id, collect());

                return (new SnappProductResource($product))->toArray($request);
            });
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $perPage),
                ],
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
