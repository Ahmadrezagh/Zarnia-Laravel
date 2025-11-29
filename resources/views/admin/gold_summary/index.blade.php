@extends('layouts.panel')
@section('content')
    <x-breadcrumb :title="'خلاصه گردش طلا'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'خلاصه گردش طلا']
      ]" />

    <x-page>
        <x-slot name="header">
            <h5>خلاصه گردش طلا</h5>
            <p class="text-muted">
                محاسبه فروش طلا به صورت وزنی - همه سفارشات موفق
                @if(request('from_date') || request('to_date'))
                    <br>
                    <span class="badge badge-info mt-2">
                        <i class="fas fa-filter"></i> فیلتر فعال
                        @if(request('from_date'))
                            از: {{ \Morilog\Jalali\Jalalian::forge(request('from_date'))->format('Y/m/d H:i') }}
                        @endif
                        @if(request('to_date'))
                            تا: {{ \Morilog\Jalali\Jalalian::forge(request('to_date'))->format('Y/m/d H:i') }}
                        @endif
                    </span>
                @endif
            </p>
            <hr>

            <!-- Date Filters -->
            <form method="GET" action="{{ route('gold_summary.index') }}" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <x-form.input 
                            title="از تاریخ" 
                            name="from_date" 
                            type="datetime-local" 
                            :value="request('from_date')" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input 
                            title="تا تاریخ" 
                            name="to_date" 
                            type="datetime-local" 
                            :value="request('to_date')" />
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary ml-2">
                            <i class="fas fa-filter"></i> اعمال فیلتر
                        </button>
                        @if(request('from_date') || request('to_date'))
                            <a href="{{ route('gold_summary.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> حذف فیلتر
                            </a>
                        @endif
                    </div>
                </div>
            </form>
            <hr>

            <!-- Summary Cards -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6>مجموع وزن طلا</h6>
                            <h4>{{ $summary['total_weight'] }}</h4>
                            <small>از همه سفارشات</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6>مجموع ریالی</h6>
                            <h4>{{ $summary['total_amount'] }}</h4>
                            <small>از همه سفارشات</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6>میانگین درصد خرید</h6>
                            <h4>{{ $summary['avg_purchase_percentage'] }}</h4>
                            <small>از همه سفارشات</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6>میانگین فروش - خرید</h6>
                            <h4>{{ $summary['percentage_difference'] }}</h4>
                            <small>از همه سفارشات</small>
                        </div>
                    </div>
                </div>
            </div>
        </x-slot>

        <div class="table-responsive">
            <table id="gold-summary-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>شناسه سفارش</th>
                        <th>کد پیگیری</th>
                        <th>تاریخ</th>
                        <th>وزن طلا (گرم)</th>
                        <th>مبلغ (تومان)</th>
                        <th>جمع تجمعی (تومان)</th>
                        <th>کارمزد خرید (گرم)</th>
                        <th>کارمزد فروش (گرم)</th>
                        <th>مبلغ مجموع تخفیف</th>
                        <th>تخفیف به گرم طلا</th>
                    </tr>
                </thead>
                <style>
                    #gold-summary-table tbody tr {
                        cursor: pointer;
                    }
                    #gold-summary-table tbody tr:hover {
                        background-color: #f8f9fa !important;
                    }
                </style>
                <tbody>
                    @php $cumulativeAmount = 0; @endphp
                    @foreach($orders as $order)
                        @php
                            $orderWeight = 0;
                            $orderAmount = 0;
                            $orderPurchaseWeightSum = 0;
                            $orderSaleWeightSum = 0;

                            foreach ($order->orderItems as $item) {
                                if ($item->product) {
                                    $itemWeight = floatval($item->product->weight ?? 0) * intval($item->count);
                                    $itemAmount = floatval($item->price) * intval($item->count);
                                    
                                    $darsadKharid = floatval($item->product->darsad_kharid ?? 0);
                                    $purchaseCommissionGrams = ($itemWeight * $darsadKharid) / 100;
                                    
                                    $ojrat = floatval($item->product->ojrat ?? 0);
                                    
                                    $orderWeight += $itemWeight;
                                    $orderAmount += $itemAmount;
                                    $orderPurchaseWeightSum += $purchaseCommissionGrams;
                                    $orderSaleWeightSum += ($itemWeight * $ojrat) / 100;
                                }
                            }

                            // Calculate discount per gram of gold
                            $totalDiscount = floatval($order->discount_price ?? 0);
                            $firstMazaneh = null;
                            
                            // Sum discounted_price from all order items' products
                            foreach ($order->orderItems as $item) {
                                if ($item->product) {
                                    $productDiscountedPrice = floatval($item->product->discounted_price ?? 0);
                                    if ($productDiscountedPrice > 0) {
                                        $totalDiscount += $productDiscountedPrice * intval($item->count);
                                    }
                                    
                                    // Find first mazaneh that is not 0
                                    if ($firstMazaneh === null && $item->product->mazaneh && floatval($item->product->mazaneh) != 0) {
                                        $firstMazaneh = floatval($item->product->mazaneh);
                                    }
                                }
                            }
                            
                            $discountPerGram = 0;
                            if ($firstMazaneh !== null && $firstMazaneh > 0) {
                                $discountPerGram = ($totalDiscount / $firstMazaneh) * 10;
                            }

                            $cumulativeAmount += $orderAmount;
                        @endphp
                        <tr style="cursor: pointer;" onclick="window.open('{{ route('gold_summary.show', $order->id) }}', '_blank')">
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->transaction_id ?? '-' }}</td>
                            <td>{{ $order->createdAtJalali }}</td>
                            <td>{{ number_format($orderWeight, 3) }}</td>
                            <td>{{ number_format($orderAmount) }}</td>
                            <td>{{ number_format($cumulativeAmount) }}</td>
                            <td>{{ number_format($orderPurchaseWeightSum, 3) }}</td>
                            <td>{{ number_format($orderSaleWeightSum, 3) }}</td>
                            <td>{{ number_format($totalDiscount) }}</td>
                            <td>{{ $firstMazaneh !== null && $firstMazaneh > 0 ? number_format($discountPerGram, 3) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold bg-info text-white">
                        <td colspan="3">جمع کل صفحه جاری</td>
                        <td>{{ $summary['page_weight'] }}</td>
                        <td>{{ $summary['page_amount'] }}</td>
                        <td>-</td>
                        <td>{{ $summary['page_purchase_commission'] }}</td>
                        <td>{{ $summary['page_sale_commission'] }}</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                    <tr class="font-weight-bold bg-primary text-white">
                        <td colspan="3">جمع کل همه سفارشات</td>
                        <td>{{ $summary['total_weight'] }}</td>
                        <td>{{ $summary['total_amount'] }}</td>
                        <td>{{ $summary['total_amount'] }}</td>
                        <td>{{ $summary['total_purchase_commission'] }}</td>
                        <td>{{ $summary['total_sale_commission'] }}</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if(method_exists($orders, 'links'))
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <span class="text-muted">
                            نمایش {{ $orders->firstItem() ?? 0 }} تا {{ $orders->lastItem() ?? 0 }} 
                            از {{ $orders->total() }} سفارش
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-end">
                        {{ $orders->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        @endif
    </x-page>

    <script>
        $(document).ready(function() {
            // Simple DataTable for sorting and searching only
            $('#gold-summary-table').DataTable({
                paging: false, // Use Laravel pagination instead
                searching: true,
                ordering: true,
                info: false,
                language: {
                    search: "جستجو:",
                    zeroRecords: "رکوردی یافت نشد",
                }
            });

            // Make table rows clickable (for DataTables rows)
            $('#gold-summary-table tbody').on('click', 'tr', function() {
                var orderId = $(this).find('td:first-child').text().trim();
                if (orderId && !isNaN(orderId)) {
                    var url = '{{ route("gold_summary.show", ":id") }}'.replace(':id', orderId);
                    window.open(url, '_blank');
                }
            });
        });
    </script>

@endsection

