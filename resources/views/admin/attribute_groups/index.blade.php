@extends('layouts.panel')
@section('content')
    <x-breadcrumb :title="'گروه ویژگی ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'گروه ویژگی ها']
      ]" />

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن ویژگی</button>
            <x-modal.create id="modal-create" title="ساخت ویژگی" action="{{route('attribute_groups.store')}}" >
                <x-form.input title="نام"  name="name" />
                <x-form.select-option title="ویژگی ها" name="attribute_ids[]" multiple="true" >
                    @foreach($attributes as $attribute)
                        <option value="{{ $attribute->id }}">{{ $attribute->name }}</option>
                    @endforeach
                </x-form.select-option>
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.attribute_groups')"
            id="attribute_groups-table"
            :columns="[
                            ['label' => 'نام', 'key' => 'name', 'type' => 'text'],
                        ]"
            :items="$attribute_groups"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >

            @foreach($attribute_groups as $attribute_group)
                <x-modal.destroy id="modal-destroy-{{$attribute_group->id}}" title="حذف ویژگی" action="{{route('attribute_groups.destroy', $attribute_group->id)}}" title="{{$attribute_group->name}}" />
                <!-- Modal -->
                <x-modal.update id="modal-edit-{{$attribute_group->id}}" title="ویرایش ویژگی" action="{{route('attribute_groups.update', $attribute_group->id)}}" >
                    <input type="hidden" name="id" value="{{$attribute_group->id}}">
                    <x-form.input title="نام" :value="$attribute_group->name" name="name" />
                    <x-form.select-option title="ویژگی ها" name="attribute_ids[]" multiple="true" >
                        @foreach($attributes as $attribute)
                            <option value="{{ $attribute->id }}" @if($attribute_group->attributes()->where('attribute_id','=',$attribute->id)->exists() ) selected @endif >{{ $attribute->name }}</option>
                        @endforeach
                    </x-form.select-option>
                </x-modal.update>
                <!-- /Modal -->
            @endforeach
        </x-table>
    </x-page>
@endsection
