@extends('layouts.panel')
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'سوال ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'سوال ها']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن سوال</button>
            <x-modal.create id="modal-create" title="ساخت سوال" action="{{route('qas.store')}}" >
                <x-form.input title="سوال"  name="question" />
                <x-form.input title="جواب"  name="answer" />
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.qas')"
            id="qas-table"
            :columns="[
                            ['label' => 'سوال', 'key' => 'question', 'type' => 'text'],
                        ]"
            :items="$qas"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($qas as $qa)

                <x-modal.destroy id="modal-destroy-{{$qa->id}}" title="حذف سوال" action="{{route('qas.destroy', $qa->id)}}" title="{{$qa->title}}" />

                <x-modal.update id="modal-edit-{{$qa->id}}" title="ویرایش سوال" action="{{route('qas.update',$qa->id)}}" >
                    <x-form.input title="سوال"  name="question" :value="$qa->question" />
                    <x-form.input title="جواب"  name="answer" :value="$qa->answer" />
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

@endsection
