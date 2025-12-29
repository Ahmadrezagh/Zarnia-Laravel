@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'افزودن محصول طلا'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'محصولات', 'url' => route('products.index')],
            ['label' => 'افزودن محصول طلا']
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
                <form id="create-product-form" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <!-- Left Column: Image Upload Section -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">تصویر اصلی محصول</label>
                                <div class="image-upload-container mb-3">
                                    <div class="image-preview-large" id="cover-image-preview" style="width: 100%; height: 250px; border: 2px dashed #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; cursor: pointer; position: relative;">
                                        <div class="text-center">
                                            <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">برای آپلود تصویر کلیک کنید</p>
                                        </div>
                                        <input type="file" id="cover-image-input" name="cover_image" accept="image/*" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer;" onchange="previewCoverImage(this)">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="font-weight-bold">گالری تصاویر</label>
                                <div class="gallery-preview-container d-flex gap-2 mb-2">
                                    <div class="gallery-item-preview" id="gallery-preview-1" style="width: 80px; height: 80px; border: 2px dashed #ddd; border-radius: 6px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; cursor: pointer; position: relative;">
                                        <i class="fas fa-plus text-muted"></i>
                                        <input type="file" name="gallery[]" accept="image/*" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer;" onchange="previewGalleryImage(this, 1)">
                                    </div>
                                    <div class="gallery-item-preview" id="gallery-preview-2" style="width: 80px; height: 80px; border: 2px dashed #ddd; border-radius: 6px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; cursor: pointer; position: relative;">
                                        <i class="fas fa-plus text-muted"></i>
                                        <input type="file" name="gallery[]" accept="image/*" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer;" onchange="previewGalleryImage(this, 2)">
                                    </div>
                                    <div class="gallery-item-preview" id="gallery-preview-3" style="width: 80px; height: 80px; border: 2px dashed #ddd; border-radius: 6px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; cursor: pointer; position: relative;">
                                        <i class="fas fa-plus text-muted"></i>
                                        <input type="file" name="gallery[]" accept="image/*" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer;" onchange="previewGalleryImage(this, 3)">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column: Form Fields -->
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="product-name" class="font-weight-bold">نام محصول <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="product-name" name="name" required placeholder="مثال: دستبند طلا دانژه">
                            </div>
                            
                            <div class="form-group">
                                <label for="parent-product" class="font-weight-bold">محصول والد (اختیاری)</label>
                                <select name="parent_id" id="parent-product" class="form-control">
                                    <option value="">-- انتخاب محصول والد --</option>
                                </select>
                                <small class="form-text text-muted">در صورت نیاز به ایجاد محصول زیرمجموعه، محصول والد را انتخاب کنید</small>
                                <div id="parent-product-url" class="mt-2" style="display: none;">
                                    <small class="text-muted">لینک محصول والد: </small>
                                    <a href="#" id="parent-product-url-link" target="_blank" class="text-primary"></a>
                                </div>
                            </div>
                            
                            <div id="gold-product-fields">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="darsad-kharid" class="font-weight-bold">اجرت خرید (%)</label>
                                            <input type="number" class="form-control" id="darsad-kharid" name="darsad_kharid" step="0.01" placeholder="درصد اجرت خرید">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="ojrat" class="font-weight-bold">اجرت فروش (%)</label>
                                            <input type="number" class="form-control" id="ojrat" name="ojrat" step="0.01" placeholder="درصد اجرت فروش">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="discount-percentage" class="font-weight-bold">درصد تخفیف (%)</label>
                                        <input type="number" class="form-control" id="discount-percentage" name="discount_percentage" step="0.01" placeholder="درصد تخفیف">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="product-categories" class="font-weight-bold">دسته بندی <span class="text-danger">*</span></label>
                                <select name="category_ids[]" id="product-categories" class="form-control" multiple required>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->title }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">حداقل یک دسته بندی باید انتخاب شود</small>
                            </div>

                            <div class="form-group">
                                <label for="attribute-group" class="font-weight-bold">گروه ویژگی</label>
                                <input type="text" class="form-control" id="attribute-group" name="attribute_group" placeholder="نام گروه ویژگی را وارد کنید">
                            </div>
                            
                            <div class="form-group">
                                <label for="product-description" class="font-weight-bold">توضیحات</label>
                                <textarea class="form-control" id="product-description" name="description" rows="3" placeholder="توضیحات محصول"></textarea>
                            </div>
                            
                            <!-- Etikets Section -->
                            <div class="form-group mt-4">
                                <label class="font-weight-bold">اتیکت‌ها</label>
                                <div id="etikets-list" class="border rounded p-3" style="width: 100%; min-height: 400px; height: auto; overflow-y: visible;">
                                    <p class="text-muted text-center mb-0">هیچ اتیکتی اضافه نشده است</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-info mt-2" onclick="addEtiket()">
                                    <i class="fas fa-plus"></i> افزودن اتیکت
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> ایجاد محصول
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
        // Get gold price from API or global variable
        $.ajax({
            url: '{{ route("products.ajax.search") }}',
            method: 'GET',
            data: { q: '', available_only: '0' },
            success: function(data) {
                // Try to get gold price from response or use default
                if (data && data.gold_price) {
                    goldPrice = parseFloat(data.gold_price);
                }
            }
        });

        // Initialize Select2 for categories
        $('#product-categories').select2({
            placeholder: 'دسته‌بندی‌ها را انتخاب کنید',
            allowClear: true,
            width: '100%'
        });
        
        // Initialize Select2 for parent product
        $('#parent-product').select2({
            placeholder: 'جستجو و انتخاب محصول والد',
            allowClear: true,
            width: '100%',
            minimumInputLength: 1,
            language: {
                inputTooShort: function() {
                    return 'حداقل 1 کاراکتر وارد کنید';
                },
                noResults: function() {
                    return 'نتیجه‌ای یافت نشد';
                },
                searching: function() {
                    return 'در حال جستجو...';
                }
            },
            ajax: {
                url: '{{ route("products.ajax.search") }}',
                dataType: 'json',
                type: 'GET',
                delay: 250,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: function (params) {
                    return {
                        q: params.term || '',
                        available_only: '0'
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

        // When parent product is selected, fill form with parent data
        $('#parent-product').on('select2:select', function (e) {
            const parentId = e.params.data.id;
            if (parentId) {
                loadParentProductData(parentId);
            }
        });

        // When parent product is cleared, optionally clear form or keep data
        $('#parent-product').on('select2:clear', function (e) {
            // Hide parent product URL
            $('#parent-product-url').hide();
            $('#parent-product-url-link').attr('href', '#').text('');
        });

        // Function to load parent product data and fill form
        function loadParentProductData(productId) {
            $.ajax({
                url: '{{ route("products.index") }}/' + productId,
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    const product = response.data || response;
                    
                    // Display parent product URL
                    if (product.frontend_url) {
                        $('#parent-product-url-link').attr('href', product.frontend_url).text(product.frontend_url);
                        $('#parent-product-url').show();
                    } else {
                        $('#parent-product-url').hide();
                    }
                    
                    // Fill name
                    if (product.name) {
                        $('#product-name').val(product.name);
                    }
                    
                    // Fill darsad_kharid (execution purchase percentage)
                    if (product.darsad_kharid !== null && product.darsad_kharid !== undefined) {
                        $('#darsad-kharid').val(product.darsad_kharid);
                    }
                    
                    // Fill ojrat (execution sale percentage)
                    if (product.ojrat !== null && product.ojrat !== undefined) {
                        $('#ojrat').val(product.ojrat);
                    }
                    
                    // Fill discount_percentage
                    if (product.discount_percentage !== null && product.discount_percentage !== undefined) {
                        $('#discount-percentage').val(product.discount_percentage);
                    }
                    
                    // Fill description
                    if (product.description) {
                        $('#product-description').val(product.description);
                    }
                    
                    // Fill attribute group
                    if (product.attribute_group_str) {
                        $('#attribute-group').val(product.attribute_group_str);
                    }
                    
                    // Fill categories
                    if (product.category_ids && product.category_ids.length > 0) {
                        $('#product-categories').val(product.category_ids).trigger('change');
                    }
                    
                    // Load cover image
                    if (product.image) {
                        const $preview = $('#cover-image-preview');
                        $preview.find('div.text-center, img').remove();
                        $preview.prepend('<img src="' + product.image + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; position: absolute; top: 0; left: 0; z-index: 1;">');
                        // Store image URL for potential use (though file input can't be set)
                        $preview.data('parent-image-url', product.image);
                    }
                    
                    // Load gallery images
                    if (product.gallery && Array.isArray(product.gallery) && product.gallery.length > 0) {
                        console.log('Loading gallery images:', product.gallery);
                        product.gallery.forEach(function(galleryItem, index) {
                            const galleryIndex = index + 1;
                            if (galleryIndex <= 3) { // We only have 3 gallery preview slots
                                const $galleryPreview = $('#gallery-preview-' + galleryIndex);
                                
                                if ($galleryPreview.length === 0) {
                                    console.warn('Gallery preview element not found for index:', galleryIndex);
                                    return;
                                }
                                
                                const $input = $galleryPreview.find('input[type="file"]');
                                
                                // Remove existing preview content but keep input
                                $galleryPreview.find('i.fas.fa-plus, img').remove();
                                
                                // Get image URL - check both src and url properties
                                const imageUrl = galleryItem.src || galleryItem.url || (typeof galleryItem === 'string' ? galleryItem : null);
                                
                                if (imageUrl) {
                                    // Ensure input stays in DOM with proper styling
                                    if ($input.length > 0) {
                                        $input.css({
                                            'position': 'absolute',
                                            'width': '100%',
                                            'height': '100%',
                                            'opacity': '0',
                                            'z-index': '10',
                                            'cursor': 'pointer'
                                        });
                                    }
                                    
                                    // Add preview image
                                    $galleryPreview.prepend('<img src="' + imageUrl + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; position: absolute; top: 0; left: 0; z-index: 1;">');
                                    
                                    // Store image URL
                                    $galleryPreview.data('parent-image-url', imageUrl);
                                } else {
                                    console.warn('No image URL found for gallery item:', galleryItem);
                                }
                            }
                        });
                    } else {
                        console.log('No gallery images found or gallery is not an array:', product.gallery);
                    }
                    
                },
                error: function(xhr) {
                    console.error('Error loading parent product data:', xhr);
                }
            });
        }
        

        // Setup form submission
        $('#create-product-form').on('submit', function(e) {
            e.preventDefault();
            
            // Validate category_ids (required)
            const categoryIds = $('#product-categories').val();
            if (!categoryIds || categoryIds.length === 0) {
                $('#product-categories').addClass('is-invalid');
                $('#product-categories').next('.invalid-feedback').remove();
                $('#product-categories').after('<div class="invalid-feedback">لطفاً حداقل یک دسته بندی انتخاب کنید</div>');
                return false;
            } else {
                $('#product-categories').removeClass('is-invalid');
                $('#product-categories').next('.invalid-feedback').remove();
            }
            
            const formData = new FormData();
            
            // Add all form fields except files
            $(this).find('input:not([type="file"]), select, textarea').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                const type = $field.attr('type');
                
                if (name) {
                    if (type === 'checkbox' || type === 'radio') {
                        if ($field.is(':checked')) {
                            formData.append(name, $field.val());
                        }
                    } else if (type !== 'file') {
                        if ($field.val()) {
                            formData.append(name, $field.val());
                        }
                    }
                }
            });
            
            // Handle multiple select fields (categories)
            $(this).find('select[multiple]').each(function() {
                const $select = $(this);
                const name = $select.attr('name');
                if (name) {
                    const values = $select.val();
                    if (values && values.length > 0) {
                        values.forEach(function(value) {
                            formData.append(name, value);
                        });
                    }
                }
            });
            
            // Explicitly add cover image
            const coverImageInput = document.getElementById('cover-image-input');
            if (coverImageInput && coverImageInput.files && coverImageInput.files[0]) {
                formData.append('cover_image', coverImageInput.files[0]);
            }
            
            // Explicitly add gallery images
            const galleryInputs = document.querySelectorAll('#create-product-form input[name="gallery[]"]');
            galleryInputs.forEach(function(input) {
                if (input.files && input.files[0]) {
                    formData.append('gallery[]', input.files[0]);
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
                    window.location.href = '{{ route("products.index") }}';
                },
                error: function(xhr) {
                    console.error('Error creating product:', xhr);
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        alert(xhr.responseJSON.message);
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errorMessages = [];
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            errorMessages.push(value[0]);
                        });
                        alert(errorMessages.join('\n'));
                    } else {
                        alert('خطا در ایجاد محصول');
                    }
                }
            });
        });
    });


    // Preview cover image
    function previewCoverImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            const $preview = $('#cover-image-preview');
            const $input = $(input);
            
            reader.onload = function(e) {
                $preview.find('div.text-center, img').remove();
                $preview.prepend('<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; position: absolute; top: 0; left: 0; z-index: 1;">');
                $input.css({
                    'position': 'absolute',
                    'width': '100%',
                    'height': '100%',
                    'opacity': '0',
                    'z-index': '10',
                    'cursor': 'pointer'
                });
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Preview gallery image
    function previewGalleryImage(input, index) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            const $preview = $('#gallery-preview-' + index);
            const $input = $(input);
            
            reader.onload = function(e) {
                $input.css({
                    'position': 'absolute',
                    'width': '100%',
                    'height': '100%',
                    'opacity': '0',
                    'z-index': '10',
                    'cursor': 'pointer'
                });
                $preview.find('i.fas.fa-plus').remove();
                $preview.prepend('<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; position: absolute; top: 0; left: 0; z-index: 1;">');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Add etiket
    function addEtiket() {
        etiketCounter++;
        const etiketHtml = '<div class="card etiket-item mb-3" data-index="' + etiketCounter + '" style="width: 100%;">' +
            '<div class="card-header d-flex justify-content-between align-items-center bg-light">' +
                '<h6 class="mb-0">اتیکت ' + etiketCounter + '</h6>' +
                '<button type="button" class="btn btn-sm btn-danger" onclick="removeEtiket(' + etiketCounter + ')">' +
                    '<i class="fas fa-times"></i> حذف' +
                '</button>' +
            '</div>' +
            '<div class="card-body">' +
                '<div class="row">' +
                    '<div class="col-md-4">' +
                        '<label class="small font-weight-bold">تعداد</label>' +
                        '<input type="number" class="form-control etiket-count-input" name="etikets[' + etiketCounter + '][count]" placeholder="تعداد" min="1" value="1" data-index="' + etiketCounter + '" onchange="calculateEtiketPrice(' + etiketCounter + ')">' +
                    '</div>' +
                    '<div class="col-md-4">' +
                        '<label class="small font-weight-bold">وزن (گرم)</label>' +
                        '<input type="number" class="form-control etiket-weight-input" name="etikets[' + etiketCounter + '][weight]" placeholder="وزن" step="0.01" data-index="' + etiketCounter + '" onchange="calculateEtiketPrice(' + etiketCounter + ')" oninput="calculateEtiketPrice(' + etiketCounter + ')">' +
                    '</div>' +
                    '<div class="col-md-4">' +
                        '<label class="small font-weight-bold">قیمت (تومان)</label>' +
                        '<input type="number" class="form-control etiket-price-input" name="etikets[' + etiketCounter + '][price]" placeholder="قیمت" readonly data-index="' + etiketCounter + '">' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        if ($('#etikets-list p.text-muted').length > 0) {
            $('#etikets-list').html('');
        }
        $('#etikets-list').append(etiketHtml);
    }
    
    // Remove etiket
    function removeEtiket(index) {
        const etiketItem = $('.etiket-item[data-index="' + index + '"]');
        etiketItem.remove();
        if ($('#etikets-list .etiket-item').length === 0) {
            $('#etikets-list').html('<p class="text-muted text-center mb-0">هیچ اتیکتی اضافه نشده است</p>');
        }
    }

    // Calculate etiket price based on weight and ojrat
    function calculateEtiketPrice(index) {
        const $etiketItem = $('.etiket-item[data-index="' + index + '"]');
        const weight = parseFloat($etiketItem.find('.etiket-weight-input').val()) || 0;
        const ojrat = parseFloat($('#ojrat').val()) || 0;
        const $priceInput = $etiketItem.find('.etiket-price-input');
        
        // Try to get gold price from global variable
        if (!goldPrice || goldPrice === 0) {
            goldPrice = window.goldPrice || 0;
        }
        
        if (weight > 0 && goldPrice > 0 && ojrat > 0) {
            // Formula: price = weight * (goldPrice * 1.01) * (1 + (ojrat / 100))
            const adjustedGoldPrice = goldPrice * 1.01;
            let calculatedPrice = weight * adjustedGoldPrice * (1 + (ojrat / 100));
            
            // Round down to nearest thousand (last three digits become 0)
            calculatedPrice = Math.floor(calculatedPrice / 1000) * 1000;
            
            // Update price field
            $priceInput.val(calculatedPrice);
        } else {
            // Clear price if required fields are missing
            $priceInput.val('');
        }
    }

    // Recalculate all etiket prices when ojrat changes
    $('#ojrat').on('input change', function() {
        $('.etiket-item').each(function() {
            const index = $(this).data('index');
            if (index) {
                calculateEtiketPrice(index);
            }
        });
    });
</script>
@endpush

