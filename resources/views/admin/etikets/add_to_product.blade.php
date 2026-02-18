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
                        </div>
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
    </x-page>

@endsection

@push('scripts')
<script>
    let etiketCounter = 0;
    let orderableEtiketCounter = 0;
    let goldPrice = 0;

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
    });

    function loadProductData(productId) {
        const url = '{{ url("/admin/products") }}/' + productId;
        $.ajax({
            url: url,
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                const product = response.data || response;
                if (product.darsad_kharid !== null && product.darsad_kharid !== undefined) {
                    $('#darsad-kharid').val(product.darsad_kharid);
                }
                if (product.ojrat !== null && product.ojrat !== undefined) {
                    $('#ojrat').val(product.ojrat);
                }
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

    function addEtiket() {
        var countStr = prompt('تعداد اتیکت\u200cهای مورد نیاز را وارد کنید:', '1');
        if (countStr === null || countStr === '') return;
        var count = parseInt(countStr, 10);
        if (isNaN(count) || count < 1) {
            alert('لطفاً یک عدد معتبر (حداقل ۱) وارد کنید.');
            return;
        }
        var weightStr = prompt('وزن (گرم) را وارد کنید:', '');
        if (weightStr === null) return;
        var weightVal = parseFloat(weightStr);
        if (isNaN(weightVal) || weightVal < 0) {
            alert('لطفاً وزن معتبر وارد کنید.');
            return;
        }
        var pending = $('#etikets-row .etiket-item').length;
        $.get('{{ route("etikets.next_numbers") }}', { pending_regular: pending }, function(res) {
            var start = res.regular_start || 7000;
            if ($('#etikets-row p.text-muted').length > 0) $('#etikets-row').html('');
            for (var i = 0; i < count; i++) {
                etiketCounter++;
                var num = start + i;
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
                            '<div class="form-group">' +
                                '<label class="small font-weight-bold">وزن (گرم)</label>' +
                                '<input type="number" class="form-control etiket-weight-input" name="etikets[' + etiketCounter + '][weight]" placeholder="وزن" step="0.01" value="' + (weightVal > 0 ? weightVal : '') + '" data-index="' + etiketCounter + '" onchange="calculateEtiketPrice(' + etiketCounter + ')" oninput="calculateEtiketPrice(' + etiketCounter + ')">' +
                            '</div>' +
                            '<div class="form-group">' +
                                '<label class="small font-weight-bold">قیمت (تومان)</label>' +
                                '<input type="number" class="form-control etiket-price-input" name="etikets[' + etiketCounter + '][price]" placeholder="قیمت" readonly data-index="' + etiketCounter + '">' +
                            '</div>' +
                        '</div>' +
                    '</div></div>';
                $('#etikets-row').append(etiketHtml);
                setTimeout(function() { calculateEtiketPrice(etiketCounter, false); }, 50 * (i + 1));
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
        var countStr = prompt('تعداد اتیکت\u200cهای قابل فروش پس از اتمام موجودی را وارد کنید:', '1');
        if (countStr === null || countStr === '') return;
        var count = parseInt(countStr, 10);
        if (isNaN(count) || count < 1) {
            alert('لطفاً یک عدد معتبر (حداقل ۱) وارد کنید.');
            return;
        }
        var weightStr = prompt('وزن (گرم) را وارد کنید:', '');
        if (weightStr === null) return;
        var weightVal = parseFloat(weightStr);
        if (isNaN(weightVal) || weightVal < 0) {
            alert('لطفاً وزن معتبر وارد کنید.');
            return;
        }
        var pending = $('#orderable-etikets-row .etiket-item').length;
        $.get('{{ route("etikets.next_numbers") }}', { pending_orderable: pending }, function(res) {
            var start = res.orderable_start || 7000;
            if ($('#orderable-etikets-row p.text-muted').length > 0) $('#orderable-etikets-row').html('');
            for (var i = 0; i < count; i++) {
                orderableEtiketCounter++;
                var num = 's-' + (start + i);
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
                            '<div class="form-group">' +
                                '<label class="small font-weight-bold">وزن (گرم)</label>' +
                                '<input type="number" class="form-control etiket-weight-input" name="orderable_etikets[' + orderableEtiketCounter + '][weight]" placeholder="وزن" step="0.01" value="' + (weightVal > 0 ? weightVal : '') + '" data-index="' + orderableEtiketCounter + '" data-orderable="true" onchange="calculateEtiketPrice(' + orderableEtiketCounter + ', true)" oninput="calculateEtiketPrice(' + orderableEtiketCounter + ', true)">' +
                            '</div>' +
                            '<div class="form-group">' +
                                '<label class="small font-weight-bold">قیمت (تومان)</label>' +
                                '<input type="number" class="form-control etiket-price-input" name="orderable_etikets[' + orderableEtiketCounter + '][price]" placeholder="قیمت" readonly data-index="' + orderableEtiketCounter + '" data-orderable="true">' +
                            '</div>' +
                        '</div>' +
                    '</div></div>';
                $('#orderable-etikets-row').append(etiketHtml);
                setTimeout(function() { calculateEtiketPrice(orderableEtiketCounter, true); }, 50 * (i + 1));
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
        const hasRegular = $('#etikets-row .etiket-item').length > 0 && $('#etikets-row .etiket-weight-input').filter(function() { return parseFloat($(this).val()) > 0; }).length > 0;
        const hasOrderable = $('#orderable-etikets-row .etiket-item').length > 0 && $('#orderable-etikets-row .etiket-weight-input').filter(function() { return parseFloat($(this).val()) > 0; }).length > 0;
        if (!hasRegular && !hasOrderable) {
            alert('حداقل یک اتیکت (عادی یا قابل فروش پس از اتمام موجودی) با وزن معتبر اضافه کنید.');
            return false;
        }
        return true;
    });
</script>
@endpush
