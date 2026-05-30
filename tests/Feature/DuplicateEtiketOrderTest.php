<?php

namespace Tests\Feature;

use App\Console\Commands\MarkPendingOrdersAsFailed;
use App\Http\Controllers\Api\V1\ShoppingCartController;
use App\Models\Etiket;
use App\Models\Order;
use App\Services\SMS\Kavehnegar;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Support\EtiketOrderTestCase;

class DuplicateEtiketOrderTest extends EtiketOrderTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(Kavehnegar::class, function ($mock): void {
            $mock->shouldReceive('send_with_two_token')->andReturn(true);
            $mock->shouldReceive('send_with_pattern')->andReturn(true);
        });
    }

    public function test_payment_reservation_sets_database_and_cache(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithEtiket();
        $user = $this->createUser();

        $order = Order::withoutEvents(fn () => $this->createPendingOrder($user, $product, $etiket));
        $order->reserveOrderEtiketsForPayment($user->id);

        $this->assertTrue(Cache::has('reserved_etiket_'.$etiket->code));
        $this->assertSame(0, $this->rawIsMojood($etiket));
        $this->assertSame(0, $etiket->fresh()->is_mojood);
        $this->assertTrue($etiket->fresh()->isAvailableForUser($user->id));
        $this->assertFalse($etiket->fresh()->isAvailableForUser($this->createUser()->id));
    }

    public function test_cart_add_does_not_reserve_etiket(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithEtiket();
        $user = $this->createUser();

        $this->actingAs($user);
        app(ShoppingCartController::class)->plus($etiket->code);

        $this->assertFalse(Cache::has('reserved_etiket_'.$etiket->code));
        $this->assertSame(1, $this->rawIsMojood($etiket));
    }

    public function test_shopping_cart_blocks_other_user_after_payment_reservation(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithEtiket();
        $buyerA = $this->createUser();
        $buyerB = $this->createUser();

        $order = Order::withoutEvents(fn () => $this->createPendingOrder($buyerA, $product, $etiket));
        $order->reserveOrderEtiketsForPayment($buyerA->id);

        $this->actingAs($buyerB);
        app(ShoppingCartController::class)->plus($etiket->code);

        $this->assertDatabaseMissing('shopping_cart_items', [
            'user_id' => $buyerB->id,
            'etiket_id' => $etiket->id,
        ]);
    }

    public function test_reserving_user_can_still_checkout_after_reservation(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithEtiket();
        $buyerA = $this->createUser();

        $order = Order::withoutEvents(fn () => $this->createPendingOrder($buyerA, $product, $etiket));
        $order->reserveOrderEtiketsForPayment($buyerA->id);

        $this->assertTrue($etiket->fresh()->isAvailableForUser($buyerA->id));
    }

    public function test_mark_as_paid_rejects_when_etiket_sold_in_another_order(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithEtiket();
        $buyerA = $this->createUser();
        $buyerB = $this->createUser();

        $orderA = Order::withoutEvents(fn () => $this->createPendingOrder($buyerA, $product, $etiket));
        $orderB = Order::withoutEvents(fn () => $this->createPendingOrder($buyerB, $product, $etiket));

        $orderA->update(['status' => Order::$STATUSES[1]]);
        $orderA->markOrderItemsOutOfStock(false);

        $orderB->markAsPaid();

        $orderB->refresh();
        $this->assertSame(Order::$STATUSES[0], $orderB->status);
    }

    public function test_canceled_pending_order_does_not_restore_etiket_sold_to_another_buyer(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithEtiket();
        $buyerA = $this->createUser();
        $buyerB = $this->createUser();

        $orderA = Order::withoutEvents(fn () => $this->createPendingOrder($buyerA, $product, $etiket));
        $orderB = Order::withoutEvents(fn () => $this->createPendingOrder($buyerB, $product, $etiket));

        $orderA->reserveOrderEtiketsForPayment($buyerA->id);
        $orderB->update(['status' => Order::$STATUSES[1]]);
        $orderB->markOrderItemsOutOfStock(false);
        $this->assertSame(0, $this->rawIsMojood($etiket));

        Order::query()->whereKey($orderA->id)->update([
            'created_at' => Carbon::now()->subMinutes(33),
            'updated_at' => Carbon::now()->subMinutes(33),
        ]);
        Artisan::call(MarkPendingOrdersAsFailed::class);

        $orderA->refresh();
        $this->assertSame(Order::$STATUSES[3], $orderA->status);
        $this->assertSame(0, $this->rawIsMojood($etiket));
    }

    public function test_mark_pending_orders_as_failed_restores_unsold_etiket_after_cancel(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithEtiket();
        $user = $this->createUser();

        $order = Order::withoutEvents(fn () => $this->createPendingOrder($user, $product, $etiket));
        $order->reserveOrderEtiketsForPayment($user->id);

        Order::query()->whereKey($order->id)->update([
            'created_at' => Carbon::now()->subMinutes(33),
            'updated_at' => Carbon::now()->subMinutes(33),
        ]);

        Artisan::call(MarkPendingOrdersAsFailed::class);

        $order->refresh();
        $this->assertSame(Order::$STATUSES[3], $order->status);
        $this->assertFalse(Cache::has('reserved_etiket_'.$etiket->code));
        $this->assertSame(1, $this->rawIsMojood($etiket));
    }

    public function test_reservation_ttl_is_32_minutes(): void
    {
        $this->assertSame(32 * 60, Etiket::PAYMENT_RESERVATION_TTL_SECONDS);
        $this->assertGreaterThan(30 * 60, Etiket::PAYMENT_RESERVATION_TTL_SECONDS);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
