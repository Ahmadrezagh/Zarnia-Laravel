@extends('layouts.panel')
@section('content')
    <x-breadcrumb :title="'ویژگی ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'ویژگی ها']
      ]" />

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن ویژگی</button>
            <x-modal.create id="modal-create" title="ساخت ویژگی" action="{{route('attributes.store')}}" >
                <x-form.input title="نام"  name="name" />
                <x-form.input title="جمله پیشوند"  name="prefix_sentence" />
                <x-form.input title="جمله پسوند"  name="postfix_sentence" />
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.attributes')"
            id="attributes-table"
            :columns="[
                            ['label' => 'نام', 'key' => 'name', 'type' => 'text'],
                            ['label' => 'جمله پیشوندی', 'key' => 'prefix_sentence', 'type' => 'text'],
                            ['label' => 'جمله پسوند', 'key' => 'postfix_sentence', 'type' => 'text'],
                        ]"
            :items="$attributes"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >

            @foreach($attributes as $attribute)
                <x-modal.destroy id="modal-destroy-{{$attribute->id}}" title="حذف ویژگی" action="{{route('attributes.destroy', $attribute->id)}}" title="{{$attribute->name}}" />
                <!-- Modal -->
                <x-modal.update id="modal-edit-{{$attribute->id}}" title="ویرایش ویژگی" action="{{route('attributes.update', $attribute->id)}}" >
                    <input type="hidden" name="id" value="{{$attribute->id}}">
                    <x-form.input title="نام" :value="$attribute->name" name="name" />
                    <x-form.input title="جمله پیشوند" :value="$attribute->prefix_sentence"   name="prefix_sentence" />
                    <x-form.input title="جمله پسوند" :value="$attribute->postfix_sentence"   name="postfix_sentence" />
                </x-modal.update>
                <!-- /Modal -->
            @endforeach
        </x-table>
    </x-page>
@endsection
