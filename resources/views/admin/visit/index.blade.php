@extends('layouts.panel')
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'آمار'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'آمار']
      ]" />
    <!-- End Page Header -->

    <!-- Row -->
    <x-page>
        <!-- page content -->
    </x-page>

@endsection
