@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'ساختار هدایا'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'ساختار هدایا']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modal-create">
                <i class="fas fa-gift"></i> افزودن ساختار هدیه
            </button>
            
            <x-modal.create id="modal-create" title="ساخت ساختار هدیه" action="{{route('gift_structures.store')}}" >
                <div class="row">
                    <div class="col-md-6">
                        <x-form.input title="از قیمت (تومان)" name="from_price" type="number" required="true" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input title="تا قیمت (تومان)" name="to_price" type="number" required="true" />
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <x-form.input title="مبلغ تخفیف (تومان)" name="amount" type="number" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input title="درصد تخفیف (%)" name="percentage" type="number" />
                    </div>
                </div>
                
                <x-form.input title="محدودیت زمانی (روز)" name="limit_in_days" type="number" required="true" />
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" checked>
                        فعال
                    </label>
                </div>
                
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    هنگامی که مبلغ سفارش کاربر بین "از قیمت" و "تا قیمت" باشد، یک کد تخفیف یکبار مصرف برای او ایجاد می‌شود.
                </small>
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.gift_structures')"
            id="gift-structures-table"
            :columns="[
                            ['label' => 'از قیمت', 'key' => 'from_price_formatted', 'type' => 'text'],
                            ['label' => 'تا قیمت', 'key' => 'to_price_formatted', 'type' => 'text'],
                            ['label' => 'اطلاعات تخفیف', 'key' => 'discount_info', 'type' => 'text'],
                            ['label' => 'محدودیت زمانی', 'key' => 'limit_in_days', 'type' => 'text'],
                            ['label' => 'وضعیت', 'key' => 'is_active', 'type' => 'text'],
                            ['label' => 'تاریخ ایجاد', 'key' => 'created_at', 'type' => 'text'],
                        ]"
            :items="$giftStructures"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($giftStructures as $giftStructure)

                <x-modal.destroy 
                    id="modal-destroy-{{$giftStructure->id}}" 
                    title="حذف ساختار هدیه" 
                    action="{{route('gift_structures.destroy', $giftStructure->id)}}" 
                    title="ساختار {{$giftStructure->id}}" />

                <x-modal.update 
                    id="modal-edit-{{$giftStructure->id}}" 
                    title="ویرایش ساختار هدیه" 
                    action="{{route('gift_structures.update',$giftStructure->id)}}" >
                    
                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input title="از قیمت (تومان)" name="from_price" type="number" :value="$giftStructure->from_price" required="true" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input title="تا قیمت (تومان)" name="to_price" type="number" :value="$giftStructure->to_price" required="true" />
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input title="مبلغ تخفیف (تومان)" name="amount" type="number" :value="$giftStructure->amount" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input title="درصد تخفیف (%)" name="percentage" type="number" :value="$giftStructure->percentage" />
                        </div>
                    </div>
                    
                    <x-form.input title="محدودیت زمانی (روز)" name="limit_in_days" type="number" :value="$giftStructure->limit_in_days" required="true" />
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1" {{ $giftStructure->is_active ? 'checked' : '' }}>
                            فعال
                        </label>
                    </div>
                    
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        هنگامی که مبلغ سفارش کاربر بین "از قیمت" و "تا قیمت" باشد، یک کد تخفیف یکبار مصرف برای او ایجاد می‌شود.
                    </small>
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

@endsection

