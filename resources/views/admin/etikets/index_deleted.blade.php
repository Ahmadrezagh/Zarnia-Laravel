@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'اتیکت‌های حذف شده'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'اتیکت‌های حذف شده']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <div class="row mb-3">
                <div class="col-6 mb-3">
                    <button type="button" id="restore-btn" class="btn btn-success" onclick="restoreEtikets()" style="display: none;">بازیابی</button>
                </div>
                <div class="col-6 mb-3">
                    <button type="button" id="force-delete-btn" class="btn btn-danger" onclick="forceDeleteEtikets()" style="display: none;">حذف دائمی</button>
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
                        <div class="col-12 mt-3">
                            <button type="button" onclick="applyFilters()" class="btn btn-primary">اعمال فیلتر</button>
                            <button type="button" onclick="clearFilters()" class="btn btn-secondary">پاک کردن فیلترها</button>
                        </div>
                    </div>
                </form>
            </div>
        </x-slot>

        <x-dataTable
            :columns="[
                ['label' => 'کد', 'key' => 'code', 'type' => 'text'],
                ['label' => 'اسم', 'key' => 'name', 'type' => 'text'],
                ['label' => 'وزن', 'key' => 'weight', 'type' => 'text'],
                ['label' => 'قیمت', 'key' => 'price', 'type' => 'text'],
                ['label' => 'محصول', 'key' => 'product_name', 'type' => 'text'],
                ['label' => 'دسته بندی ها', 'key' => 'categories', 'type' => 'text'],
                ['label' => 'موجودی', 'key' => 'is_mojood', 'type' => 'text'],
                ['label' => 'درصد اجرت', 'key' => 'ojrat', 'type' => 'text'],
                ['label' => 'درصد خرید', 'key' => 'darsad_kharid', 'type' => 'text'],
                ['label' => 'تاریخ ایجاد', 'key' => 'created_at', 'type' => 'text'],
            ]"
            :url="route('table.etikets', ['is_mojood' => '', 'deleted' => 1] + request()->all())"
            :hasCheckbox="true"
        />

    </x-page>

    <script>
        let selectedEtikets = [];

        // Update selected etikets when checkbox changes
        function updateSelectedEtikets() {
            const checkboxes = document.querySelectorAll('input[name="row_checkbox"]:checked');
            selectedEtikets = Array.from(checkboxes).map(cb => cb.value);
            
            // Show/hide action buttons based on selection
            const restoreBtn = document.getElementById('restore-btn');
            const forceDeleteBtn = document.getElementById('force-delete-btn');
            
            if (selectedEtikets.length > 0) {
                restoreBtn.style.display = 'inline-block';
                forceDeleteBtn.style.display = 'inline-block';
            } else {
                restoreBtn.style.display = 'none';
                forceDeleteBtn.style.display = 'none';
            }
        }

        // Listen for checkbox changes
        $(document).on('change', 'input[name="row_checkbox"]', updateSelectedEtikets);
        $(document).on('change', '#select-all-checkbox', updateSelectedEtikets);

        // Restore etikets
        function restoreEtikets() {
            if (selectedEtikets.length === 0) {
                alert('لطفا حداقل یک اتیکت را انتخاب کنید');
                return;
            }

            if (!confirm(`آیا از بازیابی ${selectedEtikets.length} اتیکت اطمینان دارید؟`)) {
                return;
            }

            $.ajax({
                url: '{{ route('etikets.bulk_restore') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    etiket_ids: JSON.stringify(selectedEtikets)
                },
                success: function(response) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message);
                    } else {
                        alert(response.message);
                    }
                    
                    // Reload table
                    if (typeof dataTable !== 'undefined') {
                        dataTable.ajax.reload(null, false);
                    }
                    
                    // Reset selection
                    selectedEtikets = [];
                    updateSelectedEtikets();
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'خطا در بازیابی اتیکت‌ها';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        }

        // Force delete etikets
        function forceDeleteEtikets() {
            if (selectedEtikets.length === 0) {
                alert('لطفا حداقل یک اتیکت را انتخاب کنید');
                return;
            }

            if (!confirm(`آیا از حذف دائمی ${selectedEtikets.length} اتیکت اطمینان دارید؟ این عملیات قابل بازگشت نیست!`)) {
                return;
            }

            $.ajax({
                url: '{{ route('etikets.bulk_force_delete') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    etiket_ids: JSON.stringify(selectedEtikets)
                },
                success: function(response) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message);
                    } else {
                        alert(response.message);
                    }
                    
                    // Reload table
                    if (typeof dataTable !== 'undefined') {
                        dataTable.ajax.reload(null, false);
                    }
                    
                    // Reset selection
                    selectedEtikets = [];
                    updateSelectedEtikets();
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'خطا در حذف دائمی اتیکت‌ها';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        }

        function applyFilters() {
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            const params = new URLSearchParams();

            formData.forEach((value, key) => {
                if (value) {
                    params.append(key, value);
                }
            });

            // Add deleted parameter
            params.append('deleted', '1');

            const queryString = params.toString();
            const newUrl = queryString ? `{{ route('table.etikets') }}?${queryString}` : `{{ route('table.etikets') }}?deleted=1`;

            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.url(newUrl).load();
            }
        }

        function clearFilters() {
            document.getElementById('filterForm').reset();
            $('#category_ids').val(null).trigger('change');
            
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.url('{{ route('table.etikets', ['deleted' => 1]) }}').load();
            }
        }

        $(document).ready(function() {
            $('#category_ids').select2({
                placeholder: 'انتخاب دسته بندی',
                allowClear: true
            });

            // Listen for DataTable draw event to update button visibility
            if (typeof dataTable !== 'undefined') {
                dataTable.on('draw', function() {
                    updateSelectedEtikets();
                });
            }
        });
    </script>

@endsection

