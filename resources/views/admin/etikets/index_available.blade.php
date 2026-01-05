@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'اتیکت‌های موجود'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'اتیکت‌های موجود']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <div class="row mb-3">
                <div class="col-6 mb-3">
                    <button type="button" id="bulk-update-btn" class="btn btn-primary" onclick="showBulkUpdateModal()" style="display: none;">ویرایش دسته جمعی</button>
                </div>
                <div class="col-6 mb-3">
                    <button type="button" id="bulk-delete-btn" class="btn btn-danger" onclick="bulkDeleteEtikets()" style="display: none;">حذف دسته جمعی</button>
                </div>
                <div class="col-12 mb-3">
                    <button type="button" class="btn btn-success" onclick="exportEtikets()">
                        <i class="fas fa-file-excel"></i> خروجی اکسل
                    </button>
                </div>
            </div>
            <div class="row mb-3">
                <form id="filterForm" action="" class="col-12">
                    <div class="row">
                        <x-form.input title="اسم" name="name" value="{{request('name')}}" col="col-3 mb-3" />
                        <x-form.input title="کد" name="code" value="{{request('code')}}" col="col-3 mb-3" />
                        <x-form.input title="وزن" name="weight" value="{{request('weight')}}" type="number" step="0.01" col="col-3 mb-3" />
                        <x-form.input title="از وزن" name="weight_from" value="{{request('weight_from')}}" type="number" step="0.01" col="col-3 mb-3" />
                        <x-form.input title="تا وزن" name="weight_to" value="{{request('weight_to')}}" type="number" step="0.01" col="col-3 mb-3" />
                        <x-form.select-option title="دسته بندی ها" id="category_ids" multiple="multiple" name="category_ids[]" col="col-3 mb-3">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                        @if(in_array($category->id, request()->input('category_ids', []))) selected @endif>
                                    {{ $category->title }}
                                </option>
                            @endforeach
                        </x-form.select-option>
                        <x-form.select-option title="مرتب‌سازی بر اساس" id="sort_column" name="sort_column" col="col-3 mb-3">
                            <option value="">-- انتخاب کنید --</option>
                            <option value="code" @if(request('sort_column') == 'code') selected @endif>کد</option>
                            <option value="name" @if(request('sort_column') == 'name') selected @endif>اسم</option>
                            <option value="weight" @if(request('sort_column') == 'weight') selected @endif>وزن</option>
                            <option value="price" @if(request('sort_column') == 'price') selected @endif>قیمت</option>
                            <option value="darsad_kharid" @if(request('sort_column') == 'darsad_kharid') selected @endif>درصد خرید</option>
                            <option value="ojrat" @if(request('sort_column') == 'ojrat') selected @endif>درصد اجرت</option>
                        </x-form.select-option>
                        <x-form.select-option title="ترتیب" id="sort_direction" name="sort_direction" col="col-3 mb-3">
                            <option value="asc" @if(request('sort_direction') == 'asc') selected @endif>صعودی</option>
                            <option value="desc" @if(request('sort_direction') == 'desc' || !request('sort_direction')) selected @endif>نزولی</option>
                        </x-form.select-option>
                        <div class="col-12">
                            <button type="button" onclick="filterEtikets()" class="btn btn-success" style="width:100%">فیلتر</button>
                        </div>
                    </div>
                </form>
            </div>
        </x-slot>
        <x-dataTable
            :url="route('table.etikets') . '?is_mojood=1'"
            id="etikets-available-table"
            
            hasCheckbox="true"
            :columns="[
                            ['label' => 'کد', 'key' => 'code', 'type' => 'text', 'sortable' => true],
                            ['label' => 'اسم', 'key' => 'name', 'type' => 'text', 'sortable' => true],
                            ['label' => 'وزن', 'key' => 'weight', 'type' => 'text', 'sortable' => true],
                            ['label' => 'قیمت', 'key' => 'price', 'type' => 'text', 'sortable' => true],
                            ['label' => 'محصول', 'key' => 'product_name', 'type' => 'text'],
                            ['label' => 'دسته بندی ها', 'key' => 'categories', 'type' => 'text'],
                            ['label' => 'موجودی', 'key' => 'is_mojood', 'type' => 'text'],
                            ['label' => 'درصد اجرت', 'key' => 'ojrat', 'type' => 'text', 'sortable' => true],
                            ['label' => 'درصد وزن فروش', 'key' => 'darsad_vazn_foroosh', 'type' => 'text', 'sortable' => true],
                            ['label' => 'تاریخ ایجاد', 'key' => 'created_at', 'type' => 'text', 'sortable' => true],
                        ]"
        >
        </x-dataTable>
    </x-page>

    <!-- Dynamic modal -->
    <div class="modal fade" id="dynamic-modal" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-name" id="dynamic-modal-title">ویرایش دسته جمعی اتیکت‌ها</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modal-body-content">
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function showDynamicModal(){
        const dModal = $("#dynamic-modal")
        dModal.modal("show")
    }

    function eraseModalContent(){
        $("#modal-body-content").empty()
    }

    function appendToModalContent(content){
        $("#modal-body-content").append(content)
    }

    // Show/hide bulk update and delete buttons based on selected rows
    function updateBulkUpdateButton() {
        const selectedIds = window.showArray ? window.showArray() : [];
        const updateBtn = $('#bulk-update-btn');
        const deleteBtn = $('#bulk-delete-btn');
        if (selectedIds.length > 0) {
            updateBtn.fadeIn();
            deleteBtn.fadeIn();
        } else {
            updateBtn.fadeOut();
            deleteBtn.fadeOut();
        }
    }

    // Override the updateHiddenInput function to also update the button
    $(document).ready(function() {
        // Wait for DataTable to initialize
        setTimeout(function() {
            // Hook into checkbox changes
            $(document).on('change', '#etikets-available-table .custom-select-option, #etikets-available-table #selectAll', function() {
                setTimeout(updateBulkUpdateButton, 50);
            });
            
            // Listen to DataTable draw events (when table redraws)
            if ($.fn.DataTable && $('#etikets-available-table').length) {
                const table = $('#etikets-available-table').DataTable();
                if (table) {
                    table.on('draw.dt', function() {
                        setTimeout(updateBulkUpdateButton, 100);
                    });
                    
                    // Also check after checkbox changes
                    table.on('change', '.custom-select-option', function() {
                        setTimeout(updateBulkUpdateButton, 50);
                    });
                }
            }
            
            // Initial check
            updateBulkUpdateButton();
        }, 1000);
    });

    function showBulkUpdateModal() {
        // Get selected etiket IDs
        const selectedIds = window.showArray ? window.showArray() : [];
        
        if (selectedIds.length === 0) {
            alert('لطفا حداقل یک اتیکت را انتخاب کنید');
            return;
        }

        eraseModalContent();

        const formHtml = `
        <form id="bulkUpdateForm">
            <input type="hidden" name="etiket_ids" id="etiket_ids">
            
            <div class="alert alert-info">
                <strong>تعداد اتیکت‌های انتخاب شده: ${selectedIds.length}</strong>
            </div>

            <div class="form-group">
                <label>نام</label>
                <input type="text" name="name" id="bulk-update-name" class="form-control" placeholder="مثال: دستبند طلا">
                <small class="form-text text-muted">فقط در صورت نیاز به تغییر پر کنید</small>
            </div>

            <div class="form-group">
                <label>محصول</label>
                <select name="product_id" id="bulk-update-product" class="form-control">
                    <option value="">-- انتخاب کنید --</option>
                </select>
                <small class="form-text text-muted">فقط در صورت نیاز به تغییر پر کنید</small>
            </div>

            <div class="form-group">
                <label>اجرت خرید (درصد خرید)</label>
                <input type="text" name="darsad_kharid" class="form-control" placeholder="مثال: 10">
                <small class="form-text text-muted">فقط در صورت نیاز به تغییر پر کنید</small>
            </div>

            <div class="form-group">
                <label>اجرت فروش (درصد اجرت)</label>
                <input type="text" name="ojrat" class="form-control" placeholder="مثال: 5">
                <small class="form-text text-muted">فقط در صورت نیاز به تغییر پر کنید</small>
            </div>

            <div class="form-group">
                <label>وزن (گرم)</label>
                <input type="number" name="weight" class="form-control" step="0.01" placeholder="مثال: 5.5">
                <small class="form-text text-muted">فقط در صورت نیاز به تغییر پر کنید</small>
            </div>

            <button type="button" onclick="submitBulkUpdate(this)" class="btn btn-primary">ویرایش</button>
        </form>
    `;

        appendToModalContent(formHtml);

        // Set the selected etiket IDs from hidden input
        $('#etiket_ids').val(JSON.stringify(selectedIds));

        showDynamicModal();
        
        // Initialize Select2 for product dropdown after modal is shown
        $('#dynamic-modal').on('shown.bs.modal', function() {
            // Destroy existing Select2 instance if it exists
            if ($('#bulk-update-product').hasClass('select2-hidden-accessible')) {
                $('#bulk-update-product').select2('destroy');
            }
            
            if ($('#bulk-update-product').length) {
                $('#bulk-update-product').select2({
                    placeholder: 'جستجو و انتخاب محصول',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#dynamic-modal'),
                    minimumInputLength: 1,
                    ajax: {
                        url: '{{ route("products.ajax.search.parents") }}',
                        dataType: 'json',
                        type: 'GET',
                        delay: 250,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: function (params) {
                            return {
                                q: params.term || ''
                            };
                        },
                        processResults: function (data) {
                            let results = [];
                            if (data && data.results && Array.isArray(data.results)) {
                                results = data.results;
                            } else if (Array.isArray(data)) {
                                results = data;
                            } else if (data && data.data && Array.isArray(data.data)) {
                                results = data.data;
                            }
                            
                            const products = results.filter(function(item) {
                                if (!item || !item.id) return false;
                                const itemId = item.id.toString();
                                return itemId.startsWith('Product:');
                            });
                            
                            return {
                                results: products.map(function(item) {
                                    const productId = item.id.toString().replace('Product:', '');
                                    return {
                                        id: productId,
                                        text: item.text || item.name || 'محصول'
                                    };
                                })
                            };
                        },
                        cache: true
                    }
                });
            }
        });
    }

    function submitBulkUpdate(button) {
        const form = $(button).closest('form')[0];
        const formData = new FormData(form);
        formData.append('_token', '{{ csrf_token() }}');
        
        // Get Select2 value for product_id - always set it (even if empty)
        const productId = $('#bulk-update-product').val();
        formData.set('product_id', productId || '');

        $.ajax({
            url: '{{ route('etikets.bulk_update') }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(res.message);
                    } else {
                        alert(res.message);
                    }
                    $('#dynamic-modal').modal('hide');
                    window.refreshTable();
                    // Hide button after update
                    setTimeout(function() {
                        $('#bulk-update-btn').hide();
                    }, 500);
                }
            },
            error: function (xhr) {
                const response = xhr.responseJSON;
                const errorMessage = response?.message || 'خطا در به‌روزرسانی اتیکت‌ها';
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMessage);
                } else {
                    alert(errorMessage);
                }
            }
        });
    }
</script>
<script>
    function filterEtikets() {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        // Add is_mojood filter
        params.append('is_mojood', '1');
        
        // Get all form values
        const name = formData.get('name') || '';
        const code = formData.get('code') || '';
        const weight = formData.get('weight') || '';
        const weightFrom = formData.get('weight_from') || '';
        const weightTo = formData.get('weight_to') || '';
        const sortColumn = formData.get('sort_column') || '';
        const sortDirection = formData.get('sort_direction') || 'desc';
        
        // Get all selected categories
        const categorySelect = document.getElementById('category_ids');
        const selectedCategories = Array.from(categorySelect.selectedOptions).map(option => option.value);
        
        // Build query string
        if (name) params.append('name', name);
        if (code) params.append('code', code);
        if (weight) params.append('weight', weight);
        if (weightFrom) params.append('weight_from', weightFrom);
        if (weightTo) params.append('weight_to', weightTo);
        if (sortColumn) params.append('sort_column', sortColumn);
        if (sortDirection) params.append('sort_direction', sortDirection);
        selectedCategories.forEach(catId => {
            params.append('category_ids[]', catId);
        });
        
        const queryString = params.toString();
        const url = '{{ route("table.etikets") }}' + '?' + queryString;
        
        if (typeof window.loadDataWithNewUrl === 'function') {
            window.loadDataWithNewUrl(url);
        } else if ($('#etikets-available-table').length) {
            $('#etikets-available-table').DataTable().ajax.url(url).load();
        }
    }

    function bulkDeleteEtikets() {
        // Get selected etiket IDs
        const selectedIds = window.showArray ? window.showArray() : [];
        
        if (selectedIds.length === 0) {
            alert('لطفا حداقل یک اتیکت را انتخاب کنید');
            return;
        }

        if (!confirm(`آیا از حذف ${selectedIds.length} اتیکت انتخاب شده مطمئن هستید؟ این عمل قابل بازگشت نیست.`)) {
            return;
        }

        const formData = new FormData();
        formData.append('etiket_ids', JSON.stringify(selectedIds));
        formData.append('_token', '{{ csrf_token() }}');

        $.ajax({
            url: '{{ route('etikets.bulk_delete') }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(res.message);
                    } else {
                        alert(res.message);
                    }
                    window.refreshTable();
                    // Hide buttons after delete
                    setTimeout(function() {
                        $('#bulk-update-btn').hide();
                        $('#bulk-delete-btn').hide();
                    }, 500);
                }
            },
            error: function (xhr) {
                const response = xhr.responseJSON;
                const errorMessage = response?.message || 'خطا در حذف اتیکت‌ها';
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMessage);
                } else {
                    alert(errorMessage);
                }
            }
        });
    }

    function exportEtikets() {
        // Get current filter values
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        // Add is_mojood filter
        params.append('is_mojood', '1');
        
        // Get all form values
        const name = formData.get('name') || '';
        const code = formData.get('code') || '';
        const weight = formData.get('weight') || '';
        const weightFrom = formData.get('weight_from') || '';
        const weightTo = formData.get('weight_to') || '';
        
        // Get all selected categories
        const categorySelect = document.getElementById('category_ids');
        const selectedCategories = Array.from(categorySelect.selectedOptions).map(option => option.value);
        
        // Build query string
        if (name) params.append('name', name);
        if (code) params.append('code', code);
        if (weight) params.append('weight', weight);
        if (weightFrom) params.append('weight_from', weightFrom);
        if (weightTo) params.append('weight_to', weightTo);
        selectedCategories.forEach(catId => {
            params.append('category_ids[]', catId);
        });
        
        // Get sort from form (select dropdown) - this takes priority
        const sortColumn = formData.get('sort_column') || '';
        const sortDirection = formData.get('sort_direction') || 'desc';
        
        if (sortColumn) {
            params.append('sort_column', sortColumn);
            params.append('sort_direction', sortDirection);
        } else {
            // Fallback to DataTable sorting if no form sort is selected
            if ($.fn.DataTable && $('#etikets-available-table').length) {
                const table = $('#etikets-available-table').DataTable();
                if (table) {
                    const order = table.order();
                    if (order.length > 0 && order[0].length >= 2) {
                        const columnIndex = order[0][0];
                        const direction = order[0][1];
                        // Get column data source
                        const column = table.column(columnIndex);
                        const dataSrc = column.dataSrc();
                        if (dataSrc) {
                            params.append('sort_column', dataSrc);
                            params.append('sort_direction', direction);
                        }
                    }
                }
            }
        }
        
        // Construct export URL
        const exportUrl = '{{ route('etikets.export') }}' + '?' + params.toString();
        
        // Download the file
        window.location.href = exportUrl;
        
        if (typeof toastr !== 'undefined') {
            toastr.success('در حال تهیه فایل اکسل...');
        }
    }
</script>
@endpush

