
@extends('layouts.panel')
@section('home','active')
@section('title')
    خانه
@endsection
@section('content')
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="main-content-title tx-24 mg-b-5">به داشبورد خوش آمدید</h2>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">خانه</a></li>
                <li class="breadcrumb-item active" aria-current="page">داشبورد پروژه</li>
            </ol>
        </div>
        <div class="d-flex">
            <div class="justify-content-center">

            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <!--Row-->
    <div class="row row-sm">
        <div class="col-12">
            <div class="card custom-card card-dashboard-calendar pb-0">
                <label class="main-content-label mb-2 pt-1">عنوان </label>
                <span class="d-block tx-12 mb-2 text-muted">توضیحات</span>
                <div style="min-height: 500px">

                </div>
            </div>
        </div><!-- col end -->
    </div><!-- Row end -->

@endsection
