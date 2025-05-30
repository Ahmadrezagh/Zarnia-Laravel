@extends('layouts.panel')
@section('content')


    <!-- footer_title Header -->
    <x-breadcrumb :title="'ستون های فوتر'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'ستون های فوتر']
      ]" />
    <!-- End footer_title Header -->

    <!-- Row -->
    <x-page>
        <x-slot name="header">
                <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن ستون فوتر</button>
                <x-modal.create id="modal-create" title="ساخت ستون فوتر" action="{{route('footer_titles.store')}}" class="modal-lg" >
                    <x-form.input title="عنوان"  name="title" />
                </x-modal.create>

        </x-slot>

        <x-table
            :url="route('table.footer_titles')"
            id="footer-titles-table"
            :columns="[
                            ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
                        ]"
            :items="$footer_titles"
            :actions="[
                            ['label' => 'لینک ها', 'type' => 'route', 'route' => ['footer_title.footer_title_links.index',['footer_title' => '{id}']] ],
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($footer_titles as $footer_title)
                <!-- Modal -->
                <x-modal.destroy id="modal-destroy-{{$footer_title->id}}" title="حذف ستون فوتر" action="{{route('footer_titles.destroy', $footer_title->id)}}" title="{{$footer_title->title}}" />
                <x-modal.update id="modal-edit-{{$footer_title->id}}" title="ویرایش ستون فوتر" action="{{route('footer_titles.update',$footer_title->id)}}" class="modal-lg" >
                    <x-form.input title="عنوان"  name="title" value="{{$footer_title->title}}" />
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

@endsection

