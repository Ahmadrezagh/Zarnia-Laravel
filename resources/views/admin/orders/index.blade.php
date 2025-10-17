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
                <div class="row">
                    <div class="col-md-6">
                        <x-form.input title="کد پیگیری" name="transaction_id" id="transaction_id" :value="request('transaction_id')" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input title="جستجو (شناسه / نام محصول / نام کاربر)" name="search" id="search_input" :value="request('search')" />
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
            <button class="btn btn-primary mb-3" type="button" onclick="openCreateOrderModal()">ایجاد سفارش جدید</button>
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
                                <select id="order-user" name="user_id" class="form-control" style="width: 100%"></select>
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
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="new-user-email" style="pointer-events: auto;">ایمیل</label>
                                                <input type="email" id="new-user-email" name="email" class="form-control"
                                                       style="pointer-events: auto; position: relative; z-index: 10;">
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="new-user-password" style="pointer-events: auto;">رمز عبور <span class="text-danger">*</span></label>
                                                <input type="password" id="new-user-password" name="password" class="form-control" required minlength="6"
                                                       style="pointer-events: auto; position: relative; z-index: 10;">
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="new-user-password-confirmation" style="pointer-events: auto;">تکرار رمز <span class="text-danger">*</span></label>
                                                <input type="password" id="new-user-password-confirmation" name="password_confirmation" class="form-control" required minlength="6"
                                                       style="pointer-events: auto; position: relative; z-index: 10;">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCreateUserForm()">
                                            <i class="fas fa-times"></i> انصراف
                                        </button>
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-save"></i> ذخیره کاربر
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Address Selection (loaded based on user) -->
                        <div class="form-group">
                            <label for="order-address">آدرس <span class="text-danger">*</span></label>
                            <select id="order-address" name="address_id" class="form-control" disabled>
                                <option value="">ابتدا کاربر را انتخاب کنید</option>
                            </select>
                        </div>

                        <!-- Gateway Selection -->
                        <div class="form-group">
                            <label for="order-gateway">درگاه پرداخت <span class="text-danger">*</span></label>
                            <select id="order-gateway" name="gateway_id" class="form-control">
                                <option value="">انتخاب درگاه</option>
                                @foreach(\App\Models\Gateway::all() as $gateway)
                                    <option value="{{ $gateway->id }}">{{ $gateway->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Shipping Selection -->
                        <div class="form-group">
                            <label for="order-shipping">روش ارسال <span class="text-danger">*</span></label>
                            <select id="order-shipping" name="shipping_id" class="form-control">
                                <option value="">انتخاب روش ارسال</option>
                                @foreach(\App\Models\Shipping::all() as $shipping)
                                    <option value="{{ $shipping->id }}" data-price="{{ $shipping->price ?? 0 }}">
                                        {{ $shipping->title }} ({{ number_format($shipping->price ?? 0) }} تومان)
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
        /* Ensure inline create user form is always interactive */
        #inline-create-user-form {
            position: relative !important;
            z-index: 1050 !important;
        }
        #inline-create-user-form input,
        #inline-create-user-form label,
        #inline-create-user-form button {
            pointer-events: auto !important;
            position: relative !important;
            z-index: 1051 !important;
        }
        #inline-create-user-form .card-body {
            background: white;
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
            const dModal = $("#modal-cancel-"+id)
            dModal.modal("show")
        }

    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            $('body').on('submit', 'form', function(e) {
                let form = $(this);
                let modal = form.closest('.modal');

                // Skip if this is the create order form (it has its own handler)
                if (form.attr('id') === 'create-order-form') {
                    return; // Let the custom handler handle it
                }

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
        // Store current status filter
        let currentStatusFilter = "{{ request('status') ?? '' }}";
        
        // Add Enter key support for input fields
        $(document).ready(function() {
            $('#transaction_id, #search_input').on('keypress', function(e) {
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
            
            let params = [];
            if(transaction_id) params.push("transaction_id=" + encodeURIComponent(transaction_id));
            if(search) params.push("search=" + encodeURIComponent(search));
            if(currentStatusFilter) params.push("status=" + encodeURIComponent(currentStatusFilter));
            
            let url = "{{route('table.orders')}}" + (params.length ? "?" + params.join("&") : "");
            window.loadDataWithNewUrl(url);
        }
        
        function filterByStatus(status){
            // Update current status filter
            currentStatusFilter = status;
            
            // Update button active states
            $('.btn-group button').removeClass('active');
            event.target.classList.add('active');
            
            let transaction_id = $("#transaction_id").val();
            let search = $("#search_input").val();
            
            let params = [];
            if(transaction_id) params.push("transaction_id=" + encodeURIComponent(transaction_id));
            if(search) params.push("search=" + encodeURIComponent(search));
            if(status) params.push("status=" + encodeURIComponent(status));
            
            let tableUrl = "{{route('table.orders')}}" + (params.length ? "?" + params.join("&") : "");
            window.loadDataWithNewUrl(tableUrl);
        }
        
        function clearFilters(){
            // Clear input fields
            $("#transaction_id").val('');
            $("#search_input").val('');
            
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
            
            initializeUserSelect();
            addProductRow(); // Add first product row
        }
        
        /**
         * Initialize user selection with Select2
         */
        function initializeUserSelect() {
            $('#order-user').select2({
                placeholder: 'جستجوی کاربر...',
                allowClear: true,
                ajax: {
                    url: '{{ route("admin_order.users.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results || []
                        };
                    }
                }
            });
            
            // Load addresses when user is selected
            $('#order-user').on('change', function() {
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
                        <div class="col-md-6">
                            <label>محصول</label>
                            <select class="form-control product-select" data-row="${productRowCounter}" style="width: 100%"></select>
                        </div>
                        <div class="col-md-3">
                            <label>تعداد</label>
                            <input type="number" class="form-control product-quantity" min="1" value="1" data-row="${productRowCounter}">
                        </div>
                        <div class="col-md-2">
                            <label>قیمت</label>
                            <input type="text" class="form-control product-price" readonly data-row="${productRowCounter}">
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
            
            // Initialize Select2 for the new product select
            $(`.product-select[data-row="${productRowCounter}"]`).select2({
                placeholder: 'جستجوی محصول...',
                allowClear: true,
                ajax: {
                    url: '{{ route("products.ajax.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results.map(item => {
                                // Extract product ID from "Product:123" format
                                let id = item.id.replace('Product:', '');
                                return {
                                    id: id,
                                    text: item.text
                                };
                            }).filter(item => item.id.match(/^\d+$/)) // Only numeric IDs
                        };
                    }
                }
            });
            
            // Update price when product is selected
            $(`.product-select[data-row="${productRowCounter}"]`).on('change', function() {
                loadProductPrice($(this).val(), productRowCounter);
            });
            
            // Update total when quantity changes
            $(`.product-quantity[data-row="${productRowCounter}"]`).on('input', updateOrderSummary);
            
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
                    $(`.product-price[data-row="${rowId}"]`).val(number_format(price) + ' تومان');
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
                const price = $(`.product-price[data-row="${row}"]`).data('price') || 0;
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
                const price = $(`.product-price[data-row="${row}"]`).data('price');
                
                if (productId && quantity && price) {
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
            if (!$('#order-address').val()) {
                toastr.error('لطفا آدرس را انتخاب کنید');
                return;
            }
            if (!$('#order-gateway').val()) {
                toastr.error('لطفا درگاه پرداخت را انتخاب کنید');
                return;
            }
            if (!$('#order-shipping').val()) {
                toastr.error('لطفا روش ارسال را انتخاب کنید');
                return;
            }
            
            // Prepare data object (NOT FormData)
            const requestData = {
                _token: '{{ csrf_token() }}',
                user_id: $('#order-user').val(),
                address_id: $('#order-address').val(),
                gateway_id: $('#order-gateway').val(),
                shipping_id: $('#order-shipping').val(),
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
         * Handle create user form submission
         */
        $(document).ready(function() {
            $('#create-user-inline-form').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    _token: '{{ csrf_token() }}',
                    name: $('#new-user-name').val(),
                    phone: $('#new-user-phone').val(),
                    email: $('#new-user-email').val(),
                    password: $('#new-user-password').val(),
                    password_confirmation: $('#new-user-password-confirmation').val()
                };
                
                console.log('Creating new user:', formData);
                
                $.ajax({
                    url: '{{ route("users.store") }}',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        console.log('User created:', response);
                        toastr.success('کاربر با موفقیت ایجاد شد');
                        
                        // Hide the inline form
                        $('#inline-create-user-form').slideUp();
                        
                        // Add new user to Select2 and select it
                        if (response.user) {
                            const newOption = new Option(
                                response.user.name + ' - ' + response.user.phone, 
                                response.user.id, 
                                true, 
                                true
                            );
                            $('#order-user').append(newOption).trigger('change');
                            
                            // Show info about adding address
                            toastr.info('اکنون می‌توانید آدرس برای این کاربر اضافه کنید', '', {timeOut: 5000});
                        }
                        
                        // Reset form
                        $('#create-user-inline-form')[0].reset();
                    },
                    error: function(xhr) {
                        console.error('Error creating user:', xhr);
                        
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            let errors = xhr.responseJSON.errors;
                            let errorMessage = Object.values(errors).flat().join('<br>');
                            toastr.error(errorMessage);
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error('خطا در ایجاد کاربر');
                        }
                    }
                });
            });
        });
    </script>

@endsection
