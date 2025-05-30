@extends('layouts.panel')
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'دسته بندی ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'دسته بندی ها']
      ]" />
    <!-- End Page Header -->

    <!-- Row -->
    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن دسته بندی</button>

            <x-modal.create id="modal-create" title="ساخت دسته بندی" action="{{route('categories.store')}}" >
                <x-form.input title="نام"  name="title" />
                <x-form.select-option title="دسته بندی والد" name="parent_id" >
                    @foreach($categories as $parent_category)
                        <option value="{{$parent_category->id}}">{{$parent_category->title}}</option>
                    @endforeach
                </x-form.select-option>
            </x-modal.create>
        </x-slot>
        <x-table
            :url="route('table.categories')"
            id="categories-table"
            :columns="[
                            ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
                        ]"
            :items="$categories"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >

            @foreach($categories as $category)
                <!-- Modal -->
                <x-modal.destroy id="modal-destroy-{{$category->id}}" title="حذف دسته بندی" action="{{route('categories.destroy', $category->id)}}" title="{{$category->title}}" />

                <x-modal.update id="modal-edit-{{$category->id}}" title="ساخت دسته بندی" action="{{route('categories.update',$category->id)}}" >
                    <x-form.input title="نام"  name="title" :value="$category->title" />
                    <x-form.select-option title="دسته بندی والد" name="parent_id" >
                        @foreach($categories as $parent_category)
                            @if( ($parent_category->id != $category->id) && (!$category->isParentOfCategory($parent_category) ))
                                <option value="{{$category->id}}" @if($category->parent_id == $parent_category->id) selected @endif >{{$parent_category->title}}</option>
                            @endif
                        @endforeach
                    </x-form.select-option>
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>
@endsection
