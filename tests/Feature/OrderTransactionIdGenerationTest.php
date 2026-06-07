<?php

namespace Tests\Feature;

use App\Models\Order;
use Tests\Support\EtiketOrderTestCase;

class OrderTransactionIdGenerationTest extends EtiketOrderTestCase
{
    public function test_first_transaction_id_starts_at_minimum(): void
    {
        $this->assertSame(
            (string) Order::TRANSACTION_ID_START,
            Order::generateUniqueTransactionId()
        );
    }

    public function test_transaction_id_is_last_numeric_plus_one(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithEtiket();
        $user = $this->createUser();

        $first = Order::withoutEvents(function () use ($user, $product, $etiket) {
            $order = $this->createPendingOrder($user, $product, $etiket);
            $order->update(['transaction_id' => '1000000100']);

            return $order->transaction_id;
        });

        $second = Order::generateUniqueTransactionId();

        $this->assertSame('1000000100', $first);
        $this->assertSame('1000000101', $second);
    }

    public function test_soft_deleted_orders_transaction_ids_are_not_reused(): void
    {
        ['product' => $product, 'etiket' => $etiket] = $this->createProductWithEtiket();
        $user = $this->createUser();

        $order = Order::withoutEvents(function () use ($user, $product, $etiket) {
            $order = $this->createPendingOrder($user, $product, $etiket);
            $order->update(['transaction_id' => '1000000200']);

            return $order;
        });

        $order->delete();

        $this->assertSame('1000000201', Order::generateUniqueTransactionId());
    }

    public function test_generated_transaction_ids_are_unique_in_sequence(): void
    {
        $ids = [
            Order::generateUniqueTransactionId(),
            Order::generateUniqueTransactionId(),
            Order::generateUniqueTransactionId(),
        ];

        $this->assertSame(['1000000000', '1000000001', '1000000002'], $ids);
        $this->assertCount(3, array_unique($ids));
    }
}
