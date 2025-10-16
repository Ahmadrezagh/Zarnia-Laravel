@extends('layouts.panel')
@section('content')
    <x-breadcrumb :title="'خلاصه گردش طلا'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'خلاصه گردش طلا']
      ]" />

    <x-page>
        <x-slot name="header">
            <h5>خلاصه گردش طلا</h5>
            <p class="text-muted">محاسبه فروش طلا به صورت وزنی - همه سفارشات موفق</p>
            <hr>

            <!-- Summary Cards -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6>مجموع وزن طلا</h6>
                            <h4>{{ $summary['total_weight'] }} گرم</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6>مجموع ریالی</h6>
                            <h4>{{ $summary['total_amount'] }} تومان</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6>میانگین درصد خرید</h6>
                            <h4>{{ $summary['avg_purchase_percentage'] }}%</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6>میانگین فروش - خرید</h6>
                            <h4>{{ $summary['percentage_difference'] }}%</h4>
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
                    </tr>
                </thead>
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

                            $cumulativeAmount += $orderAmount;
                        @endphp
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->transaction_id ?? '-' }}</td>
                            <td>{{ $order->createdAtJalali }}</td>
                            <td>{{ number_format($orderWeight, 3) }}</td>
                            <td>{{ number_format($orderAmount) }}</td>
                            <td>{{ number_format($cumulativeAmount) }}</td>
                            <td>{{ number_format($orderPurchaseWeightSum, 3) }}</td>
                            <td>{{ number_format($orderSaleWeightSum, 3) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold bg-light">
                        <td colspan="3">جمع کل</td>
                        <td>{{ $summary['total_weight'] }}</td>
                        <td>{{ $summary['total_amount'] }}</td>
                        <td>{{ $summary['total_amount'] }}</td>
                        <td>{{ $summary['total_purchase_commission'] }}</td>
                        <td>{{ $summary['total_sale_commission'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if(method_exists($orders, 'links'))
            <div class="d-flex justify-content-center mt-3">
                {{ $orders->links() }}
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
        });
    </script>

@endsection

