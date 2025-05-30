@extends('layouts.panel')
@section('content')
    <x-breadcrumb :title="'مدیران'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'مدیران']
      ]" />

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن مدیر</button>
            <x-modal.create id="modal-create" title="ساخت مدیر" action="{{route('admins.store')}}" >
                <x-form.input title="نام"  name="name" />
                <x-form.file-input title="عکس" name="profile_image" />
                <x-form.input title="ایمیل"  name="email" type="email" />
                <x-form.input title="شماره تماس"  name="phone" type="number" />
                <x-form.input title="رمز عبور"  name="password" type="password" />
                <x-form.input title="تکرار رمز عبور"  name="password_confirmation" type="password" />
                <x-form.select-option title="نقش" name="roles" >
                    @foreach($roles as $role)
                        <option value="{{$role->id}}" >{{$role->title}}</option>
                    @endforeach
                </x-form.select-option>
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.admins')"
            id="admins-table"
            :columns="[
                            ['label' => 'تصویر پروفایل', 'key' => 'profile_image', 'type' => 'image'],
                            ['label' => 'نام', 'key' => 'name', 'type' => 'text'],
                        ]"
            :items="$users"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >

            @foreach($users as $user)
                <x-modal.destroy id="modal-destroy-{{$user->id}}" title="حذف مدیر" action="{{route('admins.destroy', $user->id)}}" title="{{$user->name}}" />
                <!-- Modal -->
                <x-modal.update id="modal-edit-{{$user->id}}" title="ویرایش مدیر" action="{{route('admins.update', $user->id)}}" >
                    <input type="hidden" name="id" value="{{$user->id}}">
                    <x-form.input title="نام" :value="$user->name" name="name" />
                    <x-form.file-input title="عکس" name="profile_image" />
                    <x-form.input title="ایمیل" :value="$user->email" name="email" type="email" />
                    <x-form.input title="شماره تماس" :value="$user->phone" name="phone" type="number" />
                    <x-form.input title="رمز عبور"  name="password" type="password" />
                    <x-form.input title="تکرار رمز عبور"  name="password" type="password_confirmation" />
                    <x-form.select-option title="نقش" name="roles" >
                        @foreach($roles as $role)
                            <option value="{{$role->id}}" @if($user->hasRole($role)) selected @endif >{{$role->title}}</option>
                        @endforeach
                    </x-form.select-option>
                </x-modal.update>
                <!-- /Modal -->
            @endforeach
        </x-table>
    </x-page>
@endsection
