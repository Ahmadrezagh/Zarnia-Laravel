<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\ShoppingCartController;
use App\Models\Order;
use App\Services\SMS\Kavehnegar;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Support\EtiketOrderTestCase;

class OrderableSPrefixedEtiketTest extends EtiketOrderTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(Kavehnegar::class, function ($mock): void {
            $mock->shouldReceive('send_with_two_token')->andReturn(true);
            $mock->shouldReceive('send_with_pattern')->andReturn(true);
        });
    }

    public function test_s_prefixed_code_is_marked_non_reservable(): void
    {
        ['etiket' => $etiket] = $this->createProductWithSPrefixedEtiket();

        $this->assertTrue(Order::isNonReservableEtiketCode($etiket->code));
        $this->assertTrue($etiket->isAlwaysOrderableCode());
        $this->assertFalse(Order::isNonReservableEtiketCode('7001'));
    }

    public function test_payment_reservation_skips_s_prefixed_etiket(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithSPrefixedEtiket(['is_mojood' => 1]);
        $user = $this->createUser();

        $order = Order::withoutEvents(fn () => $this->createPendingOrder($user, $product, $etiket));
        $order->reserveOrderEtiketsForPayment($user->id);

        $this->assertFalse(Cache::has('reserved_etiket_'.$etiket->code));
        $this->assertSame(1, $this->rawIsMojood($etiket));
        $this->assertFalse($etiket->fresh()->isReserved());
    }

    public function test_s_prefixed_etiket_is_available_for_any_user_even_when_is_mojood_zero(): void
    {
        ['etiket' => $etiket] = $this->createProductWithSPrefixedEtiket(['is_mojood' => 0]);
        $userA = $this->createUser();
        $userB = $this->createUser();

        $this->assertTrue($etiket->fresh()->isAvailableForUser($userA->id));
        $this->assertTrue($etiket->fresh()->isAvailableForUser($userB->id));
        $this->assertTrue($etiket->fresh()->isAvailableForUser(null));
    }

    public function test_s_prefixed_etiket_ignores_payment_reservation_cache(): void
    {
        ['etiket' => $etiket] = $this->createProductWithSPrefixedEtiket(['is_mojood' => 1]);
        $otherUser = $this->createUser();

        Cache::put('reserved_etiket_'.$etiket->code, $otherUser->id, 1920);

        $this->assertFalse($etiket->fresh()->isReserved());
        $this->assertTrue($etiket->fresh()->isAvailableForUser($this->createUser()->id));
    }

    public function test_second_user_can_add_s_prefixed_etiket_to_cart_while_another_has_pending_order(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithSPrefixedEtiket(['is_mojood' => 1]);
        $buyerA = $this->createUser();
        $buyerB = $this->createUser();

        Order::withoutEvents(fn () => $this->createPendingOrder($buyerA, $product, $etiket));

        $this->actingAs($buyerB);
        app(ShoppingCartController::class)->plus($etiket->code);

        $this->assertDatabaseHas('shopping_cart_items', [
            'user_id' => $buyerB->id,
            'etiket_id' => $etiket->id,
        ]);
    }

    public function test_multiple_users_can_have_pending_orders_for_same_s_prefixed_etiket(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithSPrefixedEtiket(['is_mojood' => 1]);
        $buyerA = $this->createUser();
        $buyerB = $this->createUser();

        $orderA = Order::withoutEvents(fn () => $this->createPendingOrder($buyerA, $product, $etiket));
        $orderB = Order::withoutEvents(fn () => $this->createPendingOrder($buyerB, $product, $etiket));

        $orderA->reserveOrderEtiketsForPayment($buyerA->id);
        $orderB->reserveOrderEtiketsForPayment($buyerB->id);

        $this->assertTrue($etiket->fresh()->isAvailableForUser($buyerA->id));
        $this->assertTrue($etiket->fresh()->isAvailableForUser($buyerB->id));
        $this->assertFalse(Cache::has('reserved_etiket_'.$etiket->code));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
