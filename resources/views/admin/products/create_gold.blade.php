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
                                            <label for="darsad-kharid" class="font-weight-bold">اجرت خرید (%) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="darsad-kharid" name="darsad_kharid" step="0.01" placeholder="درصد اجرت خرید" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="ojrat" class="font-weight-bold">اجرت فروش (%) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="ojrat" name="ojrat" step="0.01" placeholder="درصد اجرت فروش" required>
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
                            
                            <!-- From Here -->
                           
                        </div>
                        <div class="col-md-12">
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
                            
                            <!-- Etikets Sections (Side by Side) -->
                            <div class="form-group mt-4">
                                <div class="row">

                                    <!-- Orderable After Out of Stock Etikets Section -->
                                    <div class="col-md-6">
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
                                    <div class="col-md-6">
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
                        <div>
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
    let orderableEtiketCounter = 0;
    let goldPrice = 0;

    // Fetch gold price on page load
    $(document).ready(function() {
        // Get gold price from PHP setting
        goldPrice = parseFloat('{{ (float) setting("gold_price") ?? 0 }}') || 0;
        window.goldPrice = goldPrice;
        
        console.log('Gold price loaded:', goldPrice);

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
        
        // Initialize image-uploader for gallery only
        // Use a function that retries if plugin is not ready
        function initializeGalleryUploader() {
            if (typeof $.fn.imageUploader !== 'undefined') {
                // Check if container exists
                if ($('#gallery-uploader').length === 0) {
                    console.error('Gallery uploader container not found');
                    return;
                }
                
                // Initialize gallery uploader
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
                // Retry after a short delay
                setTimeout(initializeGalleryUploader, 200);
            }
        }
        
        // Start initialization
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
                        $('#cover-image-placeholder').remove();
                        $preview.find('img').remove();
                        $preview.prepend('<img src="' + product.image + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; position: absolute; top: 0; left: 0; z-index: 1;">');
                        $('#cover-image-actions').show();
                        // Store image URL for potential use (though file input can't be set)
                        $preview.data('parent-image-url', product.image);
                    }
                    
                    // Load gallery images
                    if (typeof $.fn.imageUploader !== 'undefined') {
                        console.log('Loading gallery images:', product.gallery);
                        // Prepare preloaded images array
                        let preloadedGallery = [];
                        if (product.gallery && Array.isArray(product.gallery) && product.gallery.length > 0) {
                            preloadedGallery = product.gallery.map(function(galleryItem) {
                                // Handle different gallery item structures
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
                        
                        // Destroy existing uploader and recreate with preloaded images
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
            
            // Validate darsad_kharid (required)
            const darsadKharid = $('#darsad-kharid').val();
            if (!darsadKharid || parseFloat(darsadKharid) < 0) {
                $('#darsad-kharid').addClass('is-invalid');
                $('#darsad-kharid').next('.invalid-feedback').remove();
                $('#darsad-kharid').after('<div class="invalid-feedback">اجرت خرید الزامی است</div>');
                return false;
            } else {
                $('#darsad-kharid').removeClass('is-invalid');
                $('#darsad-kharid').next('.invalid-feedback').remove();
            }
            
            // Validate ojrat (required)
            const ojrat = $('#ojrat').val();
            if (!ojrat || parseFloat(ojrat) < 0) {
                $('#ojrat').addClass('is-invalid');
                $('#ojrat').next('.invalid-feedback').remove();
                $('#ojrat').after('<div class="invalid-feedback">اجرت فروش الزامی است</div>');
                return false;
            } else {
                $('#ojrat').removeClass('is-invalid');
                $('#ojrat').next('.invalid-feedback').remove();
            }
            
            // Validate at least one etiket exists in either section
            const etiketCount = $('#etikets-row .etiket-item').length;
            const orderableEtiketCount = $('#orderable-etikets-row .etiket-item').length;
            if (etiketCount === 0 && orderableEtiketCount === 0) {
                alert('حداقل یک اتیکت باید در بخش "اتیکت‌ها" یا "اتیکت‌های قابل فروش پس از اتمام موجودی" اضافه شود');
                return false;
            }
            
            // Validate each etiket has weight (regular etikets)
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
            
            // Validate each orderable etiket has weight
            $('#orderable-etikets-row .etiket-item').each(function() {
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
            
            // Add all form fields except files
            $(this).find('input:not([type="file"]), select, textarea').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                const type = $field.attr('type');
                const isRequired = $field.prop('required');
                
                if (name) {
                    if (type === 'checkbox' || type === 'radio') {
                        if ($field.is(':checked')) {
                            formData.append(name, $field.val());
                        }
                    } else if (type !== 'file') {
                        // Always include required fields, even if empty
                        if (isRequired || $field.val()) {
                            formData.append(name, $field.val() || '');
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
            
            // Get cover image from file input
            const coverImageInput = document.getElementById('cover-image-input');
            if (coverImageInput && coverImageInput.files && coverImageInput.files[0]) {
                formData.append('cover_image', coverImageInput.files[0]);
            }
            
            // Get gallery images from image-uploader
            const $galleryInput = $('#gallery-uploader').find('input[name="gallery[]"]');
            if ($galleryInput.length > 0 && $galleryInput[0].files && $galleryInput[0].files.length > 0) {
                for (let i = 0; i < $galleryInput[0].files.length; i++) {
                    formData.append('gallery[]', $galleryInput[0].files[i]);
                }
            }
            
            // Handle preloaded gallery images (existing images that weren't deleted)
            const $galleryUploadedImages = $('#gallery-uploader').find('.uploaded-image');
            const existingGalleryUrls = [];
            $galleryUploadedImages.each(function() {
                const $img = $(this).find('img');
                const src = $img.attr('src');
                if (src && !src.startsWith('blob:') && !src.startsWith('data:')) {
                    existingGalleryUrls.push(src);
                }
            });
            if (existingGalleryUrls.length > 0) {
                formData.append('existing_gallery', JSON.stringify(existingGalleryUrls));
            }
            
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

    // Preview cover image - must be global function
    window.previewCoverImage = function(input) {
        console.log('previewCoverImage called', input);
        if (input && input.files && input.files[0]) {
            const reader = new FileReader();
            const $preview = $('#cover-image-preview');
            const $input = $(input);
            
            console.log('File selected:', input.files[0].name);
            
            reader.onload = function(e) {
                console.log('File loaded, creating preview');
                // Remove placeholder
                $('#cover-image-placeholder').remove();
                // Remove existing image if any
                $preview.find('img').remove();
                // Add new image with proper styling using jQuery
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
                // Show action buttons
                $('#cover-image-actions').show();
                // Ensure input stays on top for future clicks
                $input.css({
                    'position': 'absolute',
                    'width': '100%',
                    'height': '100%',
                    'opacity': '0',
                    'z-index': '15',
                    'cursor': 'pointer'
                });
                console.log('Preview created successfully');
            };
            
            reader.onerror = function(error) {
                console.error('Error reading file:', error);
                alert('خطا در خواندن فایل تصویر');
            };
            
            reader.readAsDataURL(input.files[0]);
        } else {
            console.warn('No file selected or input is invalid');
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
            // Restore placeholder
            if ($('#cover-image-placeholder').length === 0) {
                $('#cover-image-preview').prepend('<div class="text-center" id="cover-image-placeholder"><i class="fas fa-image fa-3x text-muted mb-2"></i><p class="text-muted mb-0">برای آپلود تصویر کلیک کنید</p></div>');
            }
        }
    };

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
    function calculateEtiketPrice(index, isOrderable) {
        const selector = isOrderable ? '#orderable-etikets-row' : '#etikets-row';
        const $etiketItem = $(selector + ' .etiket-item[data-index="' + index + '"]');
        const weight = parseFloat($etiketItem.find('.etiket-weight-input').val()) || 0;
        const ojrat = parseFloat($('#ojrat').val()) || 0;
        const $priceInput = $etiketItem.find('.etiket-price-input');
        
        // Get gold price from global variable or setting
        let currentGoldPrice = goldPrice || window.goldPrice || parseFloat('{{ (float) setting("gold_price") ?? 0 }}') || 0;
        
        console.log('Calculating price for etiket', index, {
            weight: weight,
            ojrat: ojrat,
            goldPrice: currentGoldPrice,
            isOrderable: isOrderable
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
            if (weight === 0) {
                console.log('Weight is 0');
            }
            if (currentGoldPrice === 0) {
                console.log('Gold price is 0');
            }
            if (ojrat === 0) {
                console.log('Ojrat is 0');
            }
        }
    }

    // Recalculate all etiket prices when ojrat changes
    $(document).on('input change', '#ojrat', function() {
        $('#etikets-row .etiket-item').each(function() {
            const index = $(this).data('index');
            if (index) {
                calculateEtiketPrice(index, false);
            }
        });
        $('#orderable-etikets-row .etiket-item').each(function() {
            const index = $(this).data('index');
            if (index) {
                calculateEtiketPrice(index, true);
            }
        });
    });
    
    // Use event delegation for weight and count inputs in dynamically added cards
    $(document).on('input change', '.etiket-weight-input:not([data-orderable]), .etiket-count-input:not([data-orderable])', function() {
        const index = $(this).data('index');
        if (index) {
            calculateEtiketPrice(index, false);
        }
    });
    
    // Use event delegation for orderable etiket weight and count inputs
    $(document).on('input change', '.etiket-weight-input[data-orderable="true"], .etiket-count-input[data-orderable="true"]', function() {
        const index = $(this).data('index');
        if (index) {
            calculateEtiketPrice(index, true);
        }
    });
    
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
                        '<input type="number" class="form-control etiket-count-input" name="orderable_etikets[' + orderableEtiketCounter + '][count]" placeholder="تعداد" min="1" value="1" data-index="' + orderableEtiketCounter + '" data-orderable="true" onchange="calculateEtiketPrice(' + orderableEtiketCounter + ', true)">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label class="small font-weight-bold">وزن (گرم)</label>' +
                        '<input type="number" class="form-control etiket-weight-input" name="orderable_etikets[' + orderableEtiketCounter + '][weight]" placeholder="وزن" step="0.01" data-index="' + orderableEtiketCounter + '" data-orderable="true" onchange="calculateEtiketPrice(' + orderableEtiketCounter + ', true)" oninput="calculateEtiketPrice(' + orderableEtiketCounter + ', true)" onkeypress="handleOrderableWeightEnter(event, ' + orderableEtiketCounter + ')">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label class="small font-weight-bold">قیمت (تومان)</label>' +
                        '<input type="number" class="form-control etiket-price-input" name="orderable_etikets[' + orderableEtiketCounter + '][price]" placeholder="قیمت" readonly data-index="' + orderableEtiketCounter + '" data-orderable="true">' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        if ($('#orderable-etikets-row p.text-muted').length > 0) {
            $('#orderable-etikets-row').html('');
        }
        $('#orderable-etikets-row').append(etiketHtml);
        
        // Calculate price if weight and ojrat are available
        setTimeout(function() {
            calculateEtiketPrice(orderableEtiketCounter, true);
        }, 100);
        
        return orderableEtiketCounter;
    }
    
    // Handle Enter key press in orderable weight field
    function handleOrderableWeightEnter(event, currentIndex) {
        if (event.which === 13 || event.keyCode === 13) {
            event.preventDefault();
            const newIndex = addOrderableEtiket();
            setTimeout(function() {
                $('.etiket-weight-input[data-index="' + newIndex + '"][data-orderable="true"]').focus();
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

