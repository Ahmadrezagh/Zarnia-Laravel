@extends('layouts.panel')
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'قالب ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'قالب ها']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن قالب</button>
            <x-modal.create id="modal-create" title="ساخت قالب" action="{{route('invoice_templates.store')}}" >
                <x-form.input title="نام"  name="name" />
                <x-form.file-input title="فایل بک گراند" name="background" />
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.templates')"
            id="templates-table"
            :columns="[
                            ['label' => 'نام', 'key' => 'name', 'type' => 'text'],
                        ]"
            :items="$templates"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'route','route' => ['invoice_templates.edit',['invoice_template' => '{id}']] ],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($templates as $template)

                <x-modal.destroy id="modal-destroy-{{$template->id}}" title="حذف قالب" action="{{route('invoice_templates.destroy', $template->id)}}" title="{{$template->title}}" />

                <x-modal.update id="modal-edit-{{$template->id}}" title="ویرایش قالب" action="{{route('invoice_templates.update',$template->id)}}" >
                    <x-form.input title="نام"  name="name" :value="$template->name" />
                    <x-form.file-input title="فایل بک گراند" name="background" />
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

@endsection
