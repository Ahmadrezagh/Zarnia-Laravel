@extends('layouts.panel')
@section('content')
    <x-breadcrumb :title="'سفارش ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'سفارشات']
      ]" />

    <x-page>
        <x-slot name="header">
            <h5>سفارشات</h5>
{{--            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن سفارش</button>--}}
{{--            <x-modal.create id="modal-create" title="ساخت سفارش" action="{{route('orders.store')}}" >--}}
{{--                <x-form.input title="نام"  name="name" />--}}
{{--                <x-form.input title="جمله پیشوند"  name="prefix_sentence" />--}}
{{--                <x-form.input title="جمله پسوند"  name="postfix_sentence" />--}}
{{--            </x-modal.create>--}}
        </x-slot>

        <x-table
            :url="route('table.orders')"
            id="orders-table"
            :columns="[
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
                        ]"
            :items="$orders"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >

            @foreach($orders as $order)
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
            @endforeach
        </x-table>
    </x-page>
@endsection
