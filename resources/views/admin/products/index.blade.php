@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'محصولات'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'محصولات']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  href="#">افزودن محصول جامع</button>
        </x-slot>
        <x-dataTable
            :url="route('table.products')"
            id="products-table"
            hasCheckbox="true"
            :columns="[
                            ['label' => 'تصویر محصول', 'key' => 'image', 'type' => 'image'],
                            ['label' => 'نام محصول', 'key' => 'name', 'type' => 'text','url' => 'https://google.com'],
                            ['label' => 'وزن', 'key' => 'weight', 'type' => 'text'],
                            ['label' => 'درصد اجرت', 'key' => 'ojrat_percentage', 'type' => 'text'],
                            ['label' => 'موجودی', 'key' => 'count', 'type' => 'text'],
                            ['label' => 'درصد تخفیف', 'key' => 'discount_percentage', 'type' => 'text'],
                            ['label' => 'دسته بندی ها', 'key' => 'categories_title', 'type' => 'text'],
                        ]"
            :items="$products"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >

            @foreach($products as $product)
                <x-modal.destroy id="modal-destroy-{{$product->id}}" title="حذف محصول" action="{{route('users.destroy', $product->id)}}" title="{{$product->name}}" />
                <!-- Modal -->
                <x-modal.update id="modal-edit-{{$product->id}}" title="ویرایش محصول" action="{{route('users.update', $product->id)}}" >
                    موارد (*) قابل تغییر نمی باشند
                    <hr>
                    <input type="hidden" name="id" value="{{$product->id}}">
                    <x-form.input title="نام محصول*" name="name" id="{{$product->id}}" value="{{$product->name}}" disabled="true" />
                    <x-form.input title="قیمت محصول*" name="name" id="{{$product->id}}" value="{{ number_format($product->price) }}" disabled="true" />

                </x-modal.update>
                <!-- /Modal -->
            @endforeach
        </x-dataTable>
    </x-page>

@endsection
