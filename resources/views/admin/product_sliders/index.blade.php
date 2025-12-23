@extends('layouts.panel')
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'اسلایدر ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'اسلایدر ها']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن اسلایدر</button>
            <x-modal.create id="modal-create" title="ساخت اسلایدر" action="{{route('product_sliders.store')}}" >
                <x-form.input title="عنوان"  name="title"  />
                
                <x-query.generator :categories="$categories" targetInputId="query-create" />
                
                <x-form.input title="کوئری"  name="query" id="query-create" />
                <x-form.input title="قبل از دسته بندی اسلایدر"  name="before_category_slider" type="number" :value="0" />
                <x-form.input title="بعد از دسته بندی اسلایدر"  name="after_category_slider" type="number" :value="0" />
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.product_sliders')"
            id="product_sliders-table"
            :columns="[
                            ['label' => 'عنوان', 'key' => 'title', 'type' => 'text'],
                            ['label' => 'کوئری', 'key' => 'query', 'type' => 'text'],
                            ['label' => 'لیست دکمه ها', 'key' => 'buttonsTitle', 'type' => 'route', 'route' => ['product_sliders.product_slider_buttons.index', ['product_slider' => '{id}']],],
                        ]"
            :items="$product_sliders"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($product_sliders as $product_slider)

                <x-modal.destroy id="modal-destroy-{{$product_slider->id}}" title="حذف اسلایدر" action="{{route('product_sliders.destroy', $product_slider->id)}}" title="{{$product_slider->title}}" />

                <x-modal.update id="modal-edit-{{$product_slider->id}}" title="ویرایش اسلایدر" action="{{route('product_sliders.update',$product_slider->id)}}" >
                    <x-form.input title="عنوان"  name="title" :value="$product_slider->title" />
                    
                    <x-query.generator :categories="$categories" targetInputId="query-edit-{{$product_slider->id}}" />
                    
                    <x-form.input title="کوئری"  name="query" id="query-edit-{{$product_slider->id}}" :value="$product_slider->query" />
                    <x-form.input title="قبل از دسته بندی اسلایدر"  name="before_category_slider" type="number" :value="$product_slider->before_category_slider ?? 0" />
                    <x-form.input title="بعد از دسته بندی اسلایدر"  name="after_category_slider" type="number" :value="$product_slider->after_category_slider ?? 0" />
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

@endsection
