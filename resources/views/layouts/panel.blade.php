<!DOCTYPE html>
<html lang="en" dir="rtl" >

<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<head>

    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport">
    <meta name="description" content="Spruha -  Admin Panel laravel Dashboard Template">
    <meta name="author" content="Spruko Technologies Private Limited">
    <meta name="keywords" content="admin laravel template, template laravel admin, laravel css template, best admin template for laravel, laravel blade admin template, template admin laravel, laravel admin template bootstrap 4, laravel bootstrap 4 admin template, laravel admin bootstrap 4, admin template bootstrap 4 laravel, bootstrap 4 laravel admin template, bootstrap 4 admin template laravel, laravel bootstrap 4 template, bootstrap blade template, laravel bootstrap admin template">

    <!-- Favicon -->
    <link rel="icon" href="{{asset('dashboard/assets/img/brand/favicon.ico')}}" type="image/x-icon"/>

    <!-- Title -->
    <title>
        {{ setting('name') }} @if (trim($__env->yieldContent('title'))) |
        @yield('title')@endif
    </title>

    <!-- Bootstrap css-->
    <link href="{{asset('dashboard/assets/plugins/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet"/>

    <!-- Icons css-->
    <link href="{{asset('dashboard/assets/plugins/web-fonts/icons.css')}}" rel="stylesheet"/>
    <link href="{{asset('dashboard/assets/plugins/web-fonts/font-awesome/font-awesome.min.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="{{asset('dashboard/assets/plugins/web-fonts/plugin.css')}}" rel="stylesheet"/>

    <!-- Style css-->
    <link href="{{asset('dashboard/assets/css-rtl/style/style.css')}}" rel="stylesheet">
    <link href="{{asset('dashboard/assets/css-rtl/skins.css')}}" rel="stylesheet">
    <link href="{{asset('dashboard/assets/css-rtl/dark-style.css')}}" rel="stylesheet">
    <link href="{{asset('dashboard/assets/css-rtl/colors/default.css" rel="stylesheet')}}">

    <!-- Color css-->
    <link id="theme" rel="stylesheet" type="text/css" media="all" href="{{asset('dashboard/assets/css-rtl/colors/color.css')}}">

    <!-- Select2 css -->
    <link href="{{asset('dashboard/assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">

    <!-- Mutipleselect css-->
    <link rel="stylesheet" href="{{asset('dashboard/assets/plugins/multipleselect/multiple-select.css')}}">

    <!-- Sidemenu css-->
    <link href="{{asset('dashboard/assets/css-rtl/sidemenu/sidemenu.css')}}" rel="stylesheet">

    <!-- Switcher css-->
    <link href="{{asset('dashboard/assets/switcher/css/switcher-rtl.css')}}" rel="stylesheet">
    <link href="{{asset('dashboard/assets/switcher/demo.css')}}" rel="stylesheet">


    <!-- CkEditor -->
    <script src="https://cdn.ckeditor.com/4.15.0/full/ckeditor.js"></script>

    <style>
        .cke_notification_warning{
            display: none;
        }
        
        /* White mode sidebar */
        .main-sidebar {
            background-color: #ffffff !important;
            border-right: 1px solid #e0e0e0 !important;
        }
        
        .main-sidebar .sidemenu-logo {
            background-color: #ffffff !important;
            border-bottom: 1px solid #e0e0e0 !important;
        }
        
        .main-sidebar .nav-link {
            color: #495057 !important;
        }
        
        .main-sidebar .nav-link:hover,
        .main-sidebar .nav-link.active {
            background-color: #f8f9fa !important;
            color: #007bff !important;
        }
        
        .main-sidebar .sidemenu-label {
            color: #495057 !important;
        }
        
        .main-sidebar .sidemenu-icon {
            color: #6c757d !important;
        }
        
        .main-sidebar .nav-link:hover .sidemenu-icon,
        .main-sidebar .nav-link.active .sidemenu-icon {
            color: #007bff !important;
        }
        
        .main-sidebar .nav-sub {
            background-color: #f8f9fa !important;
        }
        
        .main-sidebar .nav-sub-link {
            color: #495057 !important;
        }
        
        .main-sidebar .nav-sub-link:hover {
            background-color: #e9ecef !important;
            color: #007bff !important;
        }
        
        .main-sidebar .nav-sub-item {
            color: #495057 !important;
        }
        
        .main-sidebar .nav-sub-item a {
            color: #495057 !important;
        }
        
        .main-sidebar .nav-sub-item a:hover {
            color: #007bff !important;
        }
        
        .main-sidebar .nav-sub .nav-sub-link,
        .main-sidebar .nav-sub .nav-sub-item .nav-sub-link {
            color: #495057 !important;
        }
        
        .main-sidebar .angle {
            color: #6c757d !important;
        }
        
        .main-sidebar .shape1,
        .main-sidebar .shape2 {
            background-color: transparent !important;
        }
        
        /* Force all submenu text to be dark */
        .main-sidebar .nav-sub,
        .main-sidebar .nav-sub *,
        .main-sidebar .nav-sub-item,
        .main-sidebar .nav-sub-item * {
            color: #495057 !important;
        }
        
        .main-sidebar .nav-sub-item:hover,
        .main-sidebar .nav-sub-item:hover * {
            color: #007bff !important;
        }
    </style>


    <link rel="stylesheet" href="https://unpkg.com/persian-datepicker@1.2.0/dist/css/persian-datepicker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('image-uploader/dist/image-uploader.min.css') }}" rel="stylesheet" />
    
    <!-- Custom tooltip styles for etiket codes -->
    <style>
        /* Enhanced etiket code styling */
        .etiket-code-item {
            position: relative;
            display: inline-block;
            transition: opacity 0.2s;
        }
        
        .etiket-code-item:hover {
            opacity: 0.8;
        }
        
        /* Customize Bootstrap tooltip for etiket codes */
        .tooltip {
            font-family: inherit;
        }
        
        .tooltip-inner {
            max-width: 300px;
            padding: 8px 12px;
            font-size: 13px;
            text-align: center;
            background-color: #2c3e50;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .tooltip.bs-tooltip-top .arrow::before {
            border-top-color: #2c3e50;
        }
        
        .tooltip.bs-tooltip-bottom .arrow::before {
            border-bottom-color: #2c3e50;
        }
    </style>
    
    @yield('css')
    @yield('head')


    <script>
        var s=document.createElement("script");s.src="https://van.najva.com/static/js/main-script.js";s.defer=!0;s.id="najva-mini-script";s.setAttribute("data-najva-id","6686bfc9-d05f-47d6-bd8f-af06db112a44");document.head.appendChild(s);
    </script>
</head>

<body class="main-body leftmenu">

<!-- Loader -->
<div id="global-loader">
    <img src="{{asset('dashboard/assets/img/loader.svg')}}" class="loader-img" alt="لودر">
</div>
<!-- End Loader -->

<!-- Page -->
<div class="page">

    <!-- Sidemenu -->
    <div class="main-sidebar main-sidebar-sticky side-menu">
        <div class="sidemenu-logo">
            <a class="main-logo" href="#">
                <img src="{{url('logo/long.png')}}" class="header-brand-img desktop-logo" alt="UltimateSoft" style="width: 134px;height: 37px">
                <img src="{{url('logo/ico.png')}}" class="header-brand-img icon-logo" alt="UltimateSoft" style="width: 45px;height: 45px">
                <img src="{{url('logo/ico.png')}}" class="header-brand-img desktop-logo theme-logo" alt="لوگو">
                <img src="{{url('logo/ico.png')}}" class="header-brand-iultimate type png blue.pngmg icon-logo theme-logo" alt="لوگو">
            </a>
        </div>
        <div class="main-sidebar-body">
            <ul class="nav">

                <li class="nav-item">
                    <a class="nav-link" href="{{route('home')}}">
                        <span class="shape1"></span>
                        <span class="shape2"></span>
                        <i class="fa fa-home sidemenu-icon"></i>
                        <span class="sidemenu-label">خانه</span></a>
                </li>
                @if ((Auth::user()->isAdmin() && Auth::user()->can('Admin')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-user-shield sidemenu-icon"></i>
                            <span class="sidemenu-label">مدیریت</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('admins.index')}}">مدیر ها</a>
                            </li>
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('roles.index')}}">نقش ها</a>
                            </li>
                        </ul>
                    </li>
                @endif
                @if ((Auth::user()->isAdmin() && Auth::user()->can('Statistics')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas chart-circle-value sidemenu-icon"></i>
                            <span class="sidemenu-label">آمار</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('visit.index')}}">آمار</a>
                            </li>
                        </ul>
                    </li>
                @endif
                @if ((Auth::user()->isAdmin() && Auth::user()->can('Blog')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas chart-circle-value sidemenu-icon"></i>
                            <span class="sidemenu-label">وبلاگ</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('blogs.index')}}">وبلاگ</a>
                            </li>
                        </ul>
                    </li>
                @endif
                @if ((Auth::user()->isAdmin() && Auth::user()->can('Categories')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-clipboard-list sidemenu-icon"></i>
                            <span class="sidemenu-label">دسته بندی ها</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('categories.index')}}">دسته بندی ها</a>
                            </li>
                        </ul>
                    </li>
                @endif
                @if ((Auth::user()->isAdmin() && Auth::user()->can('Pages')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-sticky-note sidemenu-icon"></i>
                            <span class="sidemenu-label">صفحه ها</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('pages.index')}}">صفحه ها</a>
                            </li>
                        </ul>
                    </li>
                @endif
                @if ((Auth::user()->isAdmin() && Auth::user()->can('Links')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-chain sidemenu-icon"></i>
                            <span class="sidemenu-label">لینک ها</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('header_links.index')}}">لینک های هدر</a>
                            </li>
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('footer_titles.index')}}">ستون های فوتر</a>
                            </li>
                        </ul>
                    </li>
                @endif

            @if ((Auth::user()->isAdmin() && Auth::user()->can('User')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-users sidemenu-icon"></i>
                            <span class="sidemenu-label">مدیریت کاربران</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('users.index')}}">کاربران</a>
                            </li>
                        </ul>
                    </li>
                @endif
            @if ((Auth::user()->isAdmin() && Auth::user()->can('Product')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-boxes sidemenu-icon"></i>
                            <span class="sidemenu-label">مدیریت محصولات</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('products.index')}}">محصولات</a>
                            </li>
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('products.products_not_available')}}">محصولات ناموجود</a>
                            </li>
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('products.product_without_category')}}">محصولات بدون دسته بندی</a>
                            </li>
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('products.products_comprehensive')}}">محصولات جامع</a>
                            </li>
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('products.products_comprehensive_not_available')}}">محصولات جامع ناموجود</a>
                            </li>
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('products.deleted')}}">محصولات حذف شده</a>
                            </li>
                        </ul>
                    </li>
                @endif
            @if ((Auth::user()->isAdmin() && Auth::user()->can('Order')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-boxes sidemenu-icon"></i>
                            <span class="sidemenu-label">مدیریت سفارشات</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('admin_orders.index')}}">سفارشات</a>
                            </li>
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('gold_summary.index')}}">
                                    <i class="fas fa-coins"></i> خلاصه گردش طلا
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
            @if ((Auth::user()->isAdmin() && Auth::user()->can('DiscontCode')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-percentage sidemenu-icon"></i>
                            <span class="sidemenu-label">کد تخفیف</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('discounts.index')}}">کد های تخفیف</a>
                            </li>
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('gift_structures.index')}}">
                                    <i class="fas fa-gift"></i> ساختار هدایا
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
            @if ((Auth::user()->isAdmin() && Auth::user()->can('Order')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-shipping-fast sidemenu-icon"></i>
                            <span class="sidemenu-label">روش‌های ارسال</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('shippings.index')}}">
                                    <i class="fas fa-truck"></i> روش‌های ارسال
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
            @if ((Auth::user()->isAdmin() && Auth::user()->can('Attribute')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-pen sidemenu-icon"></i>
                            <span class="sidemenu-label">مدیریت ویژگی ها</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('attributes.index')}}">ویژگی ها</a>
                            </li>
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('attribute_groups.index')}}">گروه ویژگی ها</a>
                            </li>
                        </ul>
                    </li>
                @endif
            @if ((Auth::user()->isAdmin() && Auth::user()->can('QA')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-question sidemenu-icon"></i>
                            <span class="sidemenu-label">پرسش های پر تکرار</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('qas.index')}}">سوال ها</a>
                            </li>
                        </ul>
                    </li>
                @endif
            @if ((Auth::user()->isAdmin() && Auth::user()->can('ProductSlider')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-sliders sidemenu-icon"></i>
                            <span class="sidemenu-label">اسلاید محصول</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('product_sliders.index')}}">اسلاید ها</a>
                            </li>
                        </ul>
                    </li>
                @endif
            @if ((Auth::user()->isAdmin() && Auth::user()->can('IndexBanner')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-sliders sidemenu-icon"></i>
                            <span class="sidemenu-label">بنر ها</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('index_banners.index')}}">بنر ها</a>
                            </li>
                        </ul>
                    </li>
                @endif
            @if ((Auth::user()->isAdmin() && Auth::user()->can('IndexButton')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-sliders sidemenu-icon"></i>
                            <span class="sidemenu-label">دکمه های صفحه اصلی</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('index_buttons.index')}}">دکمه ها</a>
                            </li>
                        </ul>
                    </li>
                @endif
            @if ((Auth::user()->isAdmin() && Auth::user()->can('Template')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-sliders sidemenu-icon"></i>
                            <span class="sidemenu-label">قالب ها</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            <li class="nav-sub-item">
                                <a class="nav-sub-link" href="{{route('invoice_templates.index')}}">قالب فاکتور ها</a>
                            </li>
                        </ul>
                    </li>
                @endif
                @if ((Auth::user()->isAdmin() && Auth::user()->can('Setting')) || Auth::user()->isSuperAdmin() )
                    <li class="nav-item">
                        <a class="nav-link with-sub" href="#">
                            <span class="shape1"></span>
                            <span class="shape2"></span>
                            <i class="fas fa-cogs sidemenu-icon"></i>
                            <span class="sidemenu-label">تنظیمات</span><i class="angle fe fe-chevron-left"></i></a>
                        <ul class="nav-sub">
                            @foreach($setting_groups as $group)
                                <li class="nav-sub-item">
                                    <a class="nav-sub-link" href="{{route('setting_group.settings.index',$group->id)}}">{{$group->title}}</a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif
                <li class="nav-item mt-3">
                    <a class="nav-link" href="#">
                        <span class="shape1"></span>
                        <span class="shape2"></span>
                        <button class=" btn btn-danger logout mt-3"
                                style="width: 100%">
                            خروج
                        </button>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <!-- End Sidemenu -->		<!-- Main Header-->
    <div class="main-header side-header sticky">
        <div class="container-fluid">
            <div class="main-header-right">
                <a class="main-header-menu-icon" href="#" id="mainSidebarToggle"><span></span></a>
            </div>
            <div class="main-header-center">
                <div class="responsive-logo">
                    <a href="{{route('home')}}"><img src="{{url('logo/long.png')}}" class="mobile-logo" alt="لوگو" style="width: 134px; height: 37px"></a>
                    <a href="{{route('home')}}"><img src="{{url('logo/long.png')}}" class="mobile-logo-dark" alt="لوگو"></a>
                </div>
                <div class="input-group">
                    <div class="input-group-btn search-panel">
                        <select class="form-control select2-no-search">
                            <option label="دسته بندی ها">
                            </option>
                            <option value="IT Projects">
                                پروژه های IT
                            </option>
                            <option value="Business Case">
                                مورد تجاری
                            </option>
                            <option value="Microsoft Project">
                                پروژه مایکروسافت
                            </option>
                            <option value="Risk Management">
                                مدیریت ریسک
                            </option>
                            <option value="Team Building">
                                تیم سازی
                            </option>
                        </select>
                    </div>
                    <input type="search" class="form-control" placeholder="هر چیزی را جستجو کنید ...">
                    <button class="btn search-btn"><i class="fe fe-search"></i></button>
                </div>
            </div>
            <div class="main-header-right">
                <div class="dropdown header-search">
                    <a class="nav-link icon header-search">
                        <i class="fe fe-search header-icons"></i>
                    </a>
                    <div class="dropdown-menu">
                        <div class="main-form-search p-2">
                            <div class="input-group">
                                <div class="input-group-btn search-panel">
                                    <select class="form-control select2-no-search">
                                        <option label="دسته بندی ها">
                                        </option>
                                        <option value="IT Projects">
                                            پروژه های IT
                                        </option>
                                        <option value="Business Case">
                                            مورد تجاری
                                        </option>
                                        <option value="Microsoft Project">
                                            پروژه مایکروسافت
                                        </option>
                                        <option value="Risk Management">
                                            مدیریت ریسک
                                        </option>
                                        <option value="Team Building">
                                            تیم سازی
                                        </option>
                                    </select>
                                </div>
                                <input type="search" class="form-control" placeholder="هر چیزی را جستجو کنید ...">
                                <button class="btn search-btn"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dropdown d-md-flex">
                    <a class="nav-link icon full-screen-link" href="#">
                        <i class="fe fe-maximize fullscreen-button fullscreen header-icons"></i>
                        <i class="fe fe-minimize fullscreen-button exit-fullscreen header-icons"></i>
                    </a>
                </div>
                <div class="dropdown main-header-notification">
                    <a class="nav-link icon" href="#">
                        <i class="fe fe-bell header-icons"></i>
                        <span class="badge badge-danger nav-link-badge">4</span>
                    </a>
                    <div class="dropdown-menu">
                        <div class="header-navheading">
                            <p class="main-notification-text">شما 1 اعلان خوانده نشده <span class="badge badge-pill badge-primary mr-3">مشاهده همه</span></p>
                        </div>
                        <div class="main-notification-list">
                            <div class="media new">
                                <div class="main-img-user online"><img alt="آواتار" src="{{url('dashboard/assets/img/users/5.jpg')}}"></div>
                                <div class="media-body">
                                    <p>به <strong>اولیویا جیمز</strong> برای شروع الگوی جدید تبریک می گوییم</p><span>15 بهمن  12:32 بعد از ظهر</span>
                                </div>
                            </div>
                            <div class="media">
                                <div class="main-img-user"><img alt="آواتار" src="{{url('dashboard/assets/img/users/2.jpg')}}"></div>
                                <div class="media-body">
                                    <p><strong></strong>پیام جدید <strong>جوشوا گری</strong> دریافت شد</p><span>13 بهمن   02:56 صبح</span>
                                </div>
                            </div>
                            <div class="media">
                                <div class="main-img-user online"><img alt="آواتار" src="{{url('dashboard/assets/img/users/3.jpg')}}"></div>
                                <div class="media-body">
                                    <p><strong>الیزابت لوئیس</strong> برنامه جدیدی را به فروش مجدد اضافه کرد</p><span>12 بهمن  10:40 بعد از ظهر</span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-footer">
                            <a href="#">مشاهده همه اعلان ها</a>
                        </div>
                    </div>
                </div>
                <div class="main-header-notification">
                    <a class="nav-link icon" href="chat.html">
                        <i class="fe fe-message-square header-icons"></i>
                        <span class="badge badge-success nav-link-badge">6</span>
                    </a>
                </div>
                <div class="dropdown main-profile-menu">
                    <a class="d-flex" href="#">
                        <span class="main-img-user"><img alt="{{auth()->user()->name}}" src="{{url(auth()->user()->profile_image)}}"></span>
                    </a>
                    <div class="dropdown-menu">
                        <div class="header-navheading">
                            <h6 class="main-notification-title">{{auth()->user()->name}}</h6>
                            <p class="main-notification-text">
                                @if(auth()->user()->isSuperAdmin())
                                    مدیر ارشد وبسایت
                                @elseif(auth()->user()->isAdmin())
                                    {{auth()->user()->roles()->first()->name ?? ' - '}}
                                @else
                                    مشتری
                                @endif
                            </p>
                        </div>
                        <a class="dropdown-item border-top" href="profile.html">
                            <i class="fe fe-user"></i> پروفایل من
                        </a>
                        <a class="dropdown-item logout" href="#">
                            <i class="fe fe-power"></i> خروج از سیستم
                        </a>
                    </div>
                </div>

                <button class="navbar-toggler navresponsive-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent-4" aria-controls="navbarSupportedContent-4" aria-expanded="false" aria-label="تغییر پیمایش">
                    <i class="fe fe-more-vertical header-icons navbar-toggler-icon"></i>
                </button><!-- Navresponsive closed -->
            </div>
        </div>
    </div>
    <!-- End Main Header-->		<!-- Mobile-header -->
    <div class="mobile-main-header">
        <div class="mb-1 navbar navbar-expand-lg  nav nav-item  navbar-nav-right responsive-navbar navbar-dark  ">
            <div class="collapse navbar-collapse" id="navbarSupportedContent-4">
                <div class="d-flex order-lg-2 mr-auto">
                    <div class="dropdown header-search">
                        <a class="nav-link icon header-search">
                            <i class="fe fe-search header-icons"></i>
                        </a>
                        <div class="dropdown-menu">
                            <div class="main-form-search p-2">
                                <div class="input-group">
                                    <div class="input-group-btn search-panel">
                                        <select class="form-control select2-no-search">
                                            <option label="دسته بندی ها">
                                            </option>
                                            <option value="IT Projects">
                                                پروژه های IT
                                            </option>
                                            <option value="Business Case">
                                                مورد تجاری
                                            </option>
                                            <option value="Microsoft Project">
                                                پروژه مایکروسافت
                                            </option>
                                            <option value="Risk Management">
                                                مدیریت ریسک
                                            </option>
                                            <option value="Team Building">
                                                تیم سازی
                                            </option>
                                        </select>
                                    </div>
                                    <input type="search" class="form-control" placeholder="هر چیزی را جستجو کنید ...">
                                    <button class="btn search-btn"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown ">
                        <a class="nav-link icon full-screen-link">
                            <i class="fe fe-maximize fullscreen-button fullscreen header-icons"></i>
                            <i class="fe fe-minimize fullscreen-button exit-fullscreen header-icons"></i>
                        </a>
                    </div>
                    <div class="dropdown main-header-notification">
                        <a class="nav-link icon" href="#">
                            <i class="fe fe-bell header-icons"></i>
                            <span class="badge badge-danger nav-link-badge">4</span>
                        </a>
                        <div class="dropdown-menu">
                            <div class="header-navheading">
                                <p class="main-notification-text">شما 1 اعلان خوانده نشده <span class="badge badge-pill badge-primary mr-3">مشاهده همه</span></p>
                            </div>
                            <div class="main-notification-list">
                                <div class="media new">
                                    <div class="main-img-user online"><img alt="آواتار" src="{{url('dashboard/assets/img/users/5.jpg')}}"></div>
                                    <div class="media-body">
                                        <p>به <strong>اولیویا جیمز</strong> برای شروع الگوی جدید تبریک می گوییم</p><span>15 بهمن  12:32 بعد از ظهر</span>
                                    </div>
                                </div>
                                <div class="media">
                                    <div class="main-img-user"><img alt="آواتار" src="{{url('dashboard/assets/img/users/2.jpg')}}"></div>
                                    <div class="media-body">
                                        <p><strong></strong>پیام جدید <strong>جوشوا گری</strong> دریافت شد</p><span>13 بهمن   02:56 صبح</span>
                                    </div>
                                </div>
                                <div class="media">
                                    <div class="main-img-user online"><img alt="آواتار" src="{{url('dashboard/assets/img/users/3.jpg')}}"></div>
                                    <div class="media-body">
                                        <p><strong>الیزابت لوئیس</strong> برنامه جدیدی را به فروش مجدد اضافه کرد</p><span>12 بهمن  10:40 بعد از ظهر</span>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-footer">
                                <a href="#">مشاهده همه اعلان ها</a>
                            </div>
                        </div>
                    </div>
                    <div class="main-header-notification mt-2">
                        <a class="nav-link icon" href="chat.html">
                            <i class="fe fe-message-square header-icons"></i>
                            <span class="badge badge-success nav-link-badge">6</span>
                        </a>
                    </div>
                    <div class="dropdown main-profile-menu">
                        <a class="d-flex" href="#">
                            <span class="main-img-user"><img alt="آواتار" src="{{url('dashboard/assets/img/users/1.jpg')}}"></span>
                        </a>
                        <div class="dropdown-menu">
                            <div class="header-navheading">
                                <h6 class="main-notification-title">{{auth()->user()->name}}</h6>
                                <p class="main-notification-text">
                                    @if(auth()->user()->isSuperAdmin())
                                        مدیر ارشد وبسایت
                                    @elseif(auth()->user()->isAdmin())
                                        {{auth()->user()->roles()->first()->name ?? ' - '}}
                                    @else
                                        مشتری
                                    @endif
                                </p>
                            </div>
                            <a class="dropdown-item border-top" href="profile.html">
                                <i class="fe fe-user"></i> پروفایل من
                            </a>
                            <a class="dropdown-item logout" href="#">
                                <i class="fe fe-power"></i> خروج از سیستم
                            </a>
                        </div>
                    </div>
                    <div class="dropdown  header-settings">
                        <a href="#" class="nav-link icon" data-toggle="sidebar-left" data-target=".sidebar-left">
                            <i class="fe fe-align-left header-icons"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Mobile-header closed -->
    <!-- Main Content-->
    <div class="main-content side-content pt-0">
        <div class="container-fluid">
            <div class="inner-body">

                @yield('content')

            </div>
        </div>
    </div>
    <!-- End Main Content-->

    <!-- Main Footer-->
    <div class="main-footer text-center">
        <div class="container">
            <div class="row row-sm">
                <div class="col-md-12">
                    <span>کپی رایت © {{date('Y')}}  . طراحی شده توسط <a href="https://ultimatesoft.co">UltimateSoft Co</a> کلیه حقوق محفوظ است.</span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Page -->

<!-- Back-to-top -->
<a href="#top" id="back-to-top"><i class="fe fe-arrow-up"></i></a>

<!-- Include jQuery -->
<script src="{{asset('dashboard/assets/js/jquery-3.6.0.min.js')}}"></script>

<script src="{{ asset('image-uploader/dist/image-uploader.min.js') }}"></script>
<!-- Bootstrap js-->
<script src="{{asset('dashboard/assets/plugins/bootstrap/js/popper.min.js')}}"></script>
<script src="{{asset('dashboard/assets/plugins/bootstrap/js/bootstrap-rtl.js')}}"></script>

<!-- Perfect-scrollbar js -->
<script src="{{asset('dashboard/assets/plugins/perfect-scrollbar/perfect-scrollbar.min-rtl.js')}}"></script>

<!-- Sidemenu js -->
<script src="{{asset('dashboard/assets/plugins/sidemenu/sidemenu-rtl.js')}}"></script>

<!-- Sidebar js -->
<script src="{{asset('dashboard/assets/plugins/sidebar/sidebar-rtl.js')}}"></script>

<!-- Select2 js-->
<script src="{{asset('dashboard/assets/plugins/select2/js/select2.min.js')}}"></script>

<!-- Internal Chart.Bundle js-->
<script src="{{asset('dashboard/assets/plugins/chart.js/Chart.bundle.min.js')}}"></script>

<!-- Peity js-->
<script src="{{asset('dashboard/assets/plugins/peity/jquery.peity.min.js')}}"></script>

<!-- Internal Morris js -->
<script src="{{asset('dashboard/assets/plugins/raphael/raphael.min.js')}}"></script>
<script src="{{asset('dashboard/assets/plugins/morris.js/morris.min.js')}}"></script>

<!-- Circle Progress js-->
<script src="{{asset('dashboard/assets/js/circle-progress.min.js')}}"></script>


<!-- Sticky js -->
<script src="{{asset('dashboard/assets/js/sticky.js')}}"></script>

<!-- Custom js -->
<script src="{{asset('dashboard/assets/js/custom.js')}}"></script>

<!-- Switcher js -->
<script src="{{asset('dashboard/assets/switcher/js/switcher-rtl.js')}}"></script>

<!-- jQuery -->
<script src="{{asset('dashboard/assets/plugins/datatable/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('dashboard/assets/plugins/datatable/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{asset('dashboard/assets/plugins/datatable/dataTables.responsive.min.js')}}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    $(function () {
        $("#table").DataTable({
            "responsive": true,
            "autoWidth": false,
            "rtl" : true,
            "language": {
                "paginate": {
                    "previous": "قبلی",
                    "next" : "بعدی"
                },
                "sLengthMenu": "تعداد رکورد در صفحه _MENU_ ",
                "sSearch" : "جستجو:",
                "emptyTable":     "هیچ داده ای برای نمایش موجود نیست",
                "info":           "نمایش _START_ تا _END_ از _TOTAL_ رکورد",
                "infoEmpty":      "نمایش 0 تا 0 از 0 رکورد",

                "infoFiltered":   "(نتیجه جستجو بین _MAX_ رکورد)",
                "zeroRecords":    "اطلاعاتی مبنی بر جستجو شما موجود نیست...",
            },



        });
    });
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })

</script>
<!-- CK Editor for all textarea -->

<!-- Page script -->
<script>

    $(document).ready(function() {
        $('.js-example-basic-single').select2();
    });
    $(document).ready(function() {
        $('.s2').select2();
    });
</script>
@foreach ($errors->all() as $error)
    <script>
        toastr.error('{{$error}}')
    </script>
@endforeach


<script>
    const createAttributeRow = (attributeId, name, value, index,prefix,postfix) => `
    <div class="attribute-row row mt-2 mb-2">
        <div class="col-5">
            <input type="text" class="form-control attribute-name"
                   name="attributes[${index}][name]"
                   data-attribute-id="${attributeId || ''}"
                   value="${name || ''}"
                   placeholder="نام ویژگی"
                   ${attributeId ? 'readonly' : ''}>
        </div>
        <div class="col-6 d-flex nowrap ">
        ${prefix}
            <input type="text" class="form-control attribute-value"
                   name="attributes[${index}][value]"
                   value="${value || ''}"
                   placeholder="مقدار ویژگی">${postfix}
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-danger btn-sm remove-btn" onclick="removeAttributeRow(this)">-</button>
        </div>
    </div>
`;

    const addAddButton = () => {
        // const addButton = $('<button type="button" class="btn btn-success btn-sm mt-2 mb-2">+ افزودن ویژگی</button>');
        // addButton.on('click', () => addAttributeInput(null, '', '', $('.attribute-row').length));
        // $('#attributeInputs').append(addButton);
    };

    const addAttributeInput = (attributeId, name, value, index,prefix,postfix) => {
        $('#attributeInputs').append(createAttributeRow(attributeId, name, value, index,prefix,postfix));
    };

    const loadAttributes = (attributes, attributeValues) => {
        attributes.forEach((attr, index) => {
            const value = attributeValues.find(val => val.attribute_id === attr.id)?.value || '';
            addAttributeInput(attr.id, attr.name, value, index,attr.prefix_sentence,attr.postfix_sentence);
        });
    };

</script>


@yield('js')
@stack('scripts')

<script>

    function hideId(id){
        $(id).hide()
    }
    function showId(id){
        $(id).show()
    }
    function hideButtonAndShowTab(id){
        hideId(id)
        showId(id+'-tab')
    }
    function hideTabAndShowButton(id){
        showId(id)
        hideId(id+'-tab')
    }
    $(document).ready(function() {
        $('.js-example-basic-single').select2();
    });

    $(document).ready(function() {
        $('.s2').select2();
    });

    $('.logout').on('click',function(){
        event.preventDefault();
        document.getElementById('logout-form').submit();
    })

    $(':file').on('change',function(){
        //get the file name

        toastr.success('فایل با موفقیت انتخاب شد')
        var fileName = $(this).val();
        //replace the "Choose a file" label
        $(this).next('.custom-file-label').html(fileName);
        $(this).next('.custom-file-label').attr('class','custom-file-label text-left');


    })


    // Helper function to remove commas from formatted numbers
    function removeCommas(value) {
        return value.replace(/,/g, '');
    }

    // Helper function to format numbers with commas
    function formatNumberWithCommas(value) {
        return value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // Function to set the initial value for the hidden input
    function setInitialHiddenValue(numberInput, hiddenInput) {
        let inputValue = removeCommas(numberInput.value); // Get the raw value without commas
        if (!isNaN(inputValue) && inputValue !== '') {
            numberInput.value = formatNumberWithCommas(inputValue); // Format the value in the visible input
            hiddenInput.value = inputValue; // Set the raw value in the hidden input
        }
    }

    // Apply to all number inputs in the document
    document.querySelectorAll('input[type="number"]').forEach(function (numberInput) {
        // Create a hidden input to store the actual number
        let hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = numberInput.name;  // Use the same name as the original input
        numberInput.name = ''; // Remove the name from the visible input to avoid duplicate submission
        hiddenInput.classList.add('hidden-number-input'); // Optional: For styling or future access
        numberInput.parentNode.insertBefore(hiddenInput, numberInput.nextSibling); // Place the hidden input after the visible one

        // Set the input type to 'text' for formatting purposes
        numberInput.type = 'text';

        // Set the initial value for edit pages or when the form is pre-populated
        setInitialHiddenValue(numberInput, hiddenInput);

        // Handle input event for number formatting
        numberInput.addEventListener('input', function (e) {
            let inputValue = removeCommas(e.target.value); // Get raw value by removing commas
            if (!isNaN(inputValue) && inputValue !== '') {
                e.target.value = formatNumberWithCommas(inputValue); // Display formatted number
                hiddenInput.value = inputValue; // Set raw number in hidden input
            } else {
                hiddenInput.value = ''; // Clear hidden input if invalid
            }
        });

        // Update the hidden input on blur (when the user leaves the input)
        numberInput.addEventListener('blur', function (e) {
            let inputValue = removeCommas(e.target.value);
            hiddenInput.value = inputValue; // Ensure hidden input is updated on blur
        });

        // Handle form submission to update hidden inputs before submission
        document.querySelector('form').addEventListener('submit', function (e) {
            let inputValue = removeCommas(numberInput.value);
            hiddenInput.value = inputValue; // Ensure hidden input is correctly set on form submit
        });
    });



</script>
<!-- Include Morilog Persian Date Picker JS -->
<script src="https://unpkg.com/persian-date@1.1.0/dist/persian-date.js"></script>
<script src="https://unpkg.com/persian-datepicker@1.2.0/dist/js/persian-datepicker.js"></script>

<script>
    function unixToDatetimeLocal(unixTimestamp) {
        // Convert the Unix timestamp (seconds) to milliseconds
        const date = new Date(unixTimestamp * 1000);

        // Extract year, month, day, hour, and minute
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are zero-indexed
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

        // Return in the 'YYYY-MM-DDTHH:MM' format
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }
    // Convert Persian date to Unix timestamp (in seconds)
    function persianDateToUnix(element) {
        // alert("Hi")
        let val = $(element).val(); // Get the value from the hidden input or the altField
        if (val) {
            $(element).val(unixToDatetimeLocal(val/1000)); // Set the value in the hidden input as Unix timestamp
        }
    }

    $(document).ready(function() {
        var dateInputs = document.querySelectorAll('input[type="date"]');
        var counter = 1;

        dateInputs.forEach(function(dateInput) {
            dateInput.type = 'text';
            var oldname = dateInput.name;
            var observer_id = `date_picker_observer_${counter}`;
            dateInput.name = '_' + oldname; // Rename the original input to avoid form submission
            dateInput.classList.add('persian_date');

            // Add a hidden input to store the Unix timestamp
            $(dateInput).parent().append(`
            <input type="hidden" name="${oldname}" id="${observer_id}" />
        `);

            // Initialize the Persian date picker
            $(dateInput).pDatepicker({
                observer: true,
                initialValue: !!dateInput.value, // Check if there is an initial value
                format: 'YYYY/MM/DD',
                altField: "#" + observer_id, // Use the hidden input as altField
                initialValueType: 'gregorian', // Initialize the picker with Gregorian date
                calendar:{
                    persian: {
                        leapYearMode: 'astronomical'
                    }
                },
                onSelect: function() {
                    persianDateToUnix(`#${observer_id}`); // Convert selected Persian date to Unix timestamp
                }
            });

            // If there's an initial value, manually trigger the conversion
            if (dateInput.value) {
                let observer = $(`#${observer_id}`);
                persianDateToUnix(observer);
            }

            counter++;
        });

        var dateTimeInputs = document.querySelectorAll('input[type="datetime-local"]');
        var _counter = 1;

        dateTimeInputs.forEach(function(dateInput) {
            dateInput.type = 'text';
            var _oldname = dateInput.name;
            var _observer_id = `date_picker_observer_${_counter}`;
            dateInput.name = '_' + _oldname; // Rename the original input to avoid form submission
            dateInput.classList.add('persian_date');

            // Add a hidden input to store the Unix timestamp
            $(dateInput).parent().append(`
            <input type="hidden" name="${_oldname}" id="${_observer_id}" />
        `);

            // Initialize the Persian datetime picker with time picker enabled
            $(dateInput).pDatepicker({
                observer: true,
                initialValue: !!dateInput.value, // Check if there is an initial value
                altField: "#" + _observer_id, // Use the hidden input as altField
                initialValueType: 'gregorian', // Initialize the picker with Gregorian date
                timePicker: {
                    enabled: true,
                    meridiem: {
                        enabled: true
                    }
                },
                calendar:{
                    persian: {
                        leapYearMode: 'astronomical'
                    }
                },
                onSelect: function() {
                    persianDateToUnix(`#${_observer_id}`); // Convert selected Persian datetime to Unix timestamp
                }
            });

            // If there's an initial value, manually trigger the conversion
            if (dateInput.value) {
                let observer = $(`#${_observer_id}`);
                persianDateToUnix(observer);
            }

            _counter++;
        });
    });


</script>
<script>
    $(document).ready(function() {
        $('.copy-btn').click(function() {
            const copyText = $(this).closest('.form-group').find('.copy-input').val();

            navigator.clipboard.writeText(copyText).then(() => {
                alert("متن با موفقیت کپی شد");
            }).catch(err => {
                console.error("مشکل در خطا : ", err);
            });
        });
    });
</script>
<script>

    function doCkEditor(){
        $("textarea").not(".no_ck_editor").each(function() {
            var editor = CKEDITOR.replace(this, {
                filebrowserImageBrowseUrl: '{{ url("/filemanager?type=Images") }}',
                filebrowserImageUploadUrl: '{{ url("/filemanager/upload?type=Images&_token=") }}{{ csrf_token() }}',
                filebrowserBrowseUrl: '{{ url("/filemanager?type=Files") }}',
                filebrowserUploadUrl: '{{ url("/filemanager/upload?type=Files&_token=") }}{{ csrf_token() }}',
                filebrowserWindowWidth: '900',
                filebrowserWindowHeight: '600'
            });
        });
    }

    doCkEditor()
</script>


<script>
    function makeFormsAjax() {
        document.querySelectorAll('.ajax-form').forEach(function (form) {
            // Prevent attaching multiple listeners
            if (form.dataset.ajaxBound === 'true') return;

            form.dataset.ajaxBound = 'true'; // Mark as bound

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(form);
                const action = form.getAttribute('action');
                const modalId = form.dataset.id;
                const method = form.dataset.method || 'POST';
                const submitButton = form.querySelector('button[type="submit"]');

                submitButton.disabled = true;
                submitButton.innerText = 'لطفاً صبر کنید...';

                if (method !== 'POST') {
                    formData.append('_method', method);
                }

                fetch(action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                    .then(async response => {
                        const data = await response.json();

                        if (!response.ok) {
                            // Laravel validation or other server-side error
                            if (data.errors) {
                                // Show first validation error
                                for (let field in data.errors) {
                                    toastr.error(data.errors[field][0]);
                                }
                            } else {
                                toastr.warning(data.message || 'خطا در ارسال فرم');
                            }
                            throw new Error(); // Stop further execution (e.g. not closing modal)
                        }

                        // ✅ Success
                        toastr.success(data.message || 'با موفقیت انجام شد');

                        // Close modal
                        $('#'+modalId).modal('hide');
                        reloadTables();
                        $(form).closest('.modal').modal('hide');

                        // Clear form if create-form
                        if (form.classList.contains('create-form')) {
                            form.querySelectorAll('input, textarea, select').forEach(el => {
                                if (el.type === 'hidden' || el.type === 'submit' || el.type === 'button') return;

                                switch (el.type) {
                                    case 'checkbox':
                                    case 'radio':
                                        el.checked = false;
                                        break;
                                    default:
                                        if (el.tagName.toLowerCase() === 'select') {
                                            el.selectedIndex = 0;
                                        } else {
                                            el.value = '';
                                        }
                                }
                            });
                        }

                    })
                    .catch(error => {
                        if (error.message) {
                            // If the error was manually thrown above, it will be caught here but ignored.
                            return;
                        }
                        toastr.error('خطای غیرمنتظره‌ای رخ داد');
                    })
                    .finally(() => {
                        submitButton.disabled = false;
                        submitButton.innerText = method === 'DELETE' ? 'حذف' : (method === 'PUT' ? 'ویرایش' : 'ایجاد');
                    });

            });
        });
    }
    document.addEventListener('DOMContentLoaded', makeFormsAjax);
</script>

<script>
    function reloadTables() {
        window.refreshTable()
        // Get all table elements on the page
        const tables = document.getElementsByTagName('table');
        // Iterate through each table
        Array.from(tables).forEach(table => {
            const url = table.getAttribute('data-url');
            if (url) {
                const parentDiv = table.parentNode;
                parentDiv.innerHTML = ''; // Clear the parent div
                // Make GET request
                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text(); // Get response body as text
                    })
                    .then(data => {
                        parentDiv.innerHTML = data; // Set response body as innerHTML
                        makeFormsAjax()
                        doCkEditor()
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        parentDiv.innerHTML = '<p>خطا در بارگذاری اطلاعات</p>';
                    });
            }
        });

    }
</script>
<script>
    function confirmation() {
        let text = "آیا از عملیات درحال انجام اطمینان دارید ؟";
        return confirm(text);
        // ✅ if user presses OK → returns true → form submits
        // ❌ if user presses Cancel → returns false → form won't submit
    }
</script>

<script>
    // Initialize Bootstrap tooltips for etiket codes
    $(document).ready(function() {
        // Initialize tooltips on page load
        initializeEtiketTooltips();
        
        // Re-initialize tooltips after AJAX updates (for DataTables)
        if (typeof window.refreshTable !== 'undefined') {
            var originalRefreshTable = window.refreshTable;
            window.refreshTable = function() {
                originalRefreshTable();
                setTimeout(initializeEtiketTooltips, 500);
            };
        }
        
        // Re-initialize after any DataTable draw event
        $(document).on('draw.dt', function() {
            setTimeout(initializeEtiketTooltips, 100);
        });
    });
    
    function initializeEtiketTooltips() {
        // Destroy existing tooltips to avoid duplicates
        $('[data-toggle="tooltip"]').tooltip('dispose');
        
        // Initialize new tooltips
        $('[data-toggle="tooltip"]').tooltip({
            container: 'body',
            trigger: 'hover',
            boundary: 'window'
        });
    }
</script>

@yield('scripts')

</body>
</html>
