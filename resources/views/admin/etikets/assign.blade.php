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

        <div class="card">
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>محصول:</strong> {{ $product->name }}
                </div>

                <form id="assign-etiket-form" action="{{ route('products.etikets.store', $product) }}" method="POST">
                    @csrf
                    
                    <div class="form-group mt-4">
                        <label class="font-weight-bold">اتیکت‌ها</label>
                        <div id="etikets-list" class="border rounded p-3" style="width: 100%; min-height: 400px; height: auto; overflow-y: visible;">
                            <div class="row" id="etikets-row">
                                <p class="text-muted text-center mb-0 col-12">هیچ اتیکتی اضافه نشده است</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-info mt-2" onclick="addEtiket()">
                            <i class="fas fa-plus"></i> افزودن اتیکت
                        </button>
                        <small class="form-text text-muted d-block mt-2">کدهای اتیکت به صورت خودکار با پیشوند zr- ایجاد می‌شوند</small>
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
    let goldPrice = 0;

    // Fetch gold price on page load
    $(document).ready(function() {
        // Get gold price from PHP setting
        goldPrice = parseFloat('{{ (float) setting("gold_price") ?? 0 }}') || 0;
        window.goldPrice = goldPrice;
        
        console.log('Gold price loaded:', goldPrice);

        // Setup form submission
        $('#assign-etiket-form').on('submit', function(e) {
            e.preventDefault();
            
            // Validate at least one etiket exists
            const etiketCount = $('#etikets-row .etiket-item').length;
            if (etiketCount === 0) {
                alert('حداقل یک اتیکت باید اضافه شود');
                return false;
            }
            
            // Validate each etiket has weight
            let hasInvalidEtiket = false;
            $('#etikets-row .etiket-item').each(function() {
                const $etiketItem = $(this);
                const weight = parseFloat($etiketItem.find('.etiket-weight-input').val()) || 0;
                if (weight <= 0) {
                    hasInvalidEtiket = true;
                    $etiketItem.find('.etiket-weight-input').addClass('is-invalid');
                    return false; // break
                } else {
                    $etiketItem.find('.etiket-weight-input').removeClass('is-invalid');
                }
            });
            
            if (hasInvalidEtiket) {
                alert('تمام اتیکت‌ها باید وزن داشته باشند');
                return false;
            }
            
            const formData = new FormData();
            
            // Collect all etiket data
            $('#etikets-row .etiket-item').each(function() {
                const $etiketItem = $(this);
                const index = $etiketItem.data('index');
                const count = parseInt($etiketItem.find('.etiket-count-input').val()) || 1;
                const weight = parseFloat($etiketItem.find('.etiket-weight-input').val()) || 0;
                
                if (weight > 0) {
                    formData.append(`etikets[${index}][count]`, count);
                    formData.append(`etikets[${index}][weight]`, weight);
                }
            });
            
            // Add CSRF token
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(function() {
                            window.location.href = '{{ route("products.index") }}';
                        }, 1500);
                    } else {
                        toastr.error(response.message || 'خطا در ایجاد اتیکت‌ها');
                    }
                },
                error: function(xhr) {
                    console.error('Error creating etikets:', xhr);
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errorMessages = [];
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            errorMessages.push(value[0]);
                        });
                        toastr.error(errorMessages.join('\n'));
                    } else {
                        toastr.error('خطا در ایجاد اتیکت‌ها');
                    }
                }
            });
        });
    });

    // Add etiket
    function addEtiket() {
        etiketCounter++;
        const etiketHtml = '<div class="col-md-3 mb-3">' +
            '<div class="card etiket-item h-100" data-index="' + etiketCounter + '">' +
                '<div class="card-header d-flex justify-content-between align-items-center bg-light">' +
                    '<h6 class="mb-0">اتیکت ' + etiketCounter + '</h6>' +
                    '<button type="button" class="btn btn-sm btn-danger" onclick="removeEtiket(' + etiketCounter + ')">' +
                        '<i class="fas fa-times"></i>' +
                    '</button>' +
                '</div>' +
                '<div class="card-body">' +
                    '<div class="form-group">' +
                        '<label class="small font-weight-bold">تعداد</label>' +
                        '<input type="number" class="form-control etiket-count-input" name="etikets[' + etiketCounter + '][count]" placeholder="تعداد" min="1" value="1" data-index="' + etiketCounter + '" onchange="calculateEtiketPrice(' + etiketCounter + ')">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label class="small font-weight-bold">وزن (گرم)</label>' +
                        '<input type="number" class="form-control etiket-weight-input" name="etikets[' + etiketCounter + '][weight]" placeholder="وزن" step="0.01" data-index="' + etiketCounter + '" onchange="calculateEtiketPrice(' + etiketCounter + ')" oninput="calculateEtiketPrice(' + etiketCounter + ')" onkeypress="handleWeightEnter(event, ' + etiketCounter + ')">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label class="small font-weight-bold">قیمت (تومان)</label>' +
                        '<input type="number" class="form-control etiket-price-input" name="etikets[' + etiketCounter + '][price]" placeholder="قیمت" readonly data-index="' + etiketCounter + '">' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        if ($('#etikets-row p.text-muted').length > 0) {
            $('#etikets-row').html('');
        }
        $('#etikets-row').append(etiketHtml);
        
        // Calculate price if weight and ojrat are available
        setTimeout(function() {
            calculateEtiketPrice(etiketCounter);
        }, 100);
        
        // Return the new index for focusing
        return etiketCounter;
    }
    
    // Handle Enter key press in weight field
    function handleWeightEnter(event, currentIndex) {
        if (event.which === 13 || event.keyCode === 13) {
            event.preventDefault();
            // Add new etiket card
            const newIndex = addEtiket();
            // Focus on the weight field of the new card
            setTimeout(function() {
                $('.etiket-weight-input[data-index="' + newIndex + '"]').focus();
            }, 100);
        }
    }
    
    // Remove etiket
    function removeEtiket(index) {
        const etiketItem = $('.etiket-item[data-index="' + index + '"]').closest('.col-md-3');
        etiketItem.remove();
        if ($('#etikets-row .etiket-item').length === 0) {
            $('#etikets-row').html('<p class="text-muted text-center mb-0 col-12">هیچ اتیکتی اضافه نشده است</p>');
        }
    }

    // Calculate etiket price based on weight and ojrat
    function calculateEtiketPrice(index) {
        const $etiketItem = $('#etikets-row .etiket-item[data-index="' + index + '"]');
        const weight = parseFloat($etiketItem.find('.etiket-weight-input').val()) || 0;
        const ojrat = parseFloat('{{ $product->ojrat ?? 0 }}') || 0;
        const $priceInput = $etiketItem.find('.etiket-price-input');
        
        // Get gold price from global variable or setting
        let currentGoldPrice = goldPrice || window.goldPrice || parseFloat('{{ (float) setting("gold_price") ?? 0 }}') || 0;
        
        console.log('Calculating price for etiket', index, {
            weight: weight,
            ojrat: ojrat,
            goldPrice: currentGoldPrice
        });
        
        if (weight > 0 && currentGoldPrice > 0 && ojrat > 0) {
            // Formula: price = weight * (goldPrice * 1.01) * (1 + (ojrat / 100))
            const adjustedGoldPrice = currentGoldPrice * 1.01;
            let calculatedPrice = weight * adjustedGoldPrice * (1 + (ojrat / 100));
            
            // Round down to nearest thousand (last three digits become 0)
            calculatedPrice = Math.floor(calculatedPrice / 1000) * 1000;
            
            // Update price field
            $priceInput.val(calculatedPrice);
            console.log('Price calculated:', calculatedPrice);
        } else {
            // Clear price if required fields are missing
            $priceInput.val('');
        }
    }
    
    // Use event delegation for weight and count inputs in dynamically added cards
    $(document).on('input change', '.etiket-weight-input, .etiket-count-input', function() {
        const index = $(this).data('index');
        if (index) {
            calculateEtiketPrice(index);
        }
    });
</script>
@endpush

