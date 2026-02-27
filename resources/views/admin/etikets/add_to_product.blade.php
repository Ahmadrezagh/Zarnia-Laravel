@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'افزودن اتیکت به محصول'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'محصولات', 'url' => route('products.index')],
            ['label' => 'افزودن اتیکت به محصول']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <a href="{{ route('products.index') }}" class="btn btn-secondary mb-3">
                <i class="fas fa-arrow-right"></i> بازگشت به لیست محصولات
            </a>
        </x-slot>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form id="add-etiket-form" action="{{ route('etikets.add_to_product.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="product-select" class="font-weight-bold">نام محصول <span class="text-danger">*</span></label>
                                <select name="product_id" id="product-select" class="form-control" required>
                                    <option value="">-- انتخاب محصول --</option>
                                </select>
                            </div>
                            <div id="product-cover-preview" class="mt-2" style="display:none;">
                                <img id="product-cover-img" src="" alt="تصویر محصول"
                                     style="max-height:160px; max-width:160px; border-radius:8px; border:1px solid #dee2e6; object-fit:cover;">
                            </div>
                        </div>
                        <div class="col-md-12" id="gold-fields">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="darsad-kharid" class="font-weight-bold">درصد خرید</label>
                                        <input type="number" class="form-control" id="darsad-kharid" name="darsad_kharid" step="0.01" placeholder="درصد خرید">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ojrat" class="font-weight-bold">درصد فروش</label>
                                        <input type="number" class="form-control" id="ojrat" name="ojrat" step="0.01" placeholder="درصد فروش">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Etikets Sections (Side by Side) -->
                    <div class="form-group mt-4">
                        <div class="row">
                            <!-- Orderable After Out of Stock Etikets Section -->
                            <div class="col-md-12">
                                <label class="font-weight-bold">اتیکت‌های قابل فروش پس از اتمام موجودی</label>
                                <div id="orderable-etikets-list" class="border rounded p-3" style="width: 100%; min-height: 400px; height: auto; overflow-y: visible; border-color: #ffc107;">
                                    <div class="row" id="orderable-etikets-row">
                                        <p class="text-muted text-center mb-0 col-12">هیچ اتیکتی اضافه نشده است</p>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-warning mt-2" onclick="addOrderableEtiket()">
                                    <i class="fas fa-plus"></i> افزودن اتیکت قابل فروش پس از اتمام موجودی
                                </button>
                            </div>

                            <!-- Regular Etikets Section -->
                            <div class="col-md-12">
                                <label class="font-weight-bold">اتیکت‌ها</label>
                                <div id="etikets-list" class="border rounded p-3" style="width: 100%; min-height: 400px; height: auto; overflow-y: visible;">
                                    <div class="row" id="etikets-row">
                                        <p class="text-muted text-center mb-0 col-12">هیچ اتیکتی اضافه نشده است</p>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-info mt-2" onclick="addEtiket()">
                                    <i class="fas fa-plus"></i> افزودن اتیکت
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> ایجاد اتیکت‌ها
                        </button>
                        <a href="{{ route('products.index') }}" class="btn btn-secondary btn-lg">
                            انصراف
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Select related etikets for comprehensive products -->
        <div class="modal fade" id="comprehensiveEtiketModal" tabindex="-1" role="dialog" aria-labelledby="comprehensiveEtiketModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="comprehensiveEtiketModalLabel">انتخاب اتیکت‌های مرتبط</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="comprehensive-related-etikets-select" class="font-weight-bold">اتیکت‌های مرتبط را انتخاب کنید</label>
                            <select id="comprehensive-related-etikets-select" class="form-control" multiple></select>
                            <small class="form-text text-muted">
                                اتیکت‌هایی را انتخاب کنید که این اتیکت جامع بر اساس آن‌ها محاسبه می‌شود.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                        <button type="button" class="btn btn-primary" id="confirm-comprehensive-etiket-btn">تایید</button>
                    </div>
                </div>
            </div>
        </div>
    </x-page>

@endsection

@push('scripts')
<script>
    let etiketCounter = 0;
    let orderableEtiketCounter = 0;
    let goldPrice = 0;
    let isNoneGoldProduct = false;
    let isComprehensiveProduct = false;
    let comprehensiveModalIsOrderable = false;

    $(document).ready(function() {
        goldPrice = parseFloat('{{ (float) setting("gold_price") ?? 0 }}') || 0;
        window.goldPrice = goldPrice;

        $('#product-select').select2({
            placeholder: 'جستجو و انتخاب محصول',
            allowClear: true,
            width: '100%',
            minimumInputLength: 1,
            language: {
                inputTooShort: function() { return 'حداقل 1 کاراکتر وارد کنید'; },
                noResults: function() { return 'نتیجه‌ای یافت نشد'; },
                searching: function() { return 'در حال جستجو...'; }
            },
            ajax: {
                url: '{{ route("products.ajax.search.parents") }}',
                dataType: 'json',
                type: 'GET',
                delay: 250,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    let results = [];
                    if (data && data.results && Array.isArray(data.results)) results = data.results;
                    else if (Array.isArray(data)) results = data;
                    else if (data && data.data && Array.isArray(data.data)) results = data.data;
                    const products = results.filter(function(item) {
                        if (!item || !item.id) return false;
                        const itemId = item.id.toString();
                        return itemId.startsWith('Product:');
                    });
                    return {
                        results: products.map(function(item) {
                            const productId = item.id.toString().replace('Product:', '');
                            return { id: productId, text: item.text || item.name || 'محصول' };
                        })
                    };
                },
                cache: true
            }
        });

        $('#product-select').on('select2:select', function (e) {
            const productId = e.params.data.id;
            if (productId) loadProductData(productId);
        });

        $('#product-select').on('select2:clear', function () {
            isNoneGoldProduct = false;
            isComprehensiveProduct = false;
            $('#product-cover-preview').hide();
            $('#product-cover-img').attr('src', '');
            $('#gold-fields').show();
        });

        // Initialize Select2 for comprehensive related etikets modal
        $('#comprehensive-related-etikets-select').select2({
            placeholder: 'جستجو و انتخاب اتیکت‌های مرتبط',
            allowClear: true,
            width: '100%',
            minimumInputLength: 1,
            language: {
                inputTooShort: function() { return 'حداقل 1 کاراکتر وارد کنید'; },
                noResults: function() { return 'نتیجه‌ای یافت نشد'; },
                searching: function() { return 'در حال جستجو...'; }
            },
            ajax: {
                url: '{{ route("etikets.ajax.search") }}',
                dataType: 'json',
                type: 'GET',
                delay: 250,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    let results = [];
                    if (data && data.results && Array.isArray(data.results)) {
                        results = data.results;
                    }
                    return { results: results };
                },
                cache: true
            }
        });

        // Confirm button in comprehensive etiket modal
        $('#confirm-comprehensive-etiket-btn').on('click', function () {
            const relatedIds = $('#comprehensive-related-etikets-select').val() || [];
            if (!relatedIds.length) {
                alert('لطفاً حداقل یک اتیکت مرتبط انتخاب کنید.');
                return;
            }
            $('#comprehensiveEtiketModal').modal('hide');
            createComprehensiveEtiketCard(relatedIds, comprehensiveModalIsOrderable);
        });
    });

    function loadProductData(productId) {
        const url = '{{ url("/admin/products") }}/' + productId;
        $.ajax({
            url: url,
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                const product = response.data || response;

                isNoneGoldProduct = (product.type === 'none_gold');
                isComprehensiveProduct = (product.type === 'comprehensive_product');

                if (isNoneGoldProduct) {
                    $('#gold-fields').hide();
                } else {
                    $('#gold-fields').show();
                    if (product.darsad_kharid !== null && product.darsad_kharid !== undefined) {
                        $('#darsad-kharid').val(product.darsad_kharid);
                    }
                    if (product.ojrat !== null && product.ojrat !== undefined) {
                        $('#ojrat').val(product.ojrat);
                    }
                }

                if (product.image) {
                    $('#product-cover-img').attr('src', product.image);
                    $('#product-cover-preview').show();
                } else {
                    $('#product-cover-preview').hide();
                }

                // Update existing etiket cards to match product type
                updateEtiketCardPriceMode();

                $('#etikets-row .etiket-item').each(function() {
                    const index = $(this).data('index');
                    if (index) calculateEtiketPrice(index, false);
                });
                $('#orderable-etikets-row .etiket-item').each(function() {
                    const index = $(this).data('index');
                    if (index) calculateEtiketPrice(index, true);
                });
            }
        });
    }

    function updateEtiketCardPriceMode() {
        if (isNoneGoldProduct) {
            $('.etiket-price-input').prop('readonly', false).attr('placeholder', 'قیمت (تومان) *');
        } else {
            $('.etiket-price-input').prop('readonly', true).attr('placeholder', 'قیمت');
        }
    }

    function addEtiket() {
        // For comprehensive products, always use modal and create exactly one etiket
        if (isComprehensiveProduct) {
            openComprehensiveEtiketModal(false);
            return;
        }
        var countStr = prompt('تعداد اتیکت\u200cهای مورد نیاز را وارد کنید:', '1');
        if (countStr === null || countStr === '') return;
        var count = parseInt(countStr, 10);
        if (isNaN(count) || count < 1) {
            alert('لطفاً یک عدد معتبر (حداقل ۱) وارد کنید.');
            return;
        }
        var weightVal = 0;
        if (!isNoneGoldProduct) {
            var weightStr = prompt('وزن (گرم) را وارد کنید:', '');
            if (weightStr === null) return;
            weightVal = parseFloat(weightStr);
            if (isNaN(weightVal) || weightVal < 0) {
                alert('لطفاً وزن معتبر وارد کنید.');
                return;
            }
        }
        var priceVal = '';
        if (isNoneGoldProduct) {
            var priceStr = prompt('قیمت (تومان) را وارد کنید:', '');
            if (priceStr === null) return;
            priceVal = parseFloat(priceStr);
            if (isNaN(priceVal) || priceVal < 0) {
                alert('لطفاً قیمت معتبر وارد کنید.');
                return;
            }
        }
        var pending = $('#etikets-row .etiket-item').length;
        $.get('{{ route("etikets.next_numbers") }}', { pending_regular: pending }, function(res) {
            var start = res.regular_start || 7000;
            if ($('#etikets-row p.text-muted').length > 0) $('#etikets-row').html('');
            for (var i = 0; i < count; i++) {
                etiketCounter++;
                var num = start + i;
                var priceReadonly = isNoneGoldProduct ? '' : ' readonly';
                var priceLabel = isNoneGoldProduct ? 'قیمت (تومان) <span class="text-danger">*</span>' : 'قیمت (تومان)';
                var priceInputVal = isNoneGoldProduct ? priceVal : '';
                var weightField = isNoneGoldProduct ? '' :
                    '<div class="form-group">' +
                        '<label class="small font-weight-bold">وزن (گرم)</label>' +
                        '<input type="number" class="form-control etiket-weight-input" name="etikets[' + etiketCounter + '][weight]" placeholder="وزن" step="0.01" value="' + (weightVal > 0 ? weightVal : '') + '" data-index="' + etiketCounter + '" onchange="calculateEtiketPrice(' + etiketCounter + ')" oninput="calculateEtiketPrice(' + etiketCounter + ')">' +
                    '</div>';
                var weightHidden = isNoneGoldProduct
                    ? '<input type="hidden" name="etikets[' + etiketCounter + '][weight]" value="0">'
                    : '';
                var etiketHtml = '<div class="col-md-3 mb-3">' +
                    '<div class="card etiket-item h-100" data-index="' + etiketCounter + '">' +
                        '<div class="card-header d-flex justify-content-between align-items-center bg-light">' +
                            '<button type="button" class="btn btn-sm btn-danger" onclick="removeEtiket(' + etiketCounter + ')"><i class="fas fa-times"></i></button>' +
                        '</div>' +
                        '<div class="card-body">' +
                            '<div class="form-group">' +
                                '<label class="small font-weight-bold">شماره اتیکت</label>' +
                                '<input type="text" class="form-control etiket-code-input" name="etikets[' + etiketCounter + '][code]" placeholder="شماره" value="' + num + '" data-index="' + etiketCounter + '">' +
                            '</div>' +
                            weightField +
                            weightHidden +
                            '<div class="form-group">' +
                                '<label class="small font-weight-bold">' + priceLabel + '</label>' +
                                '<input type="number" class="form-control etiket-price-input" name="etikets[' + etiketCounter + '][price]" placeholder="قیمت"' + priceReadonly + ' value="' + priceInputVal + '" data-index="' + etiketCounter + '">' +
                            '</div>' +
                        '</div>' +
                    '</div></div>';
                $('#etikets-row').append(etiketHtml);
                if (!isNoneGoldProduct) {
                    setTimeout(function() { calculateEtiketPrice(etiketCounter, false); }, 50 * (i + 1));
                }
            }
        }).fail(function() {
            alert('خطا در دریافت شماره\u200cهای اتیکت.');
        });
    }

    function removeEtiket(index) {
        $('.etiket-item[data-index="' + index + '"]').closest('.col-md-3').remove();
        if ($('#etikets-row .etiket-item').length === 0) {
            $('#etikets-row').html('<p class="text-muted text-center mb-0 col-12">هیچ اتیکتی اضافه نشده است</p>');
        }
    }

    function addOrderableEtiket() {
        // For comprehensive products, always use modal and create exactly one etiket
        if (isComprehensiveProduct) {
            openComprehensiveEtiketModal(true);
            return;
        }
        var countStr = prompt('تعداد اتیکت\u200cهای قابل فروش پس از اتمام موجودی را وارد کنید:', '1');
        if (countStr === null || countStr === '') return;
        var count = parseInt(countStr, 10);
        if (isNaN(count) || count < 1) {
            alert('لطفاً یک عدد معتبر (حداقل ۱) وارد کنید.');
            return;
        }
        var weightVal = 0;
        if (!isNoneGoldProduct) {
            var weightStr = prompt('وزن (گرم) را وارد کنید:', '');
            if (weightStr === null) return;
            weightVal = parseFloat(weightStr);
            if (isNaN(weightVal) || weightVal < 0) {
                alert('لطفاً وزن معتبر وارد کنید.');
                return;
            }
        }
        var priceVal = '';
        if (isNoneGoldProduct) {
            var priceStr = prompt('قیمت (تومان) را وارد کنید:', '');
            if (priceStr === null) return;
            priceVal = parseFloat(priceStr);
            if (isNaN(priceVal) || priceVal < 0) {
                alert('لطفاً قیمت معتبر وارد کنید.');
                return;
            }
        }
        var pending = $('#orderable-etikets-row .etiket-item').length;
        $.get('{{ route("etikets.next_numbers") }}', { pending_orderable: pending }, function(res) {
            var start = res.orderable_start || 7000;
            if ($('#orderable-etikets-row p.text-muted').length > 0) $('#orderable-etikets-row').html('');
            for (var i = 0; i < count; i++) {
                orderableEtiketCounter++;
                var num = 's-' + (start + i);
                var priceReadonly = isNoneGoldProduct ? '' : ' readonly';
                var priceLabel = isNoneGoldProduct ? 'قیمت (تومان) <span class="text-danger">*</span>' : 'قیمت (تومان)';
                var priceInputVal = isNoneGoldProduct ? priceVal : '';
                var weightField = isNoneGoldProduct ? '' :
                    '<div class="form-group">' +
                        '<label class="small font-weight-bold">وزن (گرم)</label>' +
                        '<input type="number" class="form-control etiket-weight-input" name="orderable_etikets[' + orderableEtiketCounter + '][weight]" placeholder="وزن" step="0.01" value="' + (weightVal > 0 ? weightVal : '') + '" data-index="' + orderableEtiketCounter + '" data-orderable="true" onchange="calculateEtiketPrice(' + orderableEtiketCounter + ', true)" oninput="calculateEtiketPrice(' + orderableEtiketCounter + ', true)">' +
                    '</div>';
                var weightHidden = isNoneGoldProduct
                    ? '<input type="hidden" name="orderable_etikets[' + orderableEtiketCounter + '][weight]" value="0">'
                    : '';
                var etiketHtml = '<div class="col-md-3 mb-3">' +
                    '<div class="card etiket-item h-100" data-index="' + orderableEtiketCounter + '" style="border-color: #ffc107;">' +
                        '<div class="card-header d-flex justify-content-between align-items-center" style="background-color: #fff3cd;">' +
                            '<button type="button" class="btn btn-sm btn-danger" onclick="removeOrderableEtiket(' + orderableEtiketCounter + ')"><i class="fas fa-times"></i></button>' +
                        '</div>' +
                        '<div class="card-body">' +
                            '<div class="form-group">' +
                                '<label class="small font-weight-bold">شماره اتیکت</label>' +
                                '<input type="text" class="form-control etiket-code-input" name="orderable_etikets[' + orderableEtiketCounter + '][code]" placeholder="شماره (مثال: s-7000)" value="' + num + '" data-index="' + orderableEtiketCounter + '" data-orderable="true">' +
                            '</div>' +
                            weightField +
                            weightHidden +
                            '<div class="form-group">' +
                                '<label class="small font-weight-bold">' + priceLabel + '</label>' +
                                '<input type="number" class="form-control etiket-price-input" name="orderable_etikets[' + orderableEtiketCounter + '][price]" placeholder="قیمت"' + priceReadonly + ' value="' + priceInputVal + '" data-index="' + orderableEtiketCounter + '" data-orderable="true">' +
                            '</div>' +
                        '</div>' +
                    '</div></div>';
                $('#orderable-etikets-row').append(etiketHtml);
                if (!isNoneGoldProduct) {
                    setTimeout(function() { calculateEtiketPrice(orderableEtiketCounter, true); }, 50 * (i + 1));
                }
            }
        }).fail(function() {
            alert('خطا در دریافت شماره\u200cهای اتیکت.');
        });
    }

    function removeOrderableEtiket(index) {
        $('#orderable-etikets-row .etiket-item[data-index="' + index + '"]').closest('.col-md-3').remove();
        if ($('#orderable-etikets-row .etiket-item').length === 0) {
            $('#orderable-etikets-row').html('<p class="text-muted text-center mb-0 col-12">هیچ اتیکتی اضافه نشده است</p>');
        }
    }

    function calculateEtiketPrice(index, isOrderable) {
        if (isNoneGoldProduct || isComprehensiveProduct) return;
        const selector = isOrderable ? '#orderable-etikets-row' : '#etikets-row';
        const $etiketItem = $(selector + ' .etiket-item[data-index="' + index + '"]');
        const weight = parseFloat($etiketItem.find('.etiket-weight-input').val()) || 0;
        const ojrat = parseFloat($('#ojrat').val()) || 0;
        const $priceInput = $etiketItem.find('.etiket-price-input');
        let currentGoldPrice = goldPrice || window.goldPrice || parseFloat('{{ (float) setting("gold_price") ?? 0 }}') || 0;
        if (weight > 0 && currentGoldPrice > 0 && ojrat > 0) {
            const adjustedGoldPrice = currentGoldPrice * 1.01;
            let calculatedPrice = weight * adjustedGoldPrice * (1 + (ojrat / 100));
            calculatedPrice = Math.floor(calculatedPrice / 1000) * 1000;
            $priceInput.val(calculatedPrice);
        } else {
            $priceInput.val('');
        }
    }

    $(document).on('input change', '#ojrat', function() {
        $('#etikets-row .etiket-item').each(function() {
            const index = $(this).data('index');
            if (index) calculateEtiketPrice(index, false);
        });
        $('#orderable-etikets-row .etiket-item').each(function() {
            const index = $(this).data('index');
            if (index) calculateEtiketPrice(index, true);
        });
    });
    $(document).on('input change', '.etiket-weight-input:not([data-orderable="true"])', function() {
        const index = $(this).data('index');
        if (index) calculateEtiketPrice(index, false);
    });
    $(document).on('input change', '.etiket-weight-input[data-orderable="true"]', function() {
        const index = $(this).data('index');
        if (index) calculateEtiketPrice(index, true);
    });

    $('#add-etiket-form').on('submit', function() {
        const hasRegular   = $('#etikets-row .etiket-item').length > 0 &&
            (isNoneGoldProduct || isComprehensiveProduct || $('#etikets-row .etiket-weight-input').filter(function() { return parseFloat($(this).val()) > 0; }).length > 0);
        const hasOrderable = $('#orderable-etikets-row .etiket-item').length > 0 &&
            (isNoneGoldProduct || isComprehensiveProduct || $('#orderable-etikets-row .etiket-weight-input').filter(function() { return parseFloat($(this).val()) > 0; }).length > 0);
        if (!hasRegular && !hasOrderable) {
            alert('حداقل یک اتیکت (عادی یا قابل فروش پس از اتمام موجودی) با وزن معتبر اضافه کنید.');
            return false;
        }
        return true;
    });

    function openComprehensiveEtiketModal(isOrderable) {
        if (!isComprehensiveProduct) {
            return;
        }
        comprehensiveModalIsOrderable = !!isOrderable;
        $('#comprehensive-related-etikets-select').val(null).trigger('change');
        $('#comprehensiveEtiketModal').modal('show');
    }

    function createComprehensiveEtiketCard(relatedEtiketIds, isOrderable) {
        if (!Array.isArray(relatedEtiketIds) || !relatedEtiketIds.length) {
            return;
        }

        const isOrder = !!isOrderable;
        const rowSelector = isOrder ? '#orderable-etikets-row' : '#etikets-row';
        const pending = $(rowSelector + ' .etiket-item').length;
        const params = isOrder ? { pending_orderable: pending } : { pending_regular: pending };

        $.get('{{ route("etikets.next_numbers") }}', params, function(res) {
            let codeValue;
            let index;

            if (isOrder) {
                const start = res.orderable_start || 7000;
                orderableEtiketCounter++;
                index = orderableEtiketCounter;
                codeValue = 's-' + start;
            } else {
                const start = res.regular_start || 7000;
                etiketCounter++;
                index = etiketCounter;
                codeValue = start;
            }

            if ($(rowSelector + ' p.text-muted').length > 0) {
                $(rowSelector).html('');
            }

            const namePrefix = isOrder
                ? 'orderable_etikets[' + index + ']'
                : 'etikets[' + index + ']';

            let relatedInputsHtml = '';
            relatedEtiketIds.forEach(function(id) {
                relatedInputsHtml += '<input type="hidden" name="' + namePrefix + '[related_etikets][]" value="' + id + '">';
            });

            const weightHidden = '<input type="hidden" name="' + namePrefix + '[weight]" value="0">';

            const priceInput = '<div class="form-group">' +
                '<label class="small font-weight-bold">قیمت (تومان)</label>' +
                '<input type="number" class="form-control etiket-price-input" name="' + namePrefix + '[price]" value="0" readonly>' +
            '</div>';

            const relatedSummary = '<div class="form-group">' +
                '<label class="small font-weight-bold">اتیکت‌های مرتبط</label>' +
                '<p class="small mb-0 text-muted">تعداد ' + relatedEtiketIds.length + ' اتیکت انتخاب شده است.</p>' +
            '</div>';

            const cardStyle = isOrder ? ' style="border-color: #ffc107;"' : '';
            const headerStyle = isOrder ? ' style="background-color: #fff3cd;"' : '';

            const html = '<div class="col-md-3 mb-3">' +
                '<div class="card etiket-item h-100" data-index="' + index + '"' + cardStyle + '>' +
                    '<div class="card-header d-flex justify-content-between align-items-center"' + headerStyle + '>' +
                        '<button type="button" class="btn btn-sm btn-danger" onclick="' + (isOrder ? 'removeOrderableEtiket(' + index + ')' : 'removeEtiket(' + index + ')') + '"><i class="fas fa-times"></i></button>' +
                    '</div>' +
                    '<div class="card-body">' +
                        '<div class="form-group">' +
                            '<label class="small font-weight-bold">شماره اتیکت</label>' +
                            '<input type="text" class="form-control etiket-code-input" name="' + namePrefix + '[code]" value="' + codeValue + '"' + (isOrder ? ' data-orderable="true"' : '') + '>' +
                        '</div>' +
                        weightHidden +
                        priceInput +
                        relatedSummary +
                        relatedInputsHtml +
                    '</div>' +
                '</div>' +
            '</div>';

            $(rowSelector).append(html);
        }).fail(function() {
            alert('خطا در دریافت شماره\u200cهای اتیکت.');
        });
    }
</script>
@endpush
