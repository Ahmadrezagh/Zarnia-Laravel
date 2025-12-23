@props([
    'categories' => [],
    'targetInputId' => 'query',
])

@php
    $instanceId = 'query-gen-' . str_replace(['-', '_'], '', $targetInputId);
@endphp

<div class="query-generator-container" data-instance-id="{{ $instanceId }}">
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">سازنده کوئری</h5>
        </div>
        <div class="card-body">
            <!-- Categories -->
            <div class="form-group mb-3">
                <label>دسته بندی‌ها</label>
                <select class="form-control query-param query-select2-categories" id="{{ $instanceId }}-category-ids" data-target-input="{{ $targetInputId }}" multiple>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->title }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">می‌توانید چند دسته بندی انتخاب کنید</small>
            </div>

            <!-- Search -->
            <div class="form-group mb-3">
                <label>جستجو</label>
                <input type="text" class="form-control query-param" id="{{ $instanceId }}-search" placeholder="جستجو در نام محصول">
            </div>

            <!-- Price Range -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label>حداقل قیمت</label>
                    <input type="number" class="form-control query-param" id="{{ $instanceId }}-from-price" placeholder="حداقل قیمت">
                </div>
                <div class="col-md-6">
                    <label>حداکثر قیمت</label>
                    <input type="number" class="form-control query-param" id="{{ $instanceId }}-to-price" placeholder="حداکثر قیمت">
                </div>
            </div>

            <!-- Has Discount -->
            <div class="form-group mb-3">
                <label>
                    <input type="checkbox" class="query-param" id="{{ $instanceId }}-has-discount" value="1">
                    فقط محصولات دارای تخفیف
                </label>
            </div>

            <!-- Sort By -->
            <div class="form-group mb-3">
                <label>مرتب‌سازی</label>
                <select class="form-control query-param" id="{{ $instanceId }}-sort-by">
                    <option value="">بدون مرتب‌سازی</option>
                    <option value="latest">جدیدترین</option>
                    <option value="oldest">قدیمی‌ترین</option>
                    <option value="price_asc">قیمت: کم به زیاد</option>
                    <option value="price_desc">قیمت: زیاد به کم</option>
                    <option value="name_asc">نام: الفبایی صعودی</option>
                    <option value="name_desc">نام: الفبایی نزولی</option>
                    <option value="random">تصادفی</option>
                    <option value="most_favorite">محبوب‌ترین</option>
                </select>
            </div>

            <!-- Per Page -->
            <div class="form-group mb-3">
                <label>تعداد در هر صفحه</label>
                <input type="number" class="form-control query-param" id="{{ $instanceId }}-per-page" value="12" min="1" max="100">
            </div>

            <!-- Generated Query -->
            <div class="form-group mb-3">
                <label>کوئری تولید شده</label>
                <textarea class="form-control no_ck_editor" id="{{ $instanceId }}-generated-query" rows="4" readonly style="font-family: monospace; direction: ltr; text-align: left;"></textarea>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="generateQuery{{ $instanceId }}()">تولید کوئری</button>
                <button type="button" class="btn btn-success" onclick="copyQuery{{ $instanceId }}()">کپی کوئری</button>
                <button type="button" class="btn btn-secondary" onclick="insertQuery{{ $instanceId }}()">درج در فیلد</button>
                <button type="button" class="btn btn-warning" onclick="clearQuery{{ $instanceId }}()">پاک کردن</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .query-generator-container .card {
        border: 1px solid #ddd;
    }
    .query-generator-container .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #ddd;
    }
    #generated-query {
        background-color: #f8f9fa;
    }
    /* Ensure Select2 search box is visible in dropdown */
    .select2-container--default .select2-search--dropdown {
        display: block !important;
        padding: 5px !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #aaa !important;
        padding: 6px 12px !important;
        width: 100% !important;
        border-radius: 4px !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: #5897fb !important;
        outline: 0 !important;
    }
</style>
@endpush

@push('scripts')
<script>
    (function() {
        const instanceId = '{{ $instanceId }}';
        const targetInputId = '{{ $targetInputId }}';
        
        // Generate query function for this instance
        window['generateQuery' + instanceId] = function() {
            const params = {};
            
            // Category IDs (using Select2)
            const categoryIds = $('#' + instanceId + '-category-ids').val() || [];
            if (categoryIds.length > 0) {
                categoryIds.forEach((id, index) => {
                    params[`category_ids[${index}]`] = id;
                });
            }
            
            // Search
            const search = document.getElementById(instanceId + '-search').value.trim();
            if (search) {
                params['search'] = search;
            }
            
            // Price Range
            const fromPrice = document.getElementById(instanceId + '-from-price').value.trim();
            if (fromPrice) {
                params['from_price'] = fromPrice;
            }
            
            const toPrice = document.getElementById(instanceId + '-to-price').value.trim();
            if (toPrice) {
                params['to_price'] = toPrice;
            }
            
            // Has Discount
            if (document.getElementById(instanceId + '-has-discount').checked) {
                params['hasDiscount'] = '1';
            }
            
            // Sort By
            const sortBy = document.getElementById(instanceId + '-sort-by').value;
            if (sortBy) {
                params['sort_by'] = sortBy;
            }
            
            // Per Page
            const perPage = document.getElementById(instanceId + '-per-page').value.trim();
            if (perPage && perPage !== '12') {
                params['per_page'] = perPage;
            }
            
            // Build query string
            const queryString = Object.keys(params)
                .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
                .join('&');
            
            document.getElementById(instanceId + '-generated-query').value = queryString;
        };
        
        // Copy query function for this instance
        window['copyQuery' + instanceId] = function() {
            const queryField = document.getElementById(instanceId + '-generated-query');
            queryField.select();
            document.execCommand('copy');
            toastr.success('کوئری کپی شد');
        };
        
        // Insert query function for this instance
        window['insertQuery' + instanceId] = function() {
            const query = document.getElementById(instanceId + '-generated-query').value;
            const targetInput = document.getElementById(targetInputId);
            if (targetInput) {
                targetInput.value = query;
                toastr.success('کوئری در فیلد درج شد');
            } else {
                toastr.error('فیلد مقصد یافت نشد');
            }
        };
        
        // Clear query function for this instance
        window['clearQuery' + instanceId] = function() {
            // Clear Select2 categories
            $('#' + instanceId + '-category-ids').val(null).trigger('change');
            
            // Clear all other inputs
            document.getElementById(instanceId + '-search').value = '';
            document.getElementById(instanceId + '-from-price').value = '';
            document.getElementById(instanceId + '-to-price').value = '';
            document.getElementById(instanceId + '-has-discount').checked = false;
            document.getElementById(instanceId + '-sort-by').value = '';
            document.getElementById(instanceId + '-per-page').value = '12';
            document.getElementById(instanceId + '-generated-query').value = '';
        };
        
        // Initialize Select2 for this instance
        function initializeSelect2() {
            const $select = $('#' + instanceId + '-category-ids');
            
            // Destroy existing Select2 if any
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            
            // Find parent modal if exists
            const $modal = $select.closest('.modal');
            const dropdownParent = $modal.length ? $modal : $('body');
            
            // Initialize Select2 with search enabled
            $select.select2({
                placeholder: 'جستجو و انتخاب دسته بندی‌ها',
                allowClear: true,
                dir: 'rtl',
                width: '100%',
                dropdownParent: dropdownParent,
                minimumInputLength: 0, // Show search box immediately
                language: {
                    noResults: function() {
                        return "نتیجه‌ای یافت نشد";
                    },
                    searching: function() {
                        return "در حال جستجو...";
                    }
                }
            });
            
            // Trigger query generation on change
            $select.off('change.queryGenerator').on('change.queryGenerator', window['generateQuery' + instanceId]);
        }
        
        // Auto-generate query on input change
        $(document).ready(function() {
            // Wait a bit to ensure Select2 library is loaded
            setTimeout(function() {
                // Initialize Select2
                initializeSelect2();
                
                // Get all query params in this instance
                const $container = $('[data-instance-id="' + instanceId + '"]');
                const queryParams = $container.find('.query-param').not('.query-select2-categories');
                
                queryParams.on('change input', window['generateQuery' + instanceId]);
                
                // Initial generation
                window['generateQuery' + instanceId]();
            }, 100);
        });
        
        // Re-initialize Select2 when modal is shown (for modals)
        $(document).on('shown.bs.modal', function(e) {
            const $modal = $(e.target);
            const $select = $modal.find('#' + instanceId + '-category-ids');
            if ($select.length && !$select.hasClass('select2-hidden-accessible')) {
                setTimeout(function() {
                    initializeSelect2();
                }, 100);
            }
        });
    })();
</script>
@endpush

