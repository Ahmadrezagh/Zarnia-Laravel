@extends('layouts.panel')
@section('content')


    <!-- header_link Header -->
    <x-breadcrumb :title="'لینک ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'لینک ها']
      ]" />
    <!-- End header_link Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن لینک</button>
            <x-modal.create id="modal-create" title="ساخت لینک" action="{{route('header_links.store')}}" class="modal-lg" >
                <x-form.input title="عنوان"  name="title" />
                <x-form.input title="لینک"  name="url" />
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.header_links')"
            id="header-links-table"
            :columns="[
                            ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
                            ['label' => 'لینک', 'key' => 'url', 'type' => 'copiableText'],
                        ]"
            :items="$header_links"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($header_links as $header_link)
                <!-- Modal -->
                <x-modal.destroy id="modal-destroy-{{$header_link->id}}" title="حذف لینک" action="{{route('header_links.destroy', $header_link->id)}}" title="{{$header_link->title}}" />
                <x-modal.update id="modal-edit-{{$header_link->id}}" title="ویرایش لینک" action="{{route('header_links.update',$header_link->id)}}" class="modal-lg" >
                    <x-form.input title="عنوان"  name="title" value="{{$header_link->title}}" />
                    <x-form.input title="لینک"  name="url" value="{{$header_link->url}}" />
                </x-modal.update>
            @endforeach
        </x-table>

    </x-page>
@endsection

