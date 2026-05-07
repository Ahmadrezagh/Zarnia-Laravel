@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'روش‌های ارسال'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'روش‌های ارسال']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modal-create">
                <i class="fas fa-truck"></i> افزودن روش ارسال
            </button>
            
            <x-modal.create id="modal-create" title="ساخت روش ارسال" action="{{route('shippings.store')}}" enctype="multipart/form-data">
                <x-form.input title="عنوان" name="title" required="true" />
                <x-form.input title="کلید (برای شناسایی در کد)" name="key" placeholder="مثال: post, peyk" />
                <x-form.input title="قیمت (تومان)" name="price" type="number" required="true" />
                <x-form.input title="قیمت غیرفعال (تومان)" name="passive_price" type="number" />
                
                <div class="form-group">
                    <label for="create-image">تصویر</label>
                    <input type="file" name="image" id="create-image" class="form-control" accept="image/*">
                </div>
                
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    قیمت غیرفعال برای زمانی است که روش ارسال موقتاً غیرفعال می‌شود.
                </small>
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.shippings')"
            id="shippings-table"
            :columns="[
                            ['label' => 'تصویر', 'key' => 'image', 'type' => 'image'],
                            ['label' => 'عنوان', 'key' => 'title', 'type' => 'text'],
                            ['label' => 'کلید', 'key' => 'key', 'type' => 'text'],
                            ['label' => 'قیمت', 'key' => 'price', 'type' => 'text'],
                            ['label' => 'قیمت غیرفعال', 'key' => 'passive_price', 'type' => 'text'],
                            ['label' => 'تعداد زمان‌ها', 'key' => 'times_count', 'type' => 'text'],
                            ['label' => 'تاریخ ایجاد', 'key' => 'created_at', 'type' => 'text'],
                        ]"
            :items="$shippings"
            :actions="[
                            ['label' => 'زمان‌های ارسال', 'route' => ['shippings.times', ['{id}']]],
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($shippings as $shipping)

                <x-modal.destroy 
                    id="modal-destroy-{{$shipping->id}}" 
                    title="حذف روش ارسال" 
                    action="{{route('shippings.destroy', $shipping->id)}}" 
                    title="{{$shipping->title}}" />

                <x-modal.update 
                    id="modal-edit-{{$shipping->id}}" 
                    title="ویرایش روش ارسال" 
                    action="{{route('shippings.update', $shipping->id)}}" 
                    enctype="multipart/form-data">
                    
                    <x-form.input title="عنوان" name="title" :value="$shipping->title" required="true" />
                    <x-form.input title="کلید (برای شناسایی در کد)" name="key" :value="$shipping->key" placeholder="مثال: post, peyk" />
                    <x-form.input title="قیمت (تومان)" name="price" type="number" :value="$shipping->price" required="true" />
                    <x-form.input title="قیمت غیرفعال (تومان)" name="passive_price" type="number" :value="$shipping->passive_price" />
                    
                    <div class="form-group">
                        <label for="edit-image-{{$shipping->id}}">تصویر جدید (اختیاری)</label>
                        <input type="file" name="image" id="edit-image-{{$shipping->id}}" class="form-control" accept="image/*">
                        @if($shipping->image)
                            <div class="mt-2">
                                <img src="{{ $shipping->image }}" width="100" class="rounded">
                            </div>
                        @endif
                    </div>
                    
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        برای نگه داشتن تصویر فعلی، فایل جدیدی آپلود نکنید.
                    </small>
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

@endsection

