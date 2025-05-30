@extends('layouts.panel')
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'نقش ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'نقش ها']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن نقش</button>
            <x-modal.create id="modal-create" title="ساخت نقش" action="{{route('roles.store')}}" >
                <x-form.input title="عنوان"  name="title" />
                <div class="row">
                    @foreach($permissions as $permission)
                        <x-form.check-input col="col-6" :title="$permission->title" name="permissions[]" :value="$permission->id" id="create-{{$permission->id}}" />
                    @endforeach
                </div>
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.roles')"
            id="roles-table"
            :columns="[
                            ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
                        ]"
            :items="$roles"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($roles as $role)

                <x-modal.destroy id="modal-destroy-{{$role->id}}" title="حذف نقش" action="{{route('roles.destroy', $role->id)}}" title="{{$role->title}}" />

                <x-modal.update id="modal-edit-{{$role->id}}" title="ویرایش نقش" action="{{route('roles.update',$role->id)}}" >
                    <x-form.input title="عنوان" value="{{$role->title}}"  name="title" />
                    <div class="row">
                        @foreach($permissions as $permission)
                            <x-form.check-input col="col-6" :title="$permission->title" name="permissions[]" :value="$permission->id" id="edit-{{$role->id}}-{{$permission->id}}"  checked="{{$role->hasThisPermission($permission)}}" />
                        @endforeach
                    </div>
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

@endsection
