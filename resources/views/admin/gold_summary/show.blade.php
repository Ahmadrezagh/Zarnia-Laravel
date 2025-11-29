@extends('layouts.panel')
@section('content')
    <x-breadcrumb :title="'جزئیات محاسبه سفارش #' . $order->id" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'خلاصه گردش طلا', 'url' => route('gold_summary.index')],
            ['label' => 'جزئیات سفارش #' . $order->id]
      ]" />

    <x-page>
        <x-slot name="header">
            <h5>جزئیات محاسبه سفارش #{{ $order->id }}</h5>
            <p class="text-muted">
                <strong>کد پیگیری:</strong> {{ $order->transaction_id ?? '-' }} | 
                <strong>تاریخ:</strong> {{ $order->createdAtJalali }} | 
                <strong>وضعیت:</strong> {{ $order->persian_status }}
            </p>
            @if($order->user)
                <p class="text-muted">
                    <strong>مشتری:</strong> {{ $order->user->name }} | 
                    <strong>تلفن:</strong> {{ $order->user->phone }}
                </p>
            @endif
            <hr>
        </x-slot>

        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6>مجموع وزن طلا</h6>
                        <h4>{{ number_format($summary['total_weight'], 3) }} گرم</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6>مجموع ریالی</h6>
                        <h4>{{ number_format($summary['total_amount']) }} تومان</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6>میانگین درصد خرید</h6>
                        <h4>{{ number_format($summary['avg_purchase_percentage'], 2) }}%</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6>میانگین درصد فروش</h6>
                        <h4>{{ number_format($summary['avg_sale_percentage'], 2) }}%</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items Details Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>نام محصول</th>
                        <th>تعداد</th>
                        <th>وزن واحد (گرم)</th>
                        <th>وزن کل (گرم)</th>
                        <th>قیمت واحد (تومان)</th>
                        <th>قیمت کل (تومان)</th>
                        <th>درصد خرید</th>
                        <th>کارمزد خرید (گرم)</th>
                        <th>درصد فروش</th>
                        <th>کارمزد فروش (گرم)</th>
                        <th>مبلغ مجموع تخفیف</th>
                        <th>مزنه</th>
                        <th>تخفیف به گرم طلا</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item['product_name'] }}</td>
                            <td>{{ number_format($item['count']) }}</td>
                            <td>{{ number_format($item['unit_weight'], 3) }}</td>
                            <td>{{ number_format($item['total_weight'], 3) }}</td>
                            <td>{{ number_format($item['unit_price']) }}</td>
                            <td>{{ number_format($item['total_amount']) }}</td>
                            <td>{{ number_format($item['purchase_percentage'], 2) }}%</td>
                            <td>{{ number_format($item['purchase_commission_grams'], 3) }}</td>
                            <td>{{ number_format($item['sale_percentage'], 2) }}%</td>
                            <td>{{ number_format($item['sale_commission_grams'], 3) }}</td>
                            <td>{{ number_format($item['total_discount']) }}</td>
                            <td>{{ $item['mazaneh'] !== null ? number_format($item['mazaneh'] / 10, 3) : '-' }}</td>
                            <td>{{ $item['mazaneh'] !== null && $item['mazaneh'] > 0 ? number_format($item['discount_per_gram'], 3) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold bg-primary text-white">
                        <td colspan="3">جمع کل</td>
                        <td>{{ number_format($summary['total_weight'], 3) }} گرم</td>
                        <td>-</td>
                        <td>{{ number_format($summary['total_amount']) }} تومان</td>
                        <td>-</td>
                        <td>{{ number_format($summary['total_purchase_commission'], 3) }} گرم</td>
                        <td>-</td>
                        <td>{{ number_format($summary['total_sale_commission'], 3) }} گرم</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-3">
            <a href="{{ route('gold_summary.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> بازگشت به لیست
            </a>
        </div>
    </x-page>

@endsection

