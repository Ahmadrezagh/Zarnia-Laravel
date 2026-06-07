<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Tests\Support\EtiketOrderTestCase;

class OrderSoftDeleteTest extends EtiketOrderTestCase
{
    public function test_order_delete_is_soft_delete(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithEtiket();
        $user = $this->createUser();

        $order = Order::withoutEvents(fn () => $this->createPendingOrder($user, $product, $etiket));

        $order->delete();

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
        $this->assertTrue(Order::withTrashed()->whereKey($order->id)->exists());
        $this->assertFalse(Order::query()->whereKey($order->id)->exists());
    }

    public function test_order_force_delete_never_removes_row_from_database(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithEtiket();
        $user = $this->createUser();

        $order = Order::withoutEvents(fn () => $this->createPendingOrder($user, $product, $etiket));
        $orderId = $order->id;

        $order->delete();
        $trashed = Order::onlyTrashed()->findOrFail($orderId);

        $this->assertFalse($trashed->forceDelete());
        $this->assertTrue(Order::withTrashed()->whereKey($orderId)->exists());
        $this->assertNotNull(Order::withTrashed()->find($orderId)->deleted_at);
    }

    public function test_order_force_delete_on_active_order_soft_deletes_instead(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithEtiket();
        $user = $this->createUser();

        $order = Order::withoutEvents(fn () => $this->createPendingOrder($user, $product, $etiket));

        $this->assertTrue((bool) $order->forceDelete());
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    public function test_bulk_delete_soft_deletes_orders(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithEtiket();
        $user = $this->createUser();

        $orderA = Order::withoutEvents(fn () => $this->createPendingOrder($user, $product, $etiket));
        $orderB = Order::withoutEvents(function () use ($user, $product, $etiket) {
            $order = Order::query()->create([
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
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'etiket' => $etiket->code,
                'name' => $product->name,
                'count' => 1,
                'price' => 100000,
            ]);

            return $order;
        });

        Order::query()->whereIn('id', [$orderA->id, $orderB->id])->delete();

        $this->assertSoftDeleted('orders', ['id' => $orderA->id]);
        $this->assertSoftDeleted('orders', ['id' => $orderB->id]);
        $this->assertSame(2, Order::onlyTrashed()->whereIn('id', [$orderA->id, $orderB->id])->count());
    }
}
