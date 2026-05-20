<?php

namespace App\Observers;

use App\Models\Gateway;
use App\Models\Order;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $gateway = Gateway::find($order->gateway_id);
        $gold_rpice = number_format(get_gold_price() / 10);
        $order->update([
            'gold_price' => $gold_rpice,
        ]);
        //        $gateway->createTransaction($order);

        // پیامک وضعیت خریدار؛ پیامک مدیران و sefareshiproduct بعد از پرداخت/ثبت کامل (markAsPaid یا Admin store).
        if ($order->status !== Order::$STATUSES[1]) {
            $order->sendBuyerOrderStatusTwoTokenSms();
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        if (in_array($order->status, [Order::$STATUSES[3], Order::$STATUSES[4]], true)) {
            $order->restoreOrderEtiketsAvailability();
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
