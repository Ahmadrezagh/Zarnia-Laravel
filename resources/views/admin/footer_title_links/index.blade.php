@extends('layouts.panel')
@section('content')


    <!-- footer_title_link Header -->
    <x-breadcrumb :title="'لینک ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'لینک ها']
      ]" />
    <!-- End footer_title_link Header -->

    <!-- Row -->
    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن لینک</button>
            <x-modal.create id="modal-create" title="ساخت لینک" action="{{route('footer_title.footer_title_links.store',$footer_title->id)}}" class="modal-lg" >
                <x-form.input title="عنوان"  name="title" />
                <x-form.input title="لینک"  name="url" />
            </x-modal.create>

        </x-slot>

        <x-table
            :url="route('table.footer_title_links',$footer_title->id)"
            id="footer-title-links-table"
            :columns="[
                            ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
                            ['label' => 'لینک', 'key' => 'url', 'type' => 'copiableText'],
                        ]"
            :items="$footer_title_links"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($footer_title_links as $footer_title_link)
                <!-- Modal -->
                <x-modal.destroy id="modal-destroy-{{$footer_title_link->id}}" title="حذف لینک" action="{{route('footer_title.footer_title_links.destroy', ['footer_title' => $footer_title->id,'footer_title_link' => $footer_title_link->id])}}" title="{{$footer_title_link->title}}" />
                <x-modal.update id="modal-edit-{{$footer_title_link->id}}" title="ویرایش لینک" action="{{route('footer_title.footer_title_links.update',['footer_title' => $footer_title->id,'footer_title_link' => $footer_title_link->id])}}" class="modal-lg" >
                    <x-form.input title="عنوان"  name="title" value="{{$footer_title_link->title}}" />
                    <x-form.input title="لینک"  name="url" value="{{$footer_title_link->url}}" />
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>
@endsection

