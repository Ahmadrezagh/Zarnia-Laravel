@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'زمان‌های ارسال - ' . $shipping->title" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'روش‌های ارسال', 'url' => route('shippings.index')],
            ['label' => 'زمان‌های ارسال']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="{{ route('shippings.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i> بازگشت به روش‌های ارسال
                </a>
                <button class="btn btn-primary" data-toggle="modal" data-target="#modal-create">
                    <i class="fas fa-clock"></i> افزودن زمان ارسال
                </button>
            </div>
            
            <x-modal.create id="modal-create" title="افزودن زمان ارسال برای {{$shipping->title}}" action="{{route('shippings.times.store', $shipping->id)}}" >
                <x-form.input title="عنوان زمان ارسال" name="title" required="true" placeholder="مثال: امروز، فردا، 2-3 روز کاری" />
                
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    مثال: "امروز"، "فردا"، "2-3 روز کاری"، "یک هفته"
                </small>
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.shipping_times', $shipping->id)"
            id="shipping-times-table"
            :columns="[
                            ['label' => 'عنوان', 'key' => 'title', 'type' => 'text'],
                            ['label' => 'تاریخ ایجاد', 'key' => 'created_at', 'type' => 'text'],
                        ]"
            :items="$shippingTimes"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($shippingTimes as $time)

                <x-modal.destroy 
                    id="modal-destroy-{{$time->id}}" 
                    title="حذف زمان ارسال" 
                    action="{{route('shippings.times.destroy', [$shipping->id, $time->id])}}" 
                    title="{{$time->title}}" />

                <x-modal.update 
                    id="modal-edit-{{$time->id}}" 
                    title="ویرایش زمان ارسال" 
                    action="{{route('shippings.times.update', [$shipping->id, $time->id])}}" >
                    
                    <x-form.input title="عنوان زمان ارسال" name="title" :value="$time->title" required="true" />
                    
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        مثال: "امروز"، "فردا"، "2-3 روز کاری"، "یک هفته"
                    </small>
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

@endsection

