@extends('layouts.panel')
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'دکمه های صفحه اصلی'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'دکمه های صفحه اصلی']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن دکمه</button>
            <x-modal.create id="modal-create" title="ساخت دکمه" action="{{route('index_buttons.store')}}" >
                <x-form.input title="عنوان"  name="title"  />
                <x-form.input title="کوئری"  name="query"  />
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.index_buttons')"
            id="index_buttons-table"
            :columns="[
                            ['label' => 'عنوان', 'key' => 'title', 'type' => 'text'],
                            ['label' => 'کوئری', 'key' => 'query', 'type' => 'text'],
                        ]"
            :items="$index_buttons"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($index_buttons as $index_button)

                <x-modal.destroy id="modal-destroy-{{$index_button->id}}" title="حذف دکمه" action="{{route('index_buttons.destroy', $index_button->id)}}" title="{{$index_button->title}}" />

                <x-modal.update id="modal-edit-{{$index_button->id}}" title="ویرایش دکمه" action="{{route('index_buttons.update',$index_button->id)}}" >
                    <x-form.input title="عنوان"  name="title" :value="$index_button->title" />
                    <x-form.input title="کوئری"  name="query" :value="$index_button->query" />
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

@endsection

