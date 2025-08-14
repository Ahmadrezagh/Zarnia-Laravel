<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\AdminUpdateOrderRequest;
use App\Models\Attribute;
use App\Models\Order;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::query()->latest()->paginate();
        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AdminUpdateOrderRequest $request, Order $order)
    {
        $order->update($request->validated());
        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->delete();
        return response()->json();
    }


    public function table()
    {
        $orders = Order::query()->latest()->paginate();

        // Initialize slot content
        $slotContent = '';

        // Loop through users and render the Blade string for each
        foreach ($orders as $order) {
            $slotContent .= Blade::render(
                <<<'BLADE'
                 <x-modal.destroy id="modal-destroy-{{$order->id}}" title="حذف سفارش" action="{{route('orders.destroy', $order->id)}}" title="{{$order->name}}" />
                <!-- Modal -->
                <x-modal.update id="modal-edit-{{$order->id}}" title="ویرایش سفارش" action="{{route('orders.update', $order->id)}}" >
                    <input type="hidden" name="id" value="{{$order->id}}">
                    <x-form.input title="شماره سفارش" :value="$order->id" name="none" />
                    <x-form.input title="نام سفارش" :value="$order->userName" name="none" />
                    <x-form.input title="شیوه ارسال" :value="$order->shippingName" name="none" />
                    <x-form.input title="بازه زمانی ارسال" :value="$order->shippingTimeName" name="none" />
                    <x-form.input title="درگاه پرداخت" :value="$order->gatewayName" name="none" />
                    <x-form.input title="زمان پرداخت" :value="$order->paid_at" name="none" />
                    <x-form.input title="کد تخفیف" :value="$order->discount_code" name="none" />
                    <x-form.input title="درصد تخفیف" :value="$order->discount_percentage" name="none" />
                    <x-form.input title="مبلغ تخفیف" :value="$order->discount_price" name="none" />
                    <x-form.input title="مبلغ" :value="$order->total_amount" name="none" />
                    <x-form.input title="مبلغ قابل پرداخت" :value="$order->final_amount" name="none" />
                    <x-form.select-option name="status" title="وضعیت" >
                        @foreach(\App\Models\Order::$PERSIAN_STATUSES as $statusEn => $statusFa)
                            <option value="{{$statusEn}}" @if($statusEn == $order->status) selected @endif >{{$statusFa}}</option>
                        @endforeach
                    </x-form.select-option>
                </x-modal.update>
                <!-- /Modal -->
            BLADE,
                ['attribute' => $order]
            );
        }

        return view('components.table', [
            'id' => 'attributes-table',
            'columns' => [
                ['label' => 'شماره سفارش', 'key' => 'id', 'type' => 'text'],
                ['label' => 'نام مشتری', 'key' => 'userName', 'type' => 'text'],
                ['label' => 'شیوه ارسال', 'key' => 'shippingName', 'type' => 'text'],
                ['label' => 'بازه زمانی ارسال', 'key' => 'shippingTimeName', 'type' => 'text'],
                ['label' => 'درگاه پرداخت', 'key' => 'gatewayName', 'type' => 'text'],
                ['label' => 'زمان پرداخت', 'key' => 'paid_at', 'type' => 'text'],
                ['label' => 'کد تخفیف', 'key' => 'discount_code', 'type' => 'text'],
                ['label' => 'درصد تخفیف', 'key' => 'discount_percentage', 'type' => 'text'],
                ['label' => 'مبلغ تخفیف', 'key' => 'discount_price', 'type' => 'text'],
                ['label' => 'مبلغ', 'key' => 'total_amount', 'type' => 'text'],
                ['label' => 'مبلغ قابل پرداخت', 'key' => 'final_amount', 'type' => 'text'],
                ['label' => 'وضعیت', 'key' => 'persianStatus', 'type' => 'text'],
            ],
            'url' => route('table.attributes'),
            'items' => $orders,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
