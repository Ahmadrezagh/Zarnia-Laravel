@extends('layouts.panel')
@section('css')
    <style>
        .select2-container {
            width: 100% !important;
        }
        .select2-search__field {
            direction: rtl;
        }


    </style>
    <style>
        .custom-multiselect {
            position: relative;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            width: 100%;
        }
        .custom-multiselect-display {
            padding: 8px 10px;
        }
        .custom-multiselect-dropdown {
            display: none;
            position: absolute;
            z-index: 9999;
            background: #fff;
            border: 1px solid #ddd;
            width: 100%;
            max-height: 250px;
            overflow-y: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .custom-multiselect.open .custom-multiselect-dropdown {
            display: block;
        }
        .custom-multiselect-search {
            width: 100%;
            border: none;
            border-bottom: 1px solid #eee;
            padding: 6px 8px;
        }
        .custom-multiselect-options label {
            display: flex;
            align-items: center;
            padding: 6px 10px;
            cursor: pointer;
        }
        .custom-multiselect-options label:hover {
            background: #f3f3f3;
        }
        .custom-multiselect-options input {
            margin-left: 8px;
        }
    </style>

@endsection
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'وبلاگ'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'وبلاگ']
      ]" />
    <!-- End Page Header -->

    <!-- Row -->
    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن وبلاگ</button>

            <x-modal.create id="modal-create" title="ساخت وبلاگ" action="{{route('blogs.store')}}" >
                <x-form.input title="عنوان"  name="title" />
                <x-form.textarea title="متن" name="description" />
                <x-form.file-input title="تصویر کاور" name="cover_image"/>
            </x-modal.create>
        </x-slot>
        <x-table
            :url="route('table.blogs')"
            id="blogs-table"
            :columns="[
                            ['label' => 'تصویر', 'key' => 'image', 'type' => 'image'],
                            ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
                        ]"
            :items="$blogs"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >

            @foreach($blogs as $blog)
                <!-- Modal -->
                <x-modal.destroy id="modal-destroy-{{$blog->id}}" title="حذف وبلاگ" action="{{route('blogs.destroy', $blog->slug)}}" title="{{$blog->title}}" />

                <x-modal.update id="modal-edit-{{$blog->id}}" title="ساخت وبلاگ" action="{{route('blogs.update',$blog->slug)}}" >
                    <x-form.input title="عنوان"  name="title" :value="$blog->title" />
                    <x-form.textarea title="متن" name="description" :value="$blog->description" />
                    <x-form.file-input title="تصویر کاور" name="cover_image"/>
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>




@endsection
@section('js')


@endsection