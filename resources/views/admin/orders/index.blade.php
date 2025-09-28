@extends('layouts.panel')
@section('content')
    <x-breadcrumb :title="'سفارش ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'سفارشات']
      ]" />

    <x-page>
        <x-slot name="header">
            <h5>سفارشات</h5>
            <hr>
            <form  method="GET">
                <x-form.input title="کد پیگیری" name="transaction_id" id="transaction_id" />
                <button class="btn btn-success" type="button" onclick="filterTransaction()" >فیلتر</button>
            </form>
{{--            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن سفارش</button>--}}
{{--            <x-modal.create id="modal-create" title="ساخت سفارش" action="{{route('admin_orders.store')}}" >--}}
{{--                <x-form.input title="نام"  name="name" />--}}
{{--                <x-form.input title="جمله پیشوند"  name="prefix_sentence" />--}}
{{--                <x-form.input title="جمله پسوند"  name="postfix_sentence" />--}}
{{--            </x-modal.create>--}}
        </x-slot>

        <x-dataTable
            :url="route('table.orders')"
            id="orders-table"
            hasCheckbox="true"
            :columns="[
                            ['label' => 'سفارش', 'key' => 'orderColumn', 'type' => 'text'],
                            ['label' => 'وضعیت', 'key' => 'status', 'type' => 'select-option','values' => \App\Models\Order::$PERSIAN_STATUSES,'colors' => \App\Models\Order::$STATUS_COLORS,'onChange' => 'changeStatus(this)'],
                            ['label' => 'تصویر اولین محصول', 'key' => 'firstImageOfOrderItem', 'type' => 'image'],
                            ['label' => 'اسم محصول', 'key' => 'productNameCol', 'type' => 'text'],
                            ['label' => 'وزن و درصد', 'key' => 'WeightCol', 'type' => 'text'],
                            ['label' => 'آدرس و درگاه', 'key' => 'AddressCol', 'type' => 'text'],
                            ['label' => 'تعداد و مقدار سفارش قبلی', 'key' => 'SumCountAndAmountCol', 'type' => 'text'],
                            ['label' => 'تخفیف', 'key' => 'discountCol', 'type' => 'text'],
                            ['label' => 'فاکتور', 'key' => 'factorCol', 'type' => 'text'],
                            ['label' => 'منبع ورود یوزر', 'key' => '', 'type' => 'text'],
                        ]"
            :items="$orders"
            :actions="[
                            ['label' => 'پرینت','type' =>'route','route' => ['admin_order.print',   ['order'=>'{id}'] ] ],
                            ['label' => 'ویرایش', 'type' => 'modalEdit'],
                            ['label' => 'لغو', 'type' => 'modalCancel'],
                            ['label' => 'حذف', 'type' => 'modalDestroy']
                        ]"
        >

            @foreach($orders as $order)
                <x-modal.cancel id="modal-cancel-{{$order->id}}" title="لغو سفارش" action="{{route('admin_order.cancel', $order->id)}}" title="سفارش {{$order->id}}" />

                <x-modal.destroy id="modal-destroy-{{$order->id}}" title="حذف سفارش" action="{{route('admin_orders.destroy', $order->id)}}" title="سفارش {{$order->id}}" />
                <!-- Modal -->
                <x-modal.update id="modal-edit-{{$order->id}}" class=" modal-lg " title="ویرایش سفارش" action="{{route('admin_orders.update', $order->id)}}" confirmation="true" >
                    <input type="hidden" name="id" value="{{$order->id}}">
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
                        <x-form.input col="col-6" title="مبلغ قابل پرداخت" :value="$order->final_amount" name="none" />
                        {{-- hidden input برای هر سفارش --}}
                        <input type="hidden" name="updated_items" id="updated-items-{{$order->id}}">

                        <div class="table-responsive col-12">
                            <table class="table-bordered" style="width: 100%" >
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
                                        data-id="{{$orderItem->id}}"
                                        data-order-id="{{$order->id}}">

                                        <td>{{$orderItem->product->name}}</td>
                                        <td class="item-count">{{$orderItem->count}}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning decrease-count" data-order-id="{{$order->id}}">-</button>
                                            <button type="button" class="btn btn-sm btn-danger delete-item" data-order-id="{{$order->id}}">حذف</button>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>


                            </table>
                        </div>
                    </div>
                </x-modal.update>
                <!-- /Modal -->
            @endforeach
        </x-dataTable>
    </x-page>

    <script>
        function changeStatus(element) {
            let rowId = element.getAttribute('data-id');
            let key   = element.getAttribute('data-key');
            let value = element.value;

            $.ajax({
                url: "{{ route('update_order_status') }}", // <-- adjust route to your Laravel endpoint
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    orderId : rowId,
                    orderStatus: value
                },
                success: function (response) {
                    // ✅ Success toast
                    if (typeof toastr !== "undefined") {
                        toastr.success("وضعیت با موفقیت ویرایش شد");
                    } else {
                        alert("خطا در ویرایش وضعیت");
                    }
                },
                error: function (xhr) {
                    // ❌ Error toast
                    if (typeof toastr !== "undefined") {
                        toastr.error("Failed to update status!");
                    } else {
                        alert("Failed to update status!");
                    }
                }
            });
        }

        function modalEdit(id){
            const dModal = $("#modal-edit-"+id)
            dModal.modal("show")
        }

        function modalDestroy(id){
            const dModal = $("#modal-destroy-"+id)
            dModal.modal("show")
        }

        function modalCancel(id){
            const dModal = $("#modal-cancel-"+id)
            dModal.modal("show")
        }

    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            $('body').on('submit', 'form', function(e) {
                let form = $(this);
                let modal = form.closest('.modal');

                // Only intercept if inside modal with id starting with "modal-"
                if (modal.length && modal.attr('id').startsWith('modal-')) {
                    e.preventDefault();

                    let action = form.attr('action');
                    let method = form.attr('method') || 'POST';
                    let data = new FormData(this);

                    $.ajax({
                        url: action,
                        type: method,
                        data: data,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            modal.modal('hide');
                            location.reload();
                        },
                        error: function(xhr) {
                            alert('خطایی رخ داد ❌');
                            console.log(xhr.responseText);
                        }
                    });
                }
            });
        });
        function filterTransaction(){
            let transaction_id = $("#transaction_id").val()
            window.loadDataWithNewUrl("{{route('table.orders')}}?transaction_id="+transaction_id);
        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // شی برای ذخیره تغییرات همه سفارش‌ها
            // مثلا: { 12: { 45: 2, 46: 0 }, 13: { 77: 1 } }
            let updates = {};

            // دکمه کاهش
            document.querySelectorAll(".decrease-count").forEach(btn => {
                btn.addEventListener("click", function () {
                    let row = this.closest("tr");
                    let orderId = this.dataset.orderId;
                    let itemId = row.dataset.id;
                    let countEl = row.querySelector(".item-count");
                    let count = parseInt(countEl.innerText);

                    count--;

                    if (count <= 0) {
                        row.remove();
                        updates[orderId] = updates[orderId] || {};
                        updates[orderId][itemId] = 0; // حذف
                    } else {
                        countEl.innerText = count;
                        updates[orderId] = updates[orderId] || {};
                        updates[orderId][itemId] = count;
                    }
                });
            });

            // دکمه حذف مستقیم
            document.querySelectorAll(".delete-item").forEach(btn => {
                btn.addEventListener("click", function () {
                    let row = this.closest("tr");
                    let orderId = this.dataset.orderId;
                    let itemId = row.dataset.id;

                    row.remove();
                    updates[orderId] = updates[orderId] || {};
                    updates[orderId][itemId] = 0; // حذف
                });
            });

            // هندل همه فرم‌ها → قبل از submit آپدیت‌ها رو تو hidden بریزیم
            document.querySelectorAll("form").forEach(form => {
                form.addEventListener("submit", function () {
                    let orderId = form.querySelector("[name='id']").value;
                    let hiddenInput = form.querySelector(`#updated-items-${orderId}`);

                    if (hiddenInput) {
                        let payload = {
                            order_id: orderId,
                            items: updates[orderId] || {}
                        };
                        hiddenInput.value = JSON.stringify(payload);
                    }
                });
            });
        });
    </script>



@endsection
