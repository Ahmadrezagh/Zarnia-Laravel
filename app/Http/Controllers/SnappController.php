<?php

namespace App\Http\Controllers;

use App\Http\Resources\SnappProductResource;
use App\Models\AttributeValue;
use App\Models\Etiket;
use App\Models\Product;
use App\Models\Shipping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SnappController extends Controller
{
    /**
     * Get products for Snapp API
     */
    public function getProducts(Request $request): JsonResponse
    {
        $token = $request->header('X-API-Token') ?? $request->input('token');
        $expectedToken = config('services.snapp.api_token', env('SNAPP_API_TOKEN'));

        if ($expectedToken && $token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid API token.',
            ], 401);
        }

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
            ->with(['categories', 'children', 'etikets', 'children.etikets']);

        $total = $productsQuery->count();

        $products = $productsQuery
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $etiketIds = Etiket::whereIn('product_id', $products->pluck('id'))
            ->orderBy('id')
            ->get(['id', 'product_id'])
            ->groupBy('product_id')
            ->map(fn ($group) => $group->first()->id);

        $attributeValues = AttributeValue::whereIn('etiket_id', $etiketIds->values())
            ->with('attribute')
            ->get()
            ->groupBy(function ($attributeValue) use ($etiketIds) {
                return $etiketIds->search($attributeValue->etiket_id);
            });

        $products->each(function ($product) use ($attributeValues) {
            $product->attributeValues = $attributeValues->get($product->id, collect());
        });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => SnappProductResource::collection($products)->resolve($request),
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
