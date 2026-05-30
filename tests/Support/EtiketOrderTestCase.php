<?php

namespace Tests\Support;

use App\Models\Etiket;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class EtiketOrderTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createProductWithEtiket(array $etiketAttributes = []): array
    {
        $product = Product::query()->create([
            'name' => 'Test Product',
            'slug' => 'test-product-'.uniqid(),
            'weight' => 5.0,
            'price' => 1000000,
        ]);

        $etiket = Etiket::query()->create(array_merge([
            'code' => '7001',
            'type' => 'real',
            'weight' => 5.0,
            'price' => 1000000,
            'product_id' => $product->id,
            'is_mojood' => 1,
            'ojrat' => 10,
        ], $etiketAttributes));

        return compact('product', 'etiket');
    }

    protected function createProductWithSPrefixedEtiket(array $etiketAttributes = []): array
    {
        return $this->createProductWithEtiket(array_merge([
            'code' => 's-7001',
            'type' => 'real',
            'is_mojood' => 0,
        ], $etiketAttributes));
    }

    protected function createPendingOrder(User $user, Product $product, Etiket $etiket, array $orderAttributes = []): Order
    {
        $order = Order::query()->create(array_merge([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'address_id' => null,
            'shipping_id' => null,
            'status' => Order::$STATUSES[0],
            'discount_code' => '',
            'discount_price' => 0,
            'discount_percentage' => 0,
            'total_amount' => 100000,
            'final_amount' => 100000,
            'has_comprehensive_etiket' => false,
        ], $orderAttributes));

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'etiket' => $etiket->code,
            'name' => $product->name,
            'count' => 1,
            'price' => 100000,
        ]);

        return $order->fresh(['orderItems']);
    }

    protected function reserveEtiketForUser(Etiket $etiket, User $user, int $ttlSeconds = 1920): void
    {
        Cache::put('reserved_etiket_'.$etiket->code, $user->id, $ttlSeconds);
    }

    protected function rawIsMojood(Etiket $etiket): int
    {
        return (int) Etiket::query()->whereKey($etiket->id)->value('is_mojood');
    }
}
