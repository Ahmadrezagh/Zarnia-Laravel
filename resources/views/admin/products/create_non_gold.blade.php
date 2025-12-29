@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'افزودن محصول غیر طلا'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'محصولات', 'url' => route('products.index')],
            ['label' => 'افزودن محصول غیر طلا']
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
                    <input type="hidden" name="is_not_gold_product" value="1">
                    
                    <div class="row">
                        <!-- Left Column: Image Upload Section -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">تصویر اصلی محصول</label>
                                <div class="image-upload-container mb-3">
                                    <div class="image-preview-large" id="cover-image-preview" style="width: 100%; height: 250px; border: 2px dashed #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; cursor: pointer; position: relative; overflow: hidden;">
                                        <div class="text-center" id="cover-image-placeholder">
                                            <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">برای آپلود تصویر کلیک کنید</p>
                                        </div>
                                        <div id="cover-image-actions" style="display: none; position: absolute; top: 10px; right: 10px; z-index: 20;">
                                            <button type="button" class="btn btn-sm btn-primary mr-1" onclick="editCoverImage()" title="ویرایش تصویر">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteCoverImage()" title="حذف تصویر">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        <input type="file" id="cover-image-input" name="cover_image" accept="image/*" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 15;" onchange="previewCoverImage(this)">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="font-weight-bold">گالری تصاویر</label>
                                <div id="gallery-uploader"></div>
                            </div>
                        </div>
                        
                        <!-- Right Column: Form Fields -->
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="product-name" class="font-weight-bold">نام محصول <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="product-name" name="name" required placeholder="مثال: جعبه طلا">
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
                            
                            <div class="form-group">
                                <label for="product-price-non-gold" class="font-weight-bold">قیمت (تومان) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="product-price-non-gold" name="price" placeholder="قیمت محصول" step="0.01" required>
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
                            
                            <!-- Orderable After Out of Stock Etikets Section -->
                            <div class="form-group mt-4">
                                <label class="font-weight-bold">اتیکت‌های قابل فروش پس از اتمام موجودی</label>
                                <div id="orderable-etikets-list" class="border rounded p-3" style="width: 100%; min-height: 400px; height: auto; overflow-y: visible; border-color: #ffc107;">
                                    <div class="row" id="orderable-etikets-row">
                                        <p class="text-muted text-center mb-0 col-12">هیچ اتیکتی اضافه نشده است</p>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-warning mt-2" onclick="addOrderableEtiket()">
                                    <i class="fas fa-plus"></i> افزودن اتیکت قابل فروش پس از اتمام موجودی
                                </button>
                                <small class="form-text text-muted d-block mt-2">برای افزودن اتیکت جدید، در فیلد تعداد Enter را بزنید</small>
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
    let orderableEtiketCounter = 0;

    $(document).ready(function() {
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

        // When parent product is selected, fill form with parent data
        $('#parent-product').on('select2:select', function (e) {
            const parentId = e.params.data.id;
            if (parentId) {
                loadParentProductData(parentId);
            }
        });

        // When parent product is cleared, hide URL
        $('#parent-product').on('select2:clear', function (e) {
            $('#parent-product-url').hide();
            $('#parent-product-url-link').attr('href', '#').text('');
        });
        
        // Initialize image-uploader for gallery only
        function initializeGalleryUploader() {
            if (typeof $.fn.imageUploader !== 'undefined') {
                if ($('#gallery-uploader').length === 0) {
                    console.error('Gallery uploader container not found');
                    return;
                }
                
                try {
                    $('#gallery-uploader').imageUploader({
                        label: 'تصاویر گالری را انتخاب کنید یا اینجا بکشید و رها کنید',
                        imagesInputName: 'gallery',
                        maxFiles: 10,
                        maxSize: 2 * 1024 * 1024,
                        preloaded: [],
                        extensions: ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.JPG', '.JPEG', '.webp', '.WEBP'],
                        mimes: ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp']
                    });
                    console.log('Gallery uploader initialized');
                } catch (e) {
                    console.error('Error initializing gallery uploader:', e);
                }
            } else {
                console.warn('imageUploader plugin not yet loaded, retrying...');
                setTimeout(initializeGalleryUploader, 200);
            }
        }
        
        setTimeout(initializeGalleryUploader, 100);

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
                        $('#cover-image-placeholder').remove();
                        $preview.find('img').remove();
                        $preview.prepend('<img src="' + product.image + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; position: absolute; top: 0; left: 0; z-index: 1;">');
                        $('#cover-image-actions').show();
                        $preview.data('parent-image-url', product.image);
                    }
                    
                    // Load gallery images
                    if (typeof $.fn.imageUploader !== 'undefined') {
                        console.log('Loading gallery images:', product.gallery);
                        let preloadedGallery = [];
                        if (product.gallery && Array.isArray(product.gallery) && product.gallery.length > 0) {
                            preloadedGallery = product.gallery.map(function(galleryItem) {
                                let imageUrl = null;
                                if (typeof galleryItem === 'string') {
                                    imageUrl = galleryItem;
                                } else if (galleryItem && typeof galleryItem === 'object') {
                                    imageUrl = galleryItem.src || galleryItem.url || galleryItem;
                                }
                                return imageUrl ? { src: imageUrl } : null;
                            }).filter(function(item) { return item !== null; });
                        }
                        
                        console.log('Prepared gallery images:', preloadedGallery);
                        
                        $('#gallery-uploader').empty();
                        $('#gallery-uploader').imageUploader({
                            label: 'تصاویر گالری را انتخاب کنید یا اینجا بکشید و رها کنید',
                            imagesInputName: 'gallery',
                            maxFiles: 10,
                            maxSize: 2 * 1024 * 1024,
                            preloaded: preloadedGallery,
                            extensions: ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.JPG', '.JPEG', '.webp', '.WEBP'],
                            mimes: ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp']
                        });
                        console.log('Gallery uploader reinitialized with', preloadedGallery.length, 'images');
                    } else {
                        console.warn('imageUploader plugin not available for gallery update');
                    }
                    
                },
                error: function(xhr) {
                    console.error('Error loading parent product data:', xhr);
                }
            });
        }
        window.loadParentProductData = loadParentProductData;

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
            
            // Validate price (required)
            const price = $('#product-price-non-gold').val();
            if (!price || parseFloat(price) <= 0) {
                $('#product-price-non-gold').addClass('is-invalid');
                $('#product-price-non-gold').next('.invalid-feedback').remove();
                $('#product-price-non-gold').after('<div class="invalid-feedback">قیمت محصول الزامی است</div>');
                return false;
            } else {
                $('#product-price-non-gold').removeClass('is-invalid');
                $('#product-price-non-gold').next('.invalid-feedback').remove();
            }
            
            // Validate that at least one orderable etiket exists
            const hasOrderableEtikets = $('#orderable-etikets-row .etiket-item').length > 0;
            if (!hasOrderableEtikets) {
                alert('لطفاً حداقل یک اتیکت قابل فروش پس از اتمام موجودی اضافه کنید');
                return false;
            }
            
            // Submit form
            this.submit();
        });
    });

    // Preview cover image - must be global function
    window.previewCoverImage = function(input) {
        if (input && input.files && input.files[0]) {
            const reader = new FileReader();
            const $preview = $('#cover-image-preview');
            const $input = $(input);
            
            reader.onload = function(e) {
                $('#cover-image-placeholder').remove();
                $preview.find('img').remove();
                const $img = $('<img>').attr('src', e.target.result).css({
                    'width': '100%',
                    'height': '100%',
                    'object-fit': 'cover',
                    'border-radius': '6px',
                    'position': 'absolute',
                    'top': '0',
                    'left': '0',
                    'z-index': '1'
                });
                $preview.prepend($img);
                $('#cover-image-actions').show();
                $input.css({
                    'position': 'absolute',
                    'width': '100%',
                    'height': '100%',
                    'opacity': '0',
                    'z-index': '15',
                    'cursor': 'pointer'
                });
            };
            
            reader.onerror = function(error) {
                console.error('Error reading file:', error);
                alert('خطا در خواندن فایل تصویر');
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    };
    
    // Edit cover image - must be global function
    window.editCoverImage = function() {
        $('#cover-image-input').click();
    };
    
    // Delete cover image - must be global function
    window.deleteCoverImage = function() {
        if (confirm('آیا از حذف تصویر کاور اطمینان دارید؟')) {
            $('#cover-image-preview').find('img').remove();
            $('#cover-image-actions').hide();
            $('#cover-image-input').val('');
            if ($('#cover-image-placeholder').length === 0) {
                $('#cover-image-preview').prepend('<div class="text-center" id="cover-image-placeholder"><i class="fas fa-image fa-3x text-muted mb-2"></i><p class="text-muted mb-0">برای آپلود تصویر کلیک کنید</p></div>');
            }
        }
    };

    // Add orderable etiket (orderable after out of stock)
    function addOrderableEtiket() {
        orderableEtiketCounter++;
        const etiketHtml = '<div class="col-md-3 mb-3">' +
            '<div class="card etiket-item h-100" data-index="' + orderableEtiketCounter + '" style="border-color: #ffc107;">' +
                '<div class="card-header d-flex justify-content-between align-items-center" style="background-color: #fff3cd;">' +
                    '<h6 class="mb-0">اتیکت قابل فروش ' + orderableEtiketCounter + '</h6>' +
                    '<button type="button" class="btn btn-sm btn-danger" onclick="removeOrderableEtiket(' + orderableEtiketCounter + ')">' +
                        '<i class="fas fa-times"></i>' +
                    '</button>' +
                '</div>' +
                '<div class="card-body">' +
                    '<div class="form-group">' +
                        '<label class="small font-weight-bold">تعداد</label>' +
                        '<input type="number" class="form-control etiket-count-input" name="orderable_etikets[' + orderableEtiketCounter + '][count]" placeholder="تعداد" min="1" value="1" data-index="' + orderableEtiketCounter + '" data-orderable="true" onkeypress="handleOrderableCountEnter(event, ' + orderableEtiketCounter + ')">' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        if ($('#orderable-etikets-row p.text-muted').length > 0) {
            $('#orderable-etikets-row').html('');
        }
        $('#orderable-etikets-row').append(etiketHtml);
        
        return orderableEtiketCounter;
    }
    
    // Handle Enter key press in orderable count field
    function handleOrderableCountEnter(event, currentIndex) {
        if (event.which === 13 || event.keyCode === 13) {
            event.preventDefault();
            const newIndex = addOrderableEtiket();
            setTimeout(function() {
                $('.etiket-count-input[data-index="' + newIndex + '"][data-orderable="true"]').focus();
            }, 100);
        }
    }
    
    // Remove orderable etiket
    function removeOrderableEtiket(index) {
        const etiketItem = $('#orderable-etikets-row .etiket-item[data-index="' + index + '"]').closest('.col-md-3');
        etiketItem.remove();
        if ($('#orderable-etikets-row .etiket-item').length === 0) {
            $('#orderable-etikets-row').html('<p class="text-muted text-center mb-0 col-12">هیچ اتیکتی اضافه نشده است</p>');
        }
    }
    
    
</script>
@endpush

