@extends('layouts.panel')
@section('content')

    <x-breadcrumb :title="'افزودن ویژگی به اتیکت'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'اتیکت‌ها', 'url' => route('etikets.available')],
            ['label' => 'افزودن ویژگی به اتیکت']
      ]" />

    <x-page>
        <x-slot name="header">
            <a href="{{ route('etikets.available') }}" class="btn btn-secondary mb-3">
                <i class="fas fa-arrow-right"></i> بازگشت به لیست اتیکت‌ها
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
                <form id="add-attribute-form" action="{{ route('etikets.add_attribute.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="etiket_id" id="etiket-id-input">

                    <!-- Etiket select -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="etiket-select" class="font-weight-bold">
                                    شماره اتیکت <span class="text-danger">*</span>
                                </label>
                                <select id="etiket-select" class="form-control" required>
                                    <option value="">-- جستجو و انتخاب اتیکت --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Product info banner -->
                    <div id="product-info" class="alert alert-info d-none mb-3">
                        <i class="fas fa-box"></i>
                        محصول: <strong id="product-name-display"></strong>
                    </div>

                    <!-- Attribute inputs -->
                    <div id="attributes-section" class="d-none">
                        <hr>
                        <h6 class="font-weight-bold mb-3">ویژگی‌ها</h6>
                        <div id="attributes-loading" class="text-center py-3 d-none">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span class="mr-2">در حال بارگذاری ویژگی‌ها...</span>
                        </div>
                        <div id="attributes-empty" class="alert alert-warning d-none">
                            هیچ گروه ویژگی‌ای برای دسته‌بندی‌های این محصول تعریف نشده است.
                        </div>
                        <div id="attributes-inputs-row" class="row"></div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i> ذخیره ویژگی‌ها
                            </button>
                            <a href="{{ route('etikets.available') }}" class="btn btn-secondary btn-lg">
                                انصراف
                            </a>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </x-page>

@endsection

@push('scripts')
<script>
    $(document).ready(function () {

        // ── Select2 for etiket search ──────────────────────────────────────────
        $('#etiket-select').select2({
            placeholder: 'شماره یا نام محصول را جستجو کنید',
            allowClear: true,
            width: '100%',
            minimumInputLength: 1,
            language: {
                inputTooShort: function () { return 'حداقل ۱ کاراکتر وارد کنید'; },
                noResults:     function () { return 'نتیجه‌ای یافت نشد'; },
                searching:     function () { return 'در حال جستجو...'; }
            },
            ajax: {
                url: '{{ route("etikets.ajax.search") }}',
                dataType: 'json',
                type: 'GET',
                delay: 250,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) { return { results: data.results || [] }; },
                cache: true
            }
        });

        // ── On etiket selected ─────────────────────────────────────────────────
        $('#etiket-select').on('select2:select', function (e) {
            const etiketId = e.params.data.id;
            loadEtiketAttributes(etiketId);
        });

        $('#etiket-select').on('select2:clear', function () {
            resetForm();
        });
    });

    function resetForm() {
        $('#etiket-id-input').val('');
        $('#product-info').addClass('d-none');
        $('#attributes-section').addClass('d-none');
        $('#attributes-inputs-row').html('');
        $('#attributes-empty').addClass('d-none');
    }

    function loadEtiketAttributes(etiketId) {
        resetForm();
        $('#etiket-id-input').val(etiketId);
        $('#attributes-loading').removeClass('d-none');
        $('#attributes-section').removeClass('d-none');

        $.ajax({
            url: '{{ url("/admin/etikets") }}/' + etiketId + '/attribute-data',
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                $('#attributes-loading').addClass('d-none');

                // Show product info
                if (response.product) {
                    $('#product-name-display').text(response.product.name);
                    $('#product-info').removeClass('d-none');
                }

                const attributes = response.attributes || [];
                if (attributes.length === 0) {
                    $('#attributes-empty').removeClass('d-none');
                    return;
                }

                renderAttributeInputs(attributes);
            },
            error: function () {
                $('#attributes-loading').addClass('d-none');
                $('#attributes-empty').removeClass('d-none').text('خطا در بارگذاری ویژگی‌ها.');
            }
        });
    }

    function renderAttributeInputs(attributes) {
        const $row = $('#attributes-inputs-row');
        $row.html('');

        attributes.forEach(function (attr, index) {
            const inputName = 'attributes[' + index + '][value]';
            const idInputName = 'attributes[' + index + '][attribute_id]';
            const inputId = 'attr-input-' + attr.id;

            const prefix   = attr.prefix_sentence  ? '<span class="input-group-text">' + $('<div>').text(attr.prefix_sentence).html()  + '</span>' : '';
            const postfix  = attr.postfix_sentence ? '<span class="input-group-text">' + $('<div>').text(attr.postfix_sentence).html() + '</span>' : '';
            const hasAddon = prefix || postfix;

            const inputHtml = hasAddon
                ? `<div class="input-group">
                       ${prefix}
                       <input type="text" class="form-control" id="${inputId}" name="${inputName}" value="${escapeHtml(attr.value)}" placeholder="${escapeHtml(attr.name)}">
                       ${postfix}
                   </div>`
                : `<input type="text" class="form-control" id="${inputId}" name="${inputName}" value="${escapeHtml(attr.value)}" placeholder="${escapeHtml(attr.name)}">`;

            const col = `
                <div class="col-md-4 mb-3">
                    <div class="form-group mb-0">
                        <label for="${inputId}" class="small font-weight-bold">${escapeHtml(attr.name)}</label>
                        <input type="hidden" name="${idInputName}" value="${attr.id}">
                        ${inputHtml}
                    </div>
                </div>`;

            $row.append(col);
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ── Prevent submit without etiket selected ────────────────────────────────
    $('#add-attribute-form').on('submit', function () {
        if (!$('#etiket-id-input').val()) {
            alert('لطفاً ابتدا یک اتیکت انتخاب کنید.');
            return false;
        }
        return true;
    });
</script>
@endpush
