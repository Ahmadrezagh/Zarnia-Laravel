<?php

namespace App\Observers;

use App\Models\Gateway;
use App\Models\Order;
use App\Services\SMS\Kavehnegar;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $gateway = Gateway::find($order->gateway_id);
        $gateway->createTransaction($order);
        $sms = new Kavehnegar();
        $sms->send_with_two_token($order->address->receiver_phone,$order->address->receiver_name,$order->id,$order->status);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
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
