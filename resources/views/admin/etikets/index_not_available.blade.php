@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'اتیکت‌های ناموجود'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'اتیکت‌های ناموجود']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <div class="row mb-3">
                <div class="col-12 mb-3">
                    <button type="button" id="bulk-update-btn" class="btn btn-primary" onclick="showBulkUpdateModal()" style="display: none;">ویرایش دسته جمعی</button>
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
                        <div class="col-12">
                            <button type="button" onclick="filterEtikets()" class="btn btn-success" style="width:100%">فیلتر</button>
                        </div>
                    </div>
                </form>
            </div>
        </x-slot>
        <x-dataTable
            :url="route('table.etikets') . '?is_mojood=0'"
            id="etikets-not-available-table"
            
            hasCheckbox="true"
            :columns="[
                            ['label' => 'کد', 'key' => 'code', 'type' => 'text', 'sortable' => true],
                            ['label' => 'اسم', 'key' => 'name', 'type' => 'text', 'sortable' => true],
                            ['label' => 'وزن', 'key' => 'weight', 'type' => 'text', 'sortable' => true],
                            ['label' => 'قیمت', 'key' => 'price', 'type' => 'text'],
                            ['label' => 'محصول', 'key' => 'product_name', 'type' => 'text'],
                            ['label' => 'دسته بندی ها', 'key' => 'categories', 'type' => 'text'],
                            ['label' => 'موجودی', 'key' => 'is_mojood', 'type' => 'text'],
                            ['label' => 'درصد اجرت', 'key' => 'ojrat', 'type' => 'text'],
                            ['label' => 'درصد خرید', 'key' => 'darsad_kharid', 'type' => 'text'],
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

    // Show/hide bulk update button based on selected rows
    function updateBulkUpdateButton() {
        const selectedIds = window.showArray ? window.showArray() : [];
        const btn = $('#bulk-update-btn');
        if (selectedIds.length > 0) {
            btn.fadeIn();
        } else {
            btn.fadeOut();
        }
    }

    // Override the updateHiddenInput function to also update the button
    $(document).ready(function() {
        // Wait for DataTable to initialize
        setTimeout(function() {
            // Hook into checkbox changes
            $(document).on('change', '#etikets-not-available-table .custom-select-option, #etikets-not-available-table #selectAll', function() {
                setTimeout(updateBulkUpdateButton, 50);
            });
            
            // Listen to DataTable draw events (when table redraws)
            if ($.fn.DataTable && $('#etikets-not-available-table').length) {
                const table = $('#etikets-not-available-table').DataTable();
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
    }

    function submitBulkUpdate(button) {
        const form = $(button).closest('form')[0];
        const formData = new FormData(form);
        formData.append('_token', '{{ csrf_token() }}');

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
        params.append('is_mojood', '0');
        
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
        
        const queryString = params.toString();
        const url = '{{ route("table.etikets") }}' + '?' + queryString;
        
        if (typeof window.loadDataWithNewUrl === 'function') {
            window.loadDataWithNewUrl(url);
        } else if ($('#etikets-not-available-table').length) {
            $('#etikets-not-available-table').DataTable().ajax.url(url).load();
        }
    }
</script>
@endpush

