@extends('layouts.panel')
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'کد های تخفیف'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'کد های تخفیف']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن کد تخفیف</button>
            <x-modal.create id="modal-create" title="ساخت کد تخفیف" action="{{route('discounts.store')}}" >
                <x-form.input title="کد تخفیف"  name="code" />
                <x-form.input title="درصد تخفیف"  name="percentage" />
                <x-form.input title="مبلغ تخفیف"  name="amount" />
                <x-form.input title="حداقل مبلغ خرید برای اعمال تخفیف"  name="min_price" />
                <x-form.input title="حداکثر مبلغ خرید برای اعمال تخفیف"  name="max_price" />
                <x-form.input title="تعداد دفعات اعمال تخفیف"  name="quantity" />
                <x-form.input title="تعداد دفعات اعمال تخفیف به ازای هر کاربر"  name="quantity_per_user" />
                <x-form.input title="تاریخ شروع کد تخفیف"  name="start_at" type="datetime-local" />
                <x-form.input title="تاریخ پایان کد تخفیف"  name="expires_at" type="datetime-local" />
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.discounts')"
            id="discounts-table"
            :columns="[
                            ['label' => 'کد تخفیف', 'key' => 'code', 'type' => 'text'],
                        ]"
            :items="$discounts"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($discounts as $discount)

                <x-modal.destroy id="modal-destroy-{{$discount->id}}" title="حذف کد تخفیف" action="{{route('discounts.destroy', $discount->id)}}" title="{{$discount->title}}" />

                <x-modal.update id="modal-edit-{{$discount->id}}" title="ویرایش کد تخفیف" action="{{route('discounts.update',$discount->id)}}" >
                    <x-form.input title="کد تخفیف"  name="code" :value="$discount->code" />
                    <x-form.input title="درصد تخفیف"  name="percentage" :value="$discount->percentage" />
                    <x-form.input title="مبلغ تخفیف"  name="amount" :value="$discount->amount"  />
                    <x-form.input title="حداقل مبلغ خرید برای اعمال تخفیف"  name="min_price" :value="$discount->min_price" />
                    <x-form.input title="حداکثر مبلغ خرید برای اعمال تخفیف"  name="max_price" :value="$discount->max_price"  />
                    <x-form.input title="تعداد دفعات اعمال تخفیف"  name="quantity"  :value="$discount->quantity" />
                    <x-form.input title="تعداد دفعات اعمال تخفیف به ازای هر کاربر"  name="quantity_per_user"  :value="$discount->quantity_per_user" />
                    <x-form.input title="تاریخ شروع کد تخفیف"  name="start_at"  :value="$discount->StartAtYMD" type="datetime-local"  />
                    <x-form.input title="تاریخ پایان کد تخفیف"  name="expires_at"  :value="$discount->ExpiresAtYMD" type="datetime-local" />
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

@endsection
