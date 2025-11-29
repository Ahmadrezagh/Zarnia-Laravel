@extends('layouts.panel')
@section('content')
    <x-breadcrumb :title="'سفارش ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'سفارشات']
      ]" />

    <x-page>
        <x-slot name="header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>سفارشات</h5>
                <a href="{{ route('admin_orders.trash') }}" class="btn btn-danger">
                    <i class="fas fa-trash"></i> زباله دان ({{ \App\Models\Order::onlyTrashed()->count() }})
                </a>
            </div>
            <hr>
            <form  method="GET">
                <div class="row">
                    <div class="col-md-4">
                        <x-form.input title="کد پیگیری" name="transaction_id" id="transaction_id" :value="request('transaction_id')" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input title="جستجو (شناسه / نام محصول / نام کاربر)" name="search" id="search_input" :value="request('search')" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input title="جستجو شماره تلفن (کاربر / گیرنده)" name="phone" id="phone_input" :value="request('phone')" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button class="btn btn-success" type="button" onclick="applyFilters()" >اعمال فیلتر</button>
                        <button class="btn btn-secondary" type="button" onclick="clearFilters()" >پاک کردن فیلتر</button>
                    </div>
                </div>
            </form>
            <hr>
            <!-- Status Filter Buttons -->
            <div class="mb-3">
                <h6>فیلتر بر اساس وضعیت:</h6>
                <div class="btn-group flex-wrap" role="group">
                    <button type="button" class="btn btn-outline-primary {{ !request('status') ? 'active' : '' }}" onclick="filterByStatus('')">
                        همه ({{ array_sum($statusCounts) }})
                    </button>
                    @foreach(\App\Models\Order::$STATUSES as $status)
                        <button type="button" 
                                class="btn btn-outline-primary {{ request('status') == $status ? 'active' : '' }} " 
                                onclick="filterByStatus('{{ $status }}')"
                                
                                >
                            {{ \App\Models\Order::$PERSIAN_STATUSES[$status] ?? $status }} ({{ $statusCounts[$status] ?? 0 }})
                        </button>
                    @endforeach
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-primary" type="button" onclick="openCreateOrderModal()">ایجاد سفارش جدید</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning" onclick="clearSystemCache()">
                        <i class="fas fa-broom"></i> پاک کردن کش
                    </button>
                    <button type="button" class="btn btn-success" onclick="getEtiketsFromAccounting()">
                        <i class="fas fa-download"></i> دریافت از حسابداری
                    </button>
                    <div class="bulk-actions">
                        <div class="btn-group">
                            <button type="button" class="btn btn-info" id="bulk-status-btn" onclick="openBulkStatusModal()">
                                <i class="fas fa-edit"></i> تغییر وضعیت دسته‌ای
                            </button>
                            <button type="button" class="btn btn-danger" id="bulk-delete-btn" onclick="openBulkDeleteModal()">
                                <i class="fas fa-trash"></i> حذف دسته‌ای
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </x-slot>

        <!-- Bulk Status Change Modal -->
        <div class="modal fade" id="modal-bulk-status" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info">
                        <h5 class="modal-title">
                            <i class="fas fa-edit"></i> تغییر وضعیت دسته‌ای
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form id="bulk-status-form">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>توجه:</strong> سفارش‌های انتخاب شده را انتخاب کنید و وضعیت جدید را تعیین کنید.
                            </div>
                            <div class="form-group">
                                <label for="bulk-status-select">وضعیت جدید:</label>
                                <select id="bulk-status-select" name="status" class="form-control" required>
                                    <option value="">انتخاب وضعیت</option>
                                    @foreach(\App\Models\Order::$STATUSES as $status)
                                        <option value="{{ $status }}">
                                            {{ \App\Models\Order::$PERSIAN_STATUSES[$status] ?? $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                            <button type="button" class="btn btn-info" onclick="submitBulkStatusChange()">
                                <i class="fas fa-save"></i> اعمال تغییرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bulk Delete Modal -->
        <div class="modal fade" id="modal-bulk-delete" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">
                            <i class="fas fa-trash"></i> حذف دسته‌ای
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>توجه:</strong> سفارش‌های انتخاب شده به زباله دان منتقل می‌شوند و قابل بازیابی هستند.
                        </div>
                        <p>آیا از انتقال سفارش‌های انتخاب شده به زباله دان اطمینان دارید؟</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                        <button type="button" class="btn btn-warning" onclick="submitBulkDelete()">
                            <i class="fas fa-trash"></i> انتقال به زباله دان
                        </button>
                    </div>
                </div>
            </div>
        </div>

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
                            ['label' => 'آدرس و درگاه', 'key' => 'AddressCol', 'type' => 'text', 'style' => 'max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'],
                            ['label' => 'تعداد و مقدار سفارش قبلی', 'key' => 'SumCountAndAmountCol', 'type' => 'text'],
                            ['label' => 'تخفیف', 'key' => 'discountCol', 'type' => 'text'],
                            ['label' => 'فاکتور', 'key' => 'factorCol', 'type' => 'text'],
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
                        <x-form.input col="col-6" title="هزینه ارسال" :value="$order->shippingPrice" name="none" />
                        <x-form.input col="col-6" title="مبلغ قابل پرداخت" :value="$order->finalPrice" name="none" />
                        <x-form.input col="col-6" title="Payment Token" :value="$order->payment_token" name="none" />
                        <x-form.input col="col-6" title="Transaction ID" :value="$order->transaction_id" name="none" />
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

    <!-- Create Order Modal -->
    <div class="modal fade" id="modal-create-order" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ایجاد سفارش جدید</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="create-order-form">
                    <div class="modal-body">
                        @csrf
                        
                        <!-- User Selection -->
                        <div class="form-group">
                            <label for="order-user">کاربر <span class="text-danger">*</span></label>
                            <div class="d-flex">
                                <select id="order-user" name="user_id" class="form-control" style="width: 100%;"></select>
                                <button type="button" class="btn btn-info mr-2" onclick="toggleCreateUserForm()" title="ایجاد کاربر جدید">
                                    <i class="fas fa-user-plus"></i> کاربر جدید
                                </button>
                            </div>
                        </div>

                        <!-- Inline Create User Form (Hidden by default) -->
                        <div id="inline-create-user-form" class="card border-info mb-3" style="display: none; position: relative; z-index: 1;">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-user-plus"></i> ایجاد کاربر جدید
                                    <button type="button" class="close text-white" onclick="toggleCreateUserForm()" style="position: relative; z-index: 2;">
                                        <span>&times;</span>
                                    </button>
                                </h6>
                            </div>
                            <div class="card-body" style="position: relative; z-index: 1;">
                                <form id="create-user-inline-form">
                                    @csrf
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="new-user-name" style="pointer-events: auto;">نام <span class="text-danger">*</span></label>
                                                <input type="text" id="new-user-name" name="name" class="form-control" required 
                                                       style="pointer-events: auto; position: relative; z-index: 10;">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="new-user-phone" style="pointer-events: auto;">شماره تلفن <span class="text-danger">*</span></label>
                                                <input type="text" id="new-user-phone" name="phone" class="form-control" required 
                                                       placeholder="09xxxxxxxxx" pattern="09[0-9]{9}"
                                                       style="pointer-events: auto; position: relative; z-index: 10;">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle"></i> رمز عبور به صورت خودکار برابر شماره تلفن تنظیم می‌شود
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCreateUserForm()">
                                            <i class="fas fa-times"></i> انصراف
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm" onclick="submitCreateUser()">
                                            <i class="fas fa-save"></i> ذخیره کاربر
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Address Selection (loaded based on user) - Optional for in-store orders -->
                        <div class="form-group">
                            <label for="order-address">آدرس</label>
                            <small class="text-muted">(اختیاری - برای سفارشات حضوری نیازی نیست)</small>
                            <select id="order-address" name="address_id" class="form-control" disabled>
                                <option value="">ابتدا کاربر را انتخاب کنید</option>
                            </select>
                        </div>

                        <!-- Shipping Selection - Optional for in-store orders -->
                        <div class="form-group">
                            <label for="order-shipping">روش ارسال</label>
                            <small class="text-muted">(اختیاری - برای سفارشات حضوری نیازی نیست)</small>
                            <select id="order-shipping" name="shipping_id" class="form-control">
                                <option value="">بدون ارسال (خرید حضوری)</option>
                                @foreach(\App\Models\Shipping::all() as $shipping)
                                    <option value="{{ $shipping->id }}" data-price="{{ $shipping->price ?? 0 }}">
                                        {{ $shipping->title }} ({{ number_format($shipping->price ?? 0) }} تومان)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Gateway Selection -->
                        <div class="form-group">
                            <label for="order-gateway">درگاه پرداخت</label>
                            <small class="text-muted">(اختیاری - برای سفارشات حضوری نیازی نیست)</small>
                            <select id="order-gateway" name="gateway_id" class="form-control">
                                <option value="">بدون درگاه (پرداخت نقدی)</option>
                                @foreach(\App\Models\Gateway::all() as $gateway)
                                    <option value="{{ $gateway->id }}">
                                        {{ $gateway->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Products Section -->
                        <div class="form-group">
                            <label>محصولات <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-success mb-2" onclick="addProductRow()">
                                <i class="fas fa-plus"></i> افزودن محصول
                            </button>
                            <div id="products-container">
                                <!-- Product rows will be added here -->
                            </div>
                        </div>

                        <!-- Discount Code -->
                        <div class="form-group">
                            <label for="order-discount-code">کد تخفیف</label>
                            <input type="text" id="order-discount-code" name="discount_code" class="form-control" placeholder="کد تخفیف (اختیاری)">
                        </div>

                        <!-- Status -->
                        <div class="form-group">
                            <label for="order-status">وضعیت</label>
                            <select id="order-status" name="status" class="form-control">
                                @foreach(\App\Models\Order::$STATUSES as $status)
                                    <option value="{{ $status }}" {{ $status == 'paid' ? 'selected' : '' }}>
                                        {{ \App\Models\Order::$PERSIAN_STATUSES[$status] ?? $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Note -->
                        <div class="form-group">
                            <label for="order-note">یادداشت</label>
                            <textarea id="order-note" name="note" class="form-control" rows="3" placeholder="یادداشت (اختیاری)"></textarea>
                        </div>

                        <!-- Order Summary -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6>خلاصه سفارش</h6>
                                <div class="row">
                                    <div class="col-6">مجموع محصولات:</div>
                                    <div class="col-6 text-right" id="summary-subtotal">0 تومان</div>
                                </div>
                                <div class="row">
                                    <div class="col-6">هزینه ارسال:</div>
                                    <div class="col-6 text-right" id="summary-shipping">0 تومان</div>
                                </div>
                                <div class="row">
                                    <div class="col-6">تخفیف:</div>
                                    <div class="col-6 text-right" id="summary-discount">0 تومان</div>
                                </div>
                                <hr>
                                <div class="row font-weight-bold">
                                    <div class="col-6">مبلغ نهایی:</div>
                                    <div class="col-6 text-right" id="summary-total">0 تومان</div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                        <button type="button" class="btn btn-primary" onclick="submitCreateOrder()">ایجاد سفارش</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Limit address column width in orders table */
        #orders-table td:nth-child(7) {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Ensure inline create user form is always interactive */
        #inline-create-user-form {
            position: relative !important;
            z-index: 1 !important;
        }
        #inline-create-user-form input,
        #inline-create-user-form label,
        #inline-create-user-form button {
            pointer-events: auto !important;
            position: relative !important;
            z-index: 2 !important;
        }
        #inline-create-user-form .card-body {
            background: white;
        }
        
        /* Ensure Select2 and user selection are always interactive */
        #order-user {
            pointer-events: auto !important;
        }
        
        .select2-container,
        .select2-container--default {
            pointer-events: auto !important;
            z-index: 99999 !important;
        }
        
        .select2-dropdown {
            pointer-events: auto !important;
            z-index: 99999 !important;
        }
        
        .select2-search__field {
            pointer-events: auto !important;
            z-index: 100000 !important;
            position: relative !important;
        }
        
        .select2-container--open {
            z-index: 99999 !important;
        }
        
        .select2-results {
            pointer-events: auto !important;
        }
        
        /* Ensure modal body allows interaction with Select2 */
        #modal-create-order .modal-body {
            overflow: visible !important;
        }
        
        /* Make sure Select2 container within modal is clickable */
        #modal-create-order .select2-container {
            display: block !important;
        }
        
        /* Ensure all Select2 elements in modal are interactive */
        #modal-create-order .product-select,
        #modal-create-order .select2-selection,
        #modal-create-order .select2-selection__rendered,
        #modal-create-order .select2-selection__arrow {
            pointer-events: auto !important;
        }
        
        /* Product Select2 specific rules */
        .product-select {
            pointer-events: auto !important;
        }
        
        #modal-create-order .product-select + .select2-container {
            z-index: 99999 !important;
            pointer-events: auto !important;
        }
    </style>

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
            // Check if modal exists, if not create it dynamically
            let modalId = "modal-cancel-"+id;
            let dModal = $("#"+modalId);
            
            if (dModal.length === 0) {
                // Create modal dynamically
                const modalHtml = `
                    <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">لغو سفارش</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <form action="/admin/admin_order/cancel/${id}" method="GET" class="ajax-form" data-method="GET">
                                    <div class="modal-body">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <h5>آیا در لغو '<b>سفارش ${id}</b>' مطمئن هستید؟</h5>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                                        <button type="submit" class="btn btn-primary">لغو سفارش</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                `;
                
                // Append modal to body
                $('body').append(modalHtml);
                dModal = $("#"+modalId);
                
                // Handle form submission
                dModal.find('form').on('submit', function(e) {
                    e.preventDefault();
                    const form = $(this);
                    const action = form.attr('action');
                    
                    $.ajax({
                        url: action,
                        type: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            toastr.success('سفارش با موفقیت لغو شد');
                            dModal.modal('hide');
                            
                            // Remove modal from DOM after hiding
                            dModal.on('hidden.bs.modal', function() {
                                $(this).remove();
                            });
                            
                            // Reload table
                            if (typeof window.refreshTable === 'function') {
                                window.refreshTable();
                            } else {
                                location.reload();
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 419) {
                                toastr.error('نشست شما منقضی شده است. لطفا صفحه را رفرش کنید.');
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                toastr.error(xhr.responseJSON.message);
                            } else if (xhr.responseJSON && xhr.responseJSON.error) {
                                toastr.error(xhr.responseJSON.error);
                            } else {
                                toastr.error('خطا در لغو سفارش');
                            }
                        }
                    });
                });
            }
            
            dModal.modal("show");
        }

    </script>
    <script>
        // Store current status filter - declare at top level to avoid initialization errors
        var currentStatusFilter = "{{ request('status') ?? '' }}";
        
        // Setup AJAX to include CSRF token in all requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        document.addEventListener("DOMContentLoaded", function() {
            $('body').on('submit', 'form', function(e) {
                let form = $(this);
                let modal = form.closest('.modal');

                // Skip if this is the create order form (it has its own handler)
                if (form.attr('id') === 'create-order-form') {
                    return; // Let the custom handler handle it
                }

                // Skip if this is the bulk status form (it has its own AJAX handler)
                if (form.attr('id') === 'bulk-status-form') {
                    return; // Let the custom handler handle it
                }

                // Only intercept if inside modal with id starting with "modal-"
                if (modal.length && modal.attr('id').startsWith('modal-')) {
                    e.preventDefault();

                    let action = form.attr('action');
                    let data = new FormData(this);
                    
                    // Check for _method field (Laravel's method spoofing)
                    let method = form.find('input[name="_method"]').val() || form.attr('method') || 'POST';

                    $.ajax({
                        url: action,
                        type: method,
                        data: data,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.message) {
                                toastr.success(response.message);
                            }
                            modal.modal('hide');
                            
                            // Reload the page after successful action
                            setTimeout(function() {
                                location.reload();
                            }, 500);
                        },
                        error: function(xhr) {
                            if (xhr.status === 419) {
                                toastr.error('نشست شما منقضی شده است. لطفا صفحه را رفرش کنید.');
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                toastr.error(xhr.responseJSON.message);
                            } else {
                                toastr.error('خطایی رخ داد ❌');
                            }
                            console.log(xhr.responseText);
                        }
                    });
                }
            });
        });
        
        // Add Enter key support for input fields
        $(document).ready(function() {
            $('#transaction_id, #search_input, #phone_input').on('keypress', function(e) {
                if(e.which === 13) { // Enter key
                    e.preventDefault();
                    applyFilters();
                }
            });
        });
        
        function filterTransaction(){
            let transaction_id = $("#transaction_id").val()
            window.loadDataWithNewUrl("{{route('table.orders')}}?transaction_id="+transaction_id);
        }
        
        function applyFilters(){
            let transaction_id = $("#transaction_id").val();
            let search = $("#search_input").val();
            let phone = $("#phone_input").val();
            
            let params = [];
            if(transaction_id) params.push("transaction_id=" + encodeURIComponent(transaction_id));
            if(search) params.push("search=" + encodeURIComponent(search));
            if(phone) params.push("phone=" + encodeURIComponent(phone));
            if(currentStatusFilter) params.push("status=" + encodeURIComponent(currentStatusFilter));
            
            let url = "{{route('table.orders')}}" + (params.length ? "?" + params.join("&") : "");
            window.loadDataWithNewUrl(url);
        }
        
        function filterByStatus(status){
            // Update current status filter
            currentStatusFilter = status;
            
            // Update button active states
            $('.btn-group[role="group"] button').removeClass('active');
            
            // Find and activate the clicked button by checking onclick attribute
            $('.btn-group[role="group"] button').each(function() {
                const onclickAttr = $(this).attr('onclick') || '';
                if (status && onclickAttr.includes("'" + status + "'")) {
                    $(this).addClass('active');
                } else if (!status && onclickAttr.includes("filterByStatus('')")) {
                    $(this).addClass('active');
                }
            });
            
            let transaction_id = $("#transaction_id").val();
            let search = $("#search_input").val();
            let phone = $("#phone_input").val();
            
            let params = [];
            if(transaction_id) params.push("transaction_id=" + encodeURIComponent(transaction_id));
            if(search) params.push("search=" + encodeURIComponent(search));
            if(phone) params.push("phone=" + encodeURIComponent(phone));
            if(status) params.push("status=" + encodeURIComponent(status));
            
            let tableUrl = "{{route('table.orders')}}" + (params.length ? "?" + params.join("&") : "");
            window.loadDataWithNewUrl(tableUrl);
        }
        
        function clearFilters(){
            // Clear input fields
            $("#transaction_id").val('');
            $("#search_input").val('');
            $("#phone_input").val('');
            
            // Reset status filter
            currentStatusFilter = '';
            
            // Reset button active states
            $('.btn-group button').removeClass('active');
            $('.btn-group button:first').addClass('active');
            
            window.loadDataWithNewUrl("{{route('table.orders')}}");
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

    <script>
        let productRowCounter = 0;
        
        /**
         * Open create order modal
         */
        function openCreateOrderModal() {
            $('#modal-create-order').modal('show');
            
            // Debug: Check if button exists
            console.log('Modal opened');
            console.log('Submit button exists:', $('#submit-create-order').length > 0);
            
            // Wait for modal to be fully shown before initializing Select2
            $('#modal-create-order').one('shown.bs.modal', function() {
                console.log('Modal fully shown, initializing Select2...');
                initializeUserSelect();
            });
            
            addProductRow(); // Add first product row
        }
        
        /**
         * Initialize user selection with Select2
         */
        function initializeUserSelect() {
            console.log('Initializing user select...');
            
            // Destroy existing Select2 if any
            if ($('#order-user').hasClass('select2-hidden-accessible')) {
                $('#order-user').select2('destroy');
            }
            
            $('#order-user').select2({
                placeholder: 'جستجوی کاربر یا کلیک برای مشاهده لیست...',
                allowClear: true,
                dropdownParent: $('#modal-create-order'),
                width: '100%',
                minimumInputLength: 0, // ✅ Show results even without typing
                ajax: {
                    url: '{{ route("admin_order.users.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        console.log('Select2 AJAX - Search term:', params.term);
                        return {
                            search: params.term || ''
                        };
                    },
                    processResults: function (data) {
                        console.log('Select2 AJAX - Results:', data);
                        return {
                            results: data.results || []
                        };
                    },
                    error: function(xhr, status, error) {
                        console.error('Select2 AJAX Error:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                    }
                }
            });
            
            console.log('User select initialized');
            
            // Open dropdown and trigger search when clicked
            $('#order-user').on('select2:open', function() {
                console.log('Select2 opened');
                // Focus on search field when dropdown opens
                setTimeout(function() {
                    $('.select2-search__field').focus();
                }, 100);
            });
            
            // Load addresses when user is selected
            $('#order-user').on('change', function() {
                console.log('User selected:', $(this).val());
                loadUserAddresses($(this).val());
            });
        }
        
        /**
         * Load user addresses
         */
        function loadUserAddresses(userId) {
            if (!userId) {
                $('#order-address').html('<option value="">ابتدا کاربر را انتخاب کنید</option>').prop('disabled', true);
                return;
            }
            
            $.ajax({
                url: '{{ route("admin_order.users.addresses", ":userId") }}'.replace(':userId', userId),
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        let addresses = response.addresses || [];
                        let options = '<option value="">انتخاب آدرس</option>';
                        
                        if (addresses.length === 0) {
                            options = '<option value="">این کاربر آدرسی ندارد</option>';
                            $('#order-address').html(options).prop('disabled', true);
                            toastr.warning('این کاربر آدرسی ثبت نکرده است');
                        } else {
                            addresses.forEach(address => {
                                let addressText = address.receiver_name + ' - ' + 
                                                (address.city ? address.city + ' - ' : '') +
                                                address.address.substring(0, 50) + '...';
                                options += `<option value="${address.id}">${addressText}</option>`;
                            });
                            $('#order-address').html(options).prop('disabled', false);
                        }
                    }
                },
                error: function(xhr) {
                    console.error('Error loading addresses:', xhr);
                    toastr.error('خطا در بارگذاری آدرس‌ها');
                    $('#order-address').html('<option value="">خطا در بارگذاری آدرس‌ها</option>').prop('disabled', true);
                }
            });
        }
        
        /**
         * Add product row
         */
        function addProductRow() {
            productRowCounter++;
            const rowHtml = `
                <div class="product-row card mb-2 p-3" data-row="${productRowCounter}">
                    <div class="row">
                        <div class="col-md-5">
                            <label>محصول</label>
                            <select class="form-control product-select" data-row="${productRowCounter}" style="width: 100%; pointer-events: auto;"></select>
                        </div>
                        <div class="col-md-2">
                            <label>وزن (گرم)</label>
                            <input type="text" class="form-control product-weight" readonly data-row="${productRowCounter}" placeholder="-">
                        </div>
                        <div class="col-md-2">
                            <label>تعداد</label>
                            <input type="number" class="form-control product-quantity" min="1" value="1" data-row="${productRowCounter}">
                            <small class="form-text text-muted product-stock-info" data-row="${productRowCounter}">موجودی: -</small>
                        </div>
                        <div class="col-md-2">
                            <label>قیمت</label>
                            <input type="text" class="form-control product-price" data-row="${productRowCounter}">
                            <small class="form-text text-muted product-original-price" data-row="${productRowCounter}" style="display:none;"></small>
                        </div>
                        <div class="col-md-1">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-block" onclick="removeProductRow(${productRowCounter})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('#products-container').append(rowHtml);
            
            console.log('Product row added:', productRowCounter);
            
            // Initialize Select2 for the new product select
            const $productSelect = $(`.product-select[data-row="${productRowCounter}"]`);
            console.log('Initializing product Select2 for row:', productRowCounter);
            
            $productSelect.select2({
                placeholder: 'جستجوی محصول...',
                allowClear: true,
                dropdownParent: $('#modal-create-order'),
                width: '100%',
                minimumInputLength: 0,
                ajax: {
                    url: '{{ route("products.ajax.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || ''
                        };
                    },
                    processResults: function (data) {
                        console.log('Product search results:', data);
                        return {
                            results: data.results.map(item => {
                                // Extract product ID from "Product:123" format
                                let id = item.id.replace('Product:', '');
                                return {
                                    id: id,
                                    text: item.text,
                                    price: item.price,
                                    single_count: item.single_count || 0,
                                    weight: item.weight || null
                                };
                            }).filter(item => item.id.match(/^\d+$/)) // Only numeric IDs
                        };
                    },
                    error: function(xhr, status, error) {
                        console.error('Product search error:', error);
                    }
                }
            });
            
            console.log('Product Select2 initialized for row:', productRowCounter);
            
            // Focus search field when dropdown opens
            $productSelect.on('select2:open', function() {
                console.log('Product dropdown opened for row:', productRowCounter);
                setTimeout(function() {
                    $('.select2-search__field').focus();
                }, 100);
            });
            
            // Update price, weight, and single count when product is selected
            $productSelect.on('change', function() {
                const selectedData = $(this).select2('data')[0];
                const rowId = $(this).data('row');
                
                if (selectedData) {
                    const basePrice = parseInt(selectedData.price || 0, 10) || 0;
                    const discountedPrice = parseInt(selectedData.discounted_price || 0, 10);
                    const originalPrice = parseInt(selectedData.original_price || 0, 10) || null;
                    const finalPrice = (discountedPrice && discountedPrice > 0) ? discountedPrice : basePrice;

                    $(`.product-price[data-row="${rowId}"]`).val(finalPrice);
                    $(`.product-price[data-row="${rowId}"]`).data('price', finalPrice);

                    const $originalPriceEl = $(`.product-original-price[data-row="${rowId}"]`);
                    if (discountedPrice && originalPrice && originalPrice !== finalPrice) {
                        $originalPriceEl.text('قیمت بدون تخفیف: ' + number_format(originalPrice) + ' تومان').show();
                    } else {
                        $originalPriceEl.text('').hide();
                    }
                    
                    // Set weight
                    const weight = selectedData.weight || null;
                    if (weight) {
                        $(`.product-weight[data-row="${rowId}"]`).val(weight + ' گرم');
                    } else {
                        $(`.product-weight[data-row="${rowId}"]`).val('-');
                    }
                    
                    // Set single count as max for quantity input
                    const singleCount = selectedData.single_count || 0;
                    $(`.product-quantity[data-row="${rowId}"]`).attr('max', singleCount > 0 ? singleCount : 999);
                    $(`.product-quantity[data-row="${rowId}"]`).data('single-count', singleCount);
                    
                    // Update stock info text
                    $(`.product-stock-info[data-row="${rowId}"]`).text('موجودی: ' + singleCount + ' عدد');
                    
                    // Reset quantity to 1
                    $(`.product-quantity[data-row="${rowId}"]`).val(1);
                    
                    updateOrderSummary();
                }
            });
            
            // Validate quantity when it changes
            $(`.product-quantity[data-row="${productRowCounter}"]`).on('input', function() {
                const rowId = $(this).data('row');
                const singleCount = parseInt($(this).data('single-count')) || 999;
                const enteredQty = parseInt($(this).val()) || 0;
                
                if (enteredQty > singleCount && singleCount > 0) {
                    toastr.error('تعداد درخواستی بیشتر از موجودی است! موجودی: ' + singleCount);
                    $(this).val(singleCount);
                }
                
                if (enteredQty < 1) {
                    $(this).val(1);
                }
                
                updateOrderSummary();
            });
            
            // Handle price editing
            $(`.product-price[data-row="${productRowCounter}"]`).on('input', function() {
                updateOrderSummary();
            });
            
            // Clean price on blur (when user finishes editing)
            $(`.product-price[data-row="${productRowCounter}"]`).on('blur', function() {
                const rowId = $(this).data('row');
                const rawValue = $(this).val();
                
                // Remove Persian digits, Arabic digits, commas, spaces, and "تومان" text
                let numericValue = rawValue
                    .replace(/[۰-۹]/g, function(d) { return String.fromCharCode(d.charCodeAt(0) - '۰'.charCodeAt(0) + '0'.charCodeAt(0)); })
                    .replace(/[٠-٩]/g, function(d) { return String.fromCharCode(d.charCodeAt(0) - '٠'.charCodeAt(0) + '0'.charCodeAt(0)); })
                    .replace(/[^\d]/g, '');
                
                const price = parseInt(numericValue) || 0;
                
                // Display plain number without formatting or "تومان"
                $(this).val(price);
                // Store the numeric price in data attribute
                $(this).data('price', price);
                
                updateOrderSummary();
            });
            
            // Update shipping cost when shipping method changes
            $('#order-shipping').on('change', updateOrderSummary);
        }
        
        /**
         * Remove product row
         */
        function removeProductRow(rowId) {
            $(`.product-row[data-row="${rowId}"]`).remove();
            updateOrderSummary();
        }
        
        /**
         * Load product price
         */
        function loadProductPrice(productId, rowId) {
            if (!productId) return;
            
            $.ajax({
                url: '/admin/products/' + productId,
                method: 'GET',
                success: function(response) {
                    const price = response.data.price || 0;
                    $(`.product-price[data-row="${rowId}"]`).val(price);
                    $(`.product-price[data-row="${rowId}"]`).data('price', price);
                    updateOrderSummary();
                },
                error: function() {
                    toastr.error('خطا در بارگذاری قیمت محصول');
                }
            });
        }
        
        /**
         * Update order summary
         */
        function updateOrderSummary() {
            let subtotal = 0;
            
            // Calculate subtotal from all products
            $('.product-row').each(function() {
                const row = $(this).data('row');
                
                // Get price from input field or data attribute
                const priceInput = $(`.product-price[data-row="${row}"]`).val();
                let price = $(`.product-price[data-row="${row}"]`).data('price') || 0;
                
                // If price input exists, try to parse it
                if (priceInput) {
                    const numericValue = priceInput
                        .replace(/[۰-۹]/g, function(d) { return String.fromCharCode(d.charCodeAt(0) - '۰'.charCodeAt(0) + '0'.charCodeAt(0)); })
                        .replace(/[٠-٩]/g, function(d) { return String.fromCharCode(d.charCodeAt(0) - '٠'.charCodeAt(0) + '0'.charCodeAt(0)); })
                        .replace(/[^\d]/g, '');
                    const parsedPrice = parseInt(numericValue);
                    if (!isNaN(parsedPrice) && parsedPrice > 0) {
                        price = parsedPrice;
                    }
                }
                
                const quantity = parseInt($(`.product-quantity[data-row="${row}"]`).val()) || 0;
                subtotal += price * quantity;
            });
            
            // Get shipping cost
            const shippingPrice = parseInt($('#order-shipping option:selected').data('price')) || 0;
            
            // Calculate discount (you can add discount logic here)
            const discount = 0;
            
            // Calculate final total
            const total = subtotal + shippingPrice - discount;
            
            // Update UI
            $('#summary-subtotal').text(number_format(subtotal) + ' تومان');
            $('#summary-shipping').text(number_format(shippingPrice) + ' تومان');
            $('#summary-discount').text(number_format(discount) + ' تومان');
            $('#summary-total').text(number_format(total) + ' تومان');
        }
        
        /**
         * Number format helper
         */
        function number_format(number) {
            return new Intl.NumberFormat('fa-IR').format(number);
        }
        
        /**
         * Submit create order form via AJAX
         */
        function submitCreateOrder() {
            console.log('submitCreateOrder function called');
                
            // Collect products first
            const products = [];
            $('.product-row').each(function() {
                const row = $(this).data('row');
                const productId = $(`.product-select[data-row="${row}"]`).val();
                const quantity = $(`.product-quantity[data-row="${row}"]`).val();
                
                // Get price from input field value (parse it)
                const priceInput = $(`.product-price[data-row="${row}"]`).val();
                let price = $(`.product-price[data-row="${row}"]`).data('price');
                
                // If price input exists, try to parse it
                if (priceInput) {
                    const numericValue = priceInput
                        .replace(/[۰-۹]/g, function(d) { return String.fromCharCode(d.charCodeAt(0) - '۰'.charCodeAt(0) + '0'.charCodeAt(0)); })
                        .replace(/[٠-٩]/g, function(d) { return String.fromCharCode(d.charCodeAt(0) - '٠'.charCodeAt(0) + '0'.charCodeAt(0)); })
                        .replace(/[^\d]/g, '');
                    const parsedPrice = parseInt(numericValue);
                    if (!isNaN(parsedPrice) && parsedPrice > 0) {
                        price = parsedPrice;
                    }
                }
                
                // Fallback to data attribute if parsing failed
                if (!price || price <= 0) {
                    price = $(`.product-price[data-row="${row}"]`).data('price');
                }
                
                if (productId && quantity && price && price > 0) {
                    products.push({
                        product_id: productId,
                        quantity: quantity,
                        price: price
                    });
                }
            });
            
            console.log('Collected products:', products);
            
            // Validate products before sending
            if (products.length === 0) {
                toastr.error('لطفا حداقل یک محصول اضافه کنید');
                return;
            }
            
            console.log('Products to send:', products);
            console.log('Products JSON:', JSON.stringify(products));
            
            // Validate required fields first
            if (!$('#order-user').val()) {
                toastr.error('لطفا کاربر را انتخاب کنید');
                return;
            }
            // Address, gateway, and shipping are now optional for in-store orders
            
            // Prepare data object (NOT FormData)
            const requestData = {
                _token: '{{ csrf_token() }}',
                user_id: $('#order-user').val(),
                address_id: $('#order-address').val() || null,
                gateway_id: $('#order-gateway').val() || null,
                shipping_id: $('#order-shipping').val() || null,
                discount_code: $('#order-discount-code').val() || '',
                status: $('#order-status').val(),
                note: $('#order-note').val() || '',
                products: JSON.stringify(products)
            };
            
            // Debug: Log data being sent
            console.log('Request data being sent:', requestData);
            console.log('Products array:', products);
            console.log('Products JSON string:', requestData.products);
            
            // Submit via AJAX
            $.ajax({
                url: '{{ route("admin_orders.store") }}',
                method: 'POST',
                data: requestData,
                dataType: 'json',
                success: function(response) {
                    console.log('Order created successfully:', response);
                    toastr.success('سفارش با موفقیت ایجاد شد');
                    $('#modal-create-order').modal('hide');
                    $('#create-order-form')[0].reset();
                    $('#products-container').empty();
                    productRowCounter = 0;
                    
                    // Refresh the table
                    if (typeof window.refreshTable === 'function') {
                        window.refreshTable();
                    } else {
                        location.reload();
                    }
                },
                error: function(xhr) {
                    console.error('Error response:', xhr);
                    console.error('Response JSON:', xhr.responseJSON);
                    console.error('Response Text:', xhr.responseText);
                    
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errors = xhr.responseJSON.errors;
                        let errorMessage = Object.values(errors).flat().join('<br>');
                        toastr.error(errorMessage);
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('خطا در ایجاد سفارش');
                    }
                }
            });
        }
        
        /**
         * Toggle inline create user form
         */
        function toggleCreateUserForm() {
            const formContainer = $('#inline-create-user-form');
            
            if (formContainer.is(':visible')) {
                formContainer.slideUp();
                // Reset form when hiding
                $('#create-user-inline-form')[0].reset();
            } else {
                formContainer.slideDown();
                
                // Explicitly enable all input fields
                $('#inline-create-user-form input').each(function() {
                    $(this).prop('disabled', false);
                    $(this).prop('readonly', false);
                });
                
                // Focus on first input
                setTimeout(() => {
                    $('#new-user-name').focus();
                    console.log('Form opened, focus set to name field');
                    console.log('Name field disabled:', $('#new-user-name').prop('disabled'));
                    console.log('Name field readonly:', $('#new-user-name').prop('readonly'));
                }, 300);
            }
        }
        
        /**
         * Submit create user form via AJAX
         */
        function submitCreateUser() {
            console.log('submitCreateUser function called');
            
            // Validate required fields
            const name = $('#new-user-name').val();
            const phone = $('#new-user-phone').val();
            
            if (!name || !phone) {
                toastr.error('لطفا نام و شماره تلفن را وارد کنید');
                return;
            }
            
            // Validate phone format
            const phonePattern = /^09[0-9]{9}$/;
            if (!phonePattern.test(phone)) {
                toastr.error('فرمت شماره تلفن صحیح نیست (09xxxxxxxxx)');
                return;
            }
            
            const defaultPassword = phone; // Use phone as default password
            
            const formData = {
                _token: '{{ csrf_token() }}',
                name: name,
                phone: phone,
                email: null,
                password: defaultPassword,
                password_confirmation: defaultPassword
            };
            
            console.log('Creating new user via AJAX:', formData);
            
            $.ajax({
                url: '{{ route("users.store") }}',
                method: 'POST',
                data: formData,
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                beforeSend: function() {
                    // Disable submit button to prevent double submission
                    $('#inline-create-user-form button').prop('disabled', true);
                    console.log('AJAX request sent...');
                },
                success: function(response) {
                    console.log('User created successfully:', response);
                    toastr.success('کاربر با موفقیت ایجاد شد');
                    
                    // Hide the inline form
                    $('#inline-create-user-form').slideUp();
                    
                    // Reset form
                    $('#create-user-inline-form')[0].reset();
                    
                    if (response.user) {
                        // Destroy existing Select2
                        $('#order-user').select2('destroy');
                        
                        // Clear the select
                        $('#order-user').empty();
                        
                        // Add the new user as an option
                        const newOption = new Option(
                            response.user.name + ' - ' + response.user.phone, 
                            response.user.id, 
                            true, 
                            true
                        );
                        $('#order-user').append(newOption);
                        
                        // Reinitialize Select2 with AJAX search
                        initializeUserSelect();
                        
                        // Set the newly created user as selected
                        $('#order-user').val(response.user.id).trigger('change');
                        
                        // Load addresses for the new user
                        loadUserAddresses(response.user.id);
                        
                        // Show info about adding address
                        toastr.info('اکنون می‌توانید آدرس برای این کاربر اضافه کنید', '', {timeOut: 5000});
                        
                        console.log('User list reloaded and new user selected:', response.user.id);
                    }
                    
                    // Re-enable buttons
                    $('#inline-create-user-form button').prop('disabled', false);
                },
                error: function(xhr) {
                    console.error('AJAX Error creating user:', xhr);
                    console.error('Status:', xhr.status);
                    console.error('Response:', xhr.responseText);
                    
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errors = xhr.responseJSON.errors;
                        let errorMessage = Object.values(errors).flat().join('<br>');
                        toastr.error(errorMessage);
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('خطا در ایجاد کاربر: ' + (xhr.statusText || 'خطای ناشناخته'));
                    }
                    
                    // Re-enable buttons
                    $('#inline-create-user-form button').prop('disabled', false);
                },
                complete: function() {
                    console.log('AJAX request completed');
                }
            });
        }

        function openBulkStatusModal() {
            // Get selected items from DataTable component's selectedValues hidden input
            const selectedValuesInput = document.getElementById('selectedValues');
            if (!selectedValuesInput || !selectedValuesInput.value || selectedValuesInput.value === '[]') {
                toastr.warning('لطفا حداقل یک سفارش را انتخاب کنید');
                return;
            }
            $('#modal-bulk-status').modal('show');
        }

        function openBulkDeleteModal() {
            // Get selected items from DataTable component's selectedValues hidden input
            const selectedValuesInput = document.getElementById('selectedValues');
            if (!selectedValuesInput || !selectedValuesInput.value || selectedValuesInput.value === '[]') {
                toastr.warning('لطفا حداقل یک سفارش را انتخاب کنید');
                return;
            }
            $('#modal-bulk-delete').modal('show');
        }

        function submitBulkStatusChange() {
            // Get selected items from DataTable component's selectedValues hidden input
            const selectedValuesInput = document.getElementById('selectedValues');
            if (!selectedValuesInput || !selectedValuesInput.value || selectedValuesInput.value === '[]') {
                toastr.error('لطفا حداقل یک سفارش را انتخاب کنید');
                return;
            }
            
            const status = $('#bulk-status-select').val();
            if (!status) {
                toastr.error('لطفا وضعیت جدید را انتخاب کنید');
                return;
            }
            
            // Show confirmation
            if (!confirm('آیا از عملیات مطمئن هستید؟')) {
                return;
            }
            
            // Debug: log the data being sent
            console.log('Sending bulk status update:', {
                order_ids: selectedValuesInput.value,
                status: status
            });
            
            $.ajax({
                url: '{{ route("admin_orders.bulk_status") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    _token: '{{ csrf_token() }}',
                    order_ids: selectedValuesInput.value, // Send as string (JSON)
                    status: status
                },
                success: function(response) {
                    console.log('Bulk status update success:', response);
                    toastr.success(response.message || 'وضعیت سفارش‌ها با موفقیت تغییر کرد');
                    $('#modal-bulk-status').modal('hide');
                    $('#bulk-status-select').val('');
                    
                    // Refresh the table
                    if (typeof window.refreshTable === 'function') {
                        window.refreshTable();
                    } else {
                        location.reload();
                    }
                },
                error: function(xhr) {
                    console.error('Bulk status update error:', xhr);
                    if (xhr.status === 419) {
                        toastr.error('نشست شما منقضی شده است. لطفا صفحه را رفرش کنید.');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('خطا در تغییر وضعیت سفارش‌ها');
                        if (xhr.responseText) {
                            console.error('Error response:', xhr.responseText);
                        }
                    }
                }
            });
        }

        function clearSystemCache() {
            if (!confirm('آیا از پاک کردن تمام کش‌های سیستم اطمینان دارید؟')) {
                return;
            }
            
            $.ajax({
                url: '{{ route("admin_orders.clear_cache") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    _token: '{{ csrf_token() }}'
                },
                beforeSend: function() {
                    // Show loading state
                    toastr.info('در حال پاک کردن کش‌ها...', '', {timeOut: 2000});
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'تمام کش‌های سیستم با موفقیت پاک شدند');
                    } else {
                        toastr.error(response.message || 'خطا در پاک کردن کش');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 419) {
                        toastr.error('نشست شما منقضی شده است. لطفا صفحه را رفرش کنید.');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('خطا در پاک کردن کش');
                    }
                }
            });
        }

        function getEtiketsFromAccounting() {
            if (!confirm('آیا از دریافت اطلاعات از حسابداری اطمینان دارید؟ این عملیات ممکن است چند دقیقه طول بکشد.')) {
                return;
            }
            
            $.ajax({
                url: '{{ route("admin_orders.get_etikets") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    _token: '{{ csrf_token() }}'
                },
                beforeSend: function() {
                    // Show loading state
                    toastr.info('در حال شروع دریافت از حسابداری...', '', {timeOut: 3000});
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'دریافت از حسابداری شروع شد');
                    } else {
                        toastr.error(response.message || 'خطا در شروع دریافت از حسابداری');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 419) {
                        toastr.error('نشست شما منقضی شده است. لطفا صفحه را رفرش کنید.');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('خطا در دریافت از حسابداری');
                    }
                }
            });
        }

        function submitBulkDelete() {
            // Get selected items from DataTable component's selectedValues hidden input
            const selectedValuesInput = document.getElementById('selectedValues');
            if (!selectedValuesInput || !selectedValuesInput.value || selectedValuesInput.value === '[]') {
                toastr.error('لطفا حداقل یک سفارش را انتخاب کنید');
                return;
            }
            
            // Show confirmation
            if (!confirm('آیا از عملیات مطمئن هستید؟')) {
                return;
            }
            
            $.ajax({
                url: '{{ route("admin_orders.bulk_delete") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    order_ids: selectedValuesInput.value // Send as string (JSON)
                },
                success: function(response) {
                    toastr.success(response.message || 'سفارش‌ها به زباله دان منتقل شدند');
                    $('#modal-bulk-delete').modal('hide');
                    
                    // Refresh the table
                    if (typeof window.refreshTable === 'function') {
                        window.refreshTable();
                    } else {
                        location.reload();
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 419) {
                        toastr.error('نشست شما منقضی شده است. لطفا صفحه را رفرش کنید.');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('خطا در حذف سفارش‌ها');
                    }
                }
            });
        }

        // No need for form submission handler since we're using button onclick
    </script>

@endsection
