@extends('layouts.panel')
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'صفحه ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'صفحه ها']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن صفحه</button>
            <x-modal.create id="modal-create" title="ساخت صفحه" action="{{route('pages.store')}}" class="modal-lg" >
                <x-form.input title="عنوان"  name="title" />
                <x-form.textarea name="content" title="محتوا" />
                <x-form.select-option title="منتشر شود" name="published" >
                    <option value="0">خیر</option>
                    <option value="1">بله</option>
                </x-form.select-option>
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.pages')"
            id="pages-table"
            :columns="[
                            ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
                            ['label' => 'لینک', 'key' => 'url', 'type' => 'copiableText'],
                            [
                                'label' => 'وضعیت', 'key' => 'published',
                                'type' => 'binaryCondition',
                                'texts'=>['true' => 'منتشر شده', 'false' => 'منتشر نشده']
                             ],
                        ]"
            :items="$pages"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >

            @foreach($pages as $page)
                <!-- Modal -->
                <x-modal.destroy id="modal-destroy-{{$page->id}}" title="حذف صفحه" action="{{route('pages.destroy', $page->slug)}}" title="{{$page->title}}" />
                <x-modal.update id="modal-edit-{{$page->id}}" title="ویرایش صفحه" action="{{route('pages.update',$page->slug)}}" class="modal-lg" >
                    <x-form.input title="عنوان"  name="title" value="{{$page->title}}" />
                    <x-form.textarea name="content" title="محتوا" :value="$page->content" />
                    <x-form.select-option title="منتشر شود" name="published" >
                        <option value="0" @if($page->published == 0) selected @endif >خیر</option>
                        <option value="1" @if($page->published == 1) selected @endif >بله</option>
                    </x-form.select-option>
                </x-modal.update>
            @endforeach
        </x-table>

    </x-page>




@endsection

