@extends('layouts.panel')
@section('content')
    <x-breadcrumb :title="'زباله دان سفارشات'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'سفارشات', 'url' => route('admin_orders.index')],
            ['label' => 'زباله دان']
      ]" />

    <x-page>
        <x-slot name="header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>زباله دان سفارشات</h5>
                <a href="{{ route('admin_orders.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i> بازگشت به سفارشات
                </a>
            </div>
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
                    <button type="button" class="btn btn-outline-danger {{ !request('status') ? 'active' : '' }}" onclick="filterByStatus('')">
                        همه ({{ array_sum($statusCounts) }})
                    </button>
                    @foreach(\App\Models\Order::$STATUSES as $status)
                        <button type="button" 
                                class="btn btn-outline-danger {{ request('status') == $status ? 'active' : '' }} " 
                                onclick="filterByStatus('{{ $status }}')"
                                
                                >
                            {{ \App\Models\Order::$PERSIAN_STATUSES[$status] ?? $status }} ({{ $statusCounts[$status] ?? 0 }})
                        </button>
                    @endforeach
                </div>
            </div>
        </x-slot>

        <x-dataTable
            :url="route('table.orders.trash')"
            id="orders-trash-table"
            hasCheckbox="false"
            :columns="[
                            ['label' => 'سفارش', 'key' => 'orderColumn', 'type' => 'text'],
                            ['label' => 'وضعیت', 'key' => 'statusBadge', 'type' => 'text'],
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
                            ['label' => 'بازیابی', 'type' => 'modalRestore'],
                            ['label' => 'حذف دائم', 'type' => 'modalForceDelete']
                        ]"
        >

            @foreach($orders as $order)
                <!-- Restore Modal -->
                <div class="modal fade" id="modal-restore-{{$order->id}}" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">بازیابی سفارش</h5>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>آیا از بازیابی سفارش شماره <strong>{{$order->id}}</strong> اطمینان دارید؟</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                                <button type="button" class="btn btn-success" onclick="restoreOrder({{$order->id}})">بازیابی</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Force Delete Modal -->
                <div class="modal fade" id="modal-force-delete-{{$order->id}}" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">حذف دائم سفارش</h5>
                                <button type="button" class="close text-white" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>هشدار:</strong> این عملیات غیرقابل بازگشت است!
                                </div>
                                <p>آیا از حذف دائمی سفارش شماره <strong>{{$order->id}}</strong> اطمینان دارید؟</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                                <button type="button" class="btn btn-danger" onclick="forceDeleteOrder({{$order->id}})">حذف دائم</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </x-dataTable>
    </x-page>

    <script>
        // Setup AJAX to include CSRF token in all requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
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
        
        function applyFilters(){
            let transaction_id = $("#transaction_id").val();
            let search = $("#search_input").val();
            
            let params = [];
            if(transaction_id) params.push("transaction_id=" + encodeURIComponent(transaction_id));
            if(search) params.push("search=" + encodeURIComponent(search));
            if(currentStatusFilter) params.push("status=" + encodeURIComponent(currentStatusFilter));
            
            let url = "{{route('table.orders.trash')}}" + (params.length ? "?" + params.join("&") : "");
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
            
            let tableUrl = "{{route('table.orders.trash')}}" + (params.length ? "?" + params.join("&") : "");
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
            
            window.loadDataWithNewUrl("{{route('table.orders.trash')}}");
        }

        function modalRestore(id){
            $("#modal-restore-"+id).modal("show");
        }

        function modalForceDelete(id){
            $("#modal-force-delete-"+id).modal("show");
        }

        function restoreOrder(id){
            $.ajax({
                url: "{{ url('admin_orders') }}/" + id + "/restore",
                type: "POST",
                success: function(response) {
                    toastr.success(response.message || 'سفارش با موفقیت بازیابی شد');
                    $("#modal-restore-"+id).modal("hide");
                    
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
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'خطا در بازیابی سفارش');
                    }
                    console.error(xhr);
                }
            });
        }

        function forceDeleteOrder(id){
            $.ajax({
                url: "{{ url('admin_orders') }}/" + id + "/force-delete",
                type: "DELETE",
                success: function(response) {
                    toastr.success(response.message || 'سفارش به طور کامل حذف شد');
                    $("#modal-force-delete-"+id).modal("hide");
                    
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
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'خطا در حذف دائم سفارش');
                    }
                    console.error(xhr);
                }
            });
        }
    </script>

@endsection

