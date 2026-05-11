@extends('layouts.panel')
@section('content')
    <x-breadcrumb :title="'مدیریت اتیکت‌های جامع'" :items="[
        ['label' => 'خانه', 'url' => route('home')],
        ['label' => 'محصولات جامع', 'url' => route('products.products_comprehensive')],
        ['label' => $product->name],
        ['label' => 'مدیریت اتیکت‌ها']
    ]" />

    <x-page>
        <x-slot name="header">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <h5 class="mb-2">
                    اتیکت‌های جامع محصول:
                    <span class="text-primary">{{ $product->name }}</span>
                </h5>
                <a href="{{ route('products.products_comprehensive') }}" class="btn btn-secondary mb-2">
                    بازگشت به لیست محصولات جامع
                </a>
            </div>
        </x-slot>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        @php
            $comprehensiveEtikets = $product->etikets;
        @endphp

        @if($comprehensiveEtikets->isEmpty())
            <div class="alert alert-warning">
                برای این محصول جامع هنوز هیچ اتیکت جامع ثبت نشده است.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>کد اتیکت جامع</th>
                        <th>وضعیت موجودی</th>
                        <th>تعداد اتیکت‌های real مرتبط</th>
                        <th style="width: 140px;">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($comprehensiveEtikets as $etiket)
                        @php
                            $relatedEtikets = $etiket->comprehensiveEtikets->pluck('relatedEtiket')->filter();
                        @endphp
                        <tr>
                            <td>{{ $etiket->code }}</td>
                            <td>
                                @if((int) $etiket->effective_is_mojood === 1)
                                    <span class="badge badge-success">موجود</span>
                                @else
                                    <span class="badge badge-danger">ناموجود</span>
                                @endif
                            </td>
                            <td>{{ $relatedEtikets->count() }}</td>
                            <td>
                                <form action="{{ route('products.comprehensive_etikets.destroy', ['product' => $product->id, 'etiket' => $etiket->id]) }}"
                                      method="POST"
                                      onsubmit="return confirm('از حذف این اتیکت جامع مطمئن هستید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">حذف اتیکت</button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <div class="p-2 bg-light border rounded">
                                    <strong>اتیکت‌های real مرتبط:</strong>
                                    @if($relatedEtikets->isEmpty())
                                        <div class="text-muted mt-2">هیچ اتیکت real برای این اتیکت جامع ثبت نشده است.</div>
                                    @else
                                        <div class="table-responsive mt-2">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="thead-light">
                                                <tr>
                                                    <th>کد اتیکت</th>
                                                    <th>نام محصول</th>
                                                    <th>وزن</th>
                                                    <th>قیمت</th>
                                                    <th>وضعیت</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($relatedEtikets as $realEtiket)
                                                    <tr>
                                                        <td>{{ $realEtiket->code }}</td>
                                                        <td>{{ $realEtiket->product->name ?? '-' }}</td>
                                                        <td>{{ $realEtiket->weight }} گرم</td>
                                                        <td>{{ number_format((int) ($realEtiket->price / 10)) }} تومان</td>
                                                        <td>
                                                            @if((int) $realEtiket->effective_is_mojood === 1)
                                                                <span class="badge badge-success">موجود</span>
                                                            @else
                                                                <span class="badge badge-danger">ناموجود</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-page>
@endsection
