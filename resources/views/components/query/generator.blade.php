@props([
    'categories' => [],
    'targetInputId' => 'query',
])

<div class="query-generator-container">
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">سازنده کوئری</h5>
        </div>
        <div class="card-body">
            <!-- Categories -->
            <div class="form-group mb-3">
                <label>دسته بندی‌ها</label>
                <select class="form-control query-param query-select2-categories" id="query-category-ids" multiple>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->title }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">می‌توانید چند دسته بندی انتخاب کنید</small>
            </div>

            <!-- Search -->
            <div class="form-group mb-3">
                <label>جستجو</label>
                <input type="text" class="form-control query-param" id="query-search" placeholder="جستجو در نام محصول">
            </div>

            <!-- Price Range -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label>حداقل قیمت</label>
                    <input type="number" class="form-control query-param" id="query-from-price" placeholder="حداقل قیمت">
                </div>
                <div class="col-md-6">
                    <label>حداکثر قیمت</label>
                    <input type="number" class="form-control query-param" id="query-to-price" placeholder="حداکثر قیمت">
                </div>
            </div>

            <!-- Has Discount -->
            <div class="form-group mb-3">
                <label>
                    <input type="checkbox" class="query-param" id="query-has-discount" value="1">
                    فقط محصولات دارای تخفیف
                </label>
            </div>

            <!-- Sort By -->
            <div class="form-group mb-3">
                <label>مرتب‌سازی</label>
                <select class="form-control query-param" id="query-sort-by">
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
                <input type="number" class="form-control query-param" id="query-per-page" value="12" min="1" max="100">
            </div>

            <!-- Generated Query -->
            <div class="form-group mb-3">
                <label>کوئری تولید شده</label>
                <textarea class="form-control no_ck_editor" id="generated-query" rows="4" readonly style="font-family: monospace; direction: ltr; text-align: left;"></textarea>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="generateQuery()">تولید کوئری</button>
                <button type="button" class="btn btn-success" onclick="copyQuery()">کپی کوئری</button>
                <button type="button" class="btn btn-secondary" onclick="insertQuery()">درج در فیلد</button>
                <button type="button" class="btn btn-warning" onclick="clearQuery()">پاک کردن</button>
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
    function generateQuery() {
        const params = {};
        
        // Category IDs (using Select2)
        const categoryIds = $('#query-category-ids').val() || [];
        if (categoryIds.length > 0) {
            categoryIds.forEach((id, index) => {
                params[`category_ids[${index}]`] = id;
            });
        }
        
        // Search
        const search = document.getElementById('query-search').value.trim();
        if (search) {
            params['search'] = search;
        }
        
        // Price Range
        const fromPrice = document.getElementById('query-from-price').value.trim();
        if (fromPrice) {
            params['from_price'] = fromPrice;
        }
        
        const toPrice = document.getElementById('query-to-price').value.trim();
        if (toPrice) {
            params['to_price'] = toPrice;
        }
        
        // Has Discount
        if (document.getElementById('query-has-discount').checked) {
            params['hasDiscount'] = '1';
        }
        
        // Sort By
        const sortBy = document.getElementById('query-sort-by').value;
        if (sortBy) {
            params['sort_by'] = sortBy;
        }
        
        // Per Page
        const perPage = document.getElementById('query-per-page').value.trim();
        if (perPage && perPage !== '12') {
            params['per_page'] = perPage;
        }
        
        // Build query string
        const queryString = Object.keys(params)
            .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
            .join('&');
        
        document.getElementById('generated-query').value = queryString;
    }
    
    function copyQuery() {
        const queryField = document.getElementById('generated-query');
        queryField.select();
        document.execCommand('copy');
        toastr.success('کوئری کپی شد');
    }
    
    function insertQuery() {
        const query = document.getElementById('generated-query').value;
        const targetInput = document.getElementById('{{ $targetInputId }}');
        if (targetInput) {
            targetInput.value = query;
            toastr.success('کوئری در فیلد درج شد');
        } else {
            toastr.error('فیلد مقصد یافت نشد');
        }
    }
    
    function clearQuery() {
        // Clear Select2 categories
        $('#query-category-ids').val(null).trigger('change');
        
        // Clear all other inputs
        document.getElementById('query-search').value = '';
        document.getElementById('query-from-price').value = '';
        document.getElementById('query-to-price').value = '';
        document.getElementById('query-has-discount').checked = false;
        document.getElementById('query-sort-by').value = '';
        document.getElementById('query-per-page').value = '12';
        document.getElementById('generated-query').value = '';
    }
    
    // Initialize Select2 for categories
    function initializeSelect2() {
        const $select = $('#query-category-ids');
        
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
        $select.off('change.queryGenerator').on('change.queryGenerator', generateQuery);
    }
    
    // Auto-generate query on input change
    $(document).ready(function() {
        // Wait a bit to ensure Select2 library is loaded
        setTimeout(function() {
            // Initialize Select2
            initializeSelect2();
            
            const queryParams = document.querySelectorAll('.query-param');
            queryParams.forEach(param => {
                // Skip Select2 elements as they're handled separately
                if (!$(param).hasClass('query-select2-categories')) {
                    param.addEventListener('change', generateQuery);
                    param.addEventListener('input', generateQuery);
                }
            });
            
            // Initial generation
            generateQuery();
        }, 100);
    });
    
    // Re-initialize Select2 when modal is shown (for modals)
    $(document).on('shown.bs.modal', function(e) {
        const $modal = $(e.target);
        if ($modal.find('#query-category-ids').length) {
            setTimeout(function() {
                initializeSelect2();
            }, 100);
        }
    });
</script>
@endpush

