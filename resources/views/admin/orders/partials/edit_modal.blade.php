@php
    $order = $order ?? null;
    if (!$order) return;
@endphp
<div class="modal fade" id="modal-edit-{{ $order->id }}" tabindex="-1" aria-labelledby="modal-edit-{{ $order->id }}-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-edit-{{ $order->id }}-label">ویرایش سفارش</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin_orders.update', $order->id) }}" method="post" enctype="multipart/form-data" class="ajax-form"
                  data-id="modal-edit-{{ $order->id }}"
                  data-method="PUT">
                <div class="modal-body">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" value="{{ $order->id }}">
                    <div class="row">
                        <x-form.input col="col-6" title="شماره سفارش" :value="$order->id" name="none" />
                        <x-form.input col="col-6" title="نام سفارش" :value="$order->userName" name="none" />
                        <x-form.input col="col-6" title="شیوه ارسال" :value="$order->shippingName" name="none" />
                        <x-form.input col="col-6" title="بازه زمانی ارسال" :value="$order->shippingTimeName" name="none" />
                        <x-form.input col="col-6" title="درگاه پرداخت" :value="$order->gatewayName" name="none" />
                        <x-form.input col="col-6" title="زمان پرداخت" :value="$order->paid_at" name="none" />
                        <x-form.input col="col-6" title="کد تخفیف" :value="$order->discount_code" name="none" />
                        <x-form.input col="col-6" title="درصد تخفیف" :value="$order->discount_percentage" name="none" />
                        <x-form.input col="col-6" title="مبلغ تخفیف" :value="$order->discount_price" name="none" />
                        <x-form.input col="col-6" title="مبلغ" :value="$order->total_amount" name="none" />
                        <x-form.input col="col-6" title="هزینه ارسال" :value="$order->shippingPrice" name="none" />
                        <x-form.input col="col-6" title="مبلغ قابل پرداخت" :value="$order->finalPrice" name="none" />
                        <x-form.input col="col-6" title="Payment Token" :value="$order->payment_token" name="none" />
                        <x-form.input col="col-6" title="Transaction ID" :value="$order->transaction_id" name="none" />
                        <input type="hidden" name="updated_items" id="updated-items-{{ $order->id }}">

                        <div class="table-responsive col-12">
                            <table class="table-bordered" style="width: 100%">
                                <thead>
                                    <tr class="text-center">
                                        <th>محصول</th>
                                        <th>تعداد</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody class="order-items-table">
                                    @foreach($order->orderItems as $orderItem)
                                        <tr class="text-center"
                                            data-id="{{ $orderItem->id }}"
                                            data-order-id="{{ $order->id }}">
                                            <td>{{ $orderItem->product->name ?? '-' }}</td>
                                            <td class="item-count">{{ $orderItem->count }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-warning decrease-count" data-order-id="{{ $order->id }}">-</button>
                                                <button type="button" class="btn btn-sm btn-danger delete-item" data-order-id="{{ $order->id }}">حذف</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" onclick="return confirmation()">ویرایش</button>
                </div>
            </form>
        </div>
    </div>
</div>
