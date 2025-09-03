@extends('layouts.panel')
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'دکمه ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'اسلایدر', 'url' => route('home')],
            ['label' => $product_slider->title, 'url' => route('home')],
            ['label' => 'دکمه ها']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن دکمه</button>
            <x-modal.create id="modal-create" title="ساخت دکمه" action="{{route('product_sliders.product_slider_buttons.store',$product_slider->id)}}" >
                <x-form.input title="عنوان"  name="title"  />
                <x-form.input title="کوئری"  name="query"  />
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.product_slider_buttons',$product_slider->id)"
            id="product_slider_buttons-table"
            :columns="[
                            ['label' => 'عنوان', 'key' => 'title', 'type' => 'text'],
                            ['label' => 'کوئری', 'key' => 'query', 'type' => 'text'],
                         ]"
            :items="$product_slider_buttons"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($product_slider_buttons as $product_slider_button)

                <x-modal.destroy id="modal-destroy-{{$product_slider_button->id}}" title="حذف دکمه" action="{{route('product_sliders.product_slider_buttons.destroy',['product_slider' => $product_slider->id,'product_slider_button' => $product_slider_button->id])}}" title="{{$product_slider_button->title}}" />

                <x-modal.update id="modal-edit-{{$product_slider_button->id}}" title="ویرایش دکمه" action="{{route('product_sliders.product_slider_buttons.update',['product_slider' => $product_slider->id,'product_slider_button' => $product_slider_button->id])}}" >
                    <x-form.input title="عنوان"  name="title" :value="$product_slider_button->title" />
                    <x-form.input title="کوئری"  name="query" :value="$product_slider_button->query" />
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

@endsection
