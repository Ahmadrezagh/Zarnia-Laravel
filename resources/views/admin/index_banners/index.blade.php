@extends('layouts.panel')
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'بنر ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'بنر ها']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن بنر</button>
            <x-modal.create id="modal-create" title="ساخت بنر" action="{{route('index_banners.store')}}" >
                <x-form.input title="عنوان"  name="title" />
                <x-form.input title="لینک"  name="link" />
                <x-form.file-input title="تصویر بنر" name="cover_image" />
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.index_banners')"
            id="index_banners-table"
            :columns="[
                            ['label' => 'عنوان', 'key' => 'title', 'type' => 'text'],
                        ]"
            :items="$index_banners"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($index_banners as $index_banner)

                <x-modal.destroy id="modal-destroy-{{$index_banner->id}}" title="حذف بنر" action="{{route('index_banners.destroy', $index_banner->id)}}" title="{{$index_banner->title}}" />

                <x-modal.update id="modal-edit-{{$index_banner->id}}" title="ویرایش بنر" action="{{route('index_banners.update',$index_banner->id)}}" >
                    <x-form.input title="عنوان"  name="title" :value="$index_banner->title" />
                    <x-form.input title="لینک"  name="link" :value="$index_banner->link" />
                    <x-form.file-input title="تصویر بنر" name="cover_image" />
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

@endsection
