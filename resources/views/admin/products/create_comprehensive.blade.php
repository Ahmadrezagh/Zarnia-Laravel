@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'افزودن محصول جامع'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'محصولات', 'url' => route('products.index')],
            ['label' => 'افزودن محصول جامع']
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
                <form id="create-comprehensive-product-form" action="{{ route('comprehensive_product.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <!-- Left Column: Image Upload Section -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">تصویر اصلی محصول</label>
                                <div class="image-upload-container mb-3">
                                    <div class="image-preview-large" id="comprehensive-cover-image-preview" style="width: 100%; height: 250px; border: 2px dashed #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; cursor: pointer; position: relative; overflow: hidden;">
                                        <div class="text-center" id="comprehensive-cover-image-placeholder">
                                            <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">برای آپلود تصویر کلیک کنید</p>
                                        </div>
                                        <div id="comprehensive-cover-image-actions" style="display: none; position: absolute; top: 10px; right: 10px; z-index: 20;">
                                            <button type="button" class="btn btn-sm btn-primary mr-1" onclick="editComprehensiveCoverImage()" title="ویرایش تصویر">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteComprehensiveCoverImage()" title="حذف تصویر">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        <input type="file" id="comprehensive-cover-image-input" name="cover_image" accept="image/*" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 15;" onchange="previewComprehensiveCoverImage(this)">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="font-weight-bold">گالری تصاویر</label>
                                <div id="comprehensive-gallery-uploader"></div>
                            </div>
                        </div>
                        
                        <!-- Right Column: Form Fields -->
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="comprehensive-product-name" class="font-weight-bold">نام محصول جامع <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="comprehensive-product-name" name="name" required placeholder="مثال: ست طلا">
                            </div>
                            
                            <div class="form-group">
                                <label for="comprehensive-parent-product" class="font-weight-bold">محصول والد (اختیاری)</label>
                                <select name="parent_id" id="comprehensive-parent-product" class="form-control">
                                    <option value="">-- انتخاب محصول والد --</option>
                                </select>
                                <small class="form-text text-muted">در صورت نیاز به ایجاد محصول زیرمجموعه، محصول والد را انتخاب کنید</small>
                                <div id="comprehensive-parent-product-url" class="mt-2" style="display: none;">
                                    <small class="text-muted">لینک محصول والد: </small>
                                    <a href="#" id="comprehensive-parent-product-url-link" target="_blank" class="text-primary"></a>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="comprehensive-product-slug" class="font-weight-bold">نامک (Slug) (اختیاری)</label>
                                <input type="text" id="comprehensive-product-slug" name="slug" class="form-control" dir="ltr" placeholder="example-slug">
                            </div>
                            
                            <div class="form-group">
                                <label for="comprehensive-product-description" class="font-weight-bold">توضیحات</label>
                                <textarea class="form-control" id="comprehensive-product-description" name="description" rows="3" placeholder="توضیحات محصول"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="comprehensive-product-categories" class="font-weight-bold">دسته بندی <span class="text-danger">*</span></label>
                                <select name="categories[]" id="comprehensive-product-categories" class="form-control" multiple required>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->title }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">حداقل یک دسته بندی باید انتخاب شود</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="comprehensive-etiket-select" class="font-weight-bold">محصولات <span class="text-danger">*</span></label>
                                <select id="comprehensive-etiket-select" name="product_ids[]" class="form-control" multiple required></select>
                                <small class="form-text text-muted">محصولاتی که این محصول جامع از آن‌ها تشکیل شده است را انتخاب کنید</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> ایجاد محصول جامع
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
    $(document).ready(function() {
        // Initialize Select2 for categories
        $('#comprehensive-product-categories').select2({
            placeholder: 'دسته‌بندی‌ها را انتخاب کنید',
            allowClear: true,
            width: '100%'
        });
        
        // Initialize Select2 for parent product
        $('#comprehensive-parent-product').select2({
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
        $('#comprehensive-parent-product').on('select2:select', function (e) {
            const parentId = e.params.data.id;
            if (parentId) {
                loadComprehensiveParentProductData(parentId);
            }
        });

        // When parent product is cleared, hide URL
        $('#comprehensive-parent-product').on('select2:clear', function (e) {
            $('#comprehensive-parent-product-url').hide();
            $('#comprehensive-parent-product-url-link').attr('href', '#').text('');
        });
        
        // Initialize image-uploader for gallery only
        function initializeComprehensiveGalleryUploader() {
            if (typeof $.fn.imageUploader !== 'undefined') {
                if ($('#comprehensive-gallery-uploader').length === 0) {
                    console.error('Comprehensive gallery uploader container not found');
                    return;
                }
                
                try {
                    $('#comprehensive-gallery-uploader').imageUploader({
                        label: 'تصاویر گالری را انتخاب کنید یا اینجا بکشید و رها کنید',
                        imagesInputName: 'gallery',
                        maxFiles: 10,
                        maxSize: 2 * 1024 * 1024,
                        preloaded: [],
                        extensions: ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.JPG', '.JPEG', '.webp', '.WEBP'],
                        mimes: ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp']
                    });
                    console.log('Comprehensive gallery uploader initialized');
                } catch (e) {
                    console.error('Error initializing comprehensive gallery uploader:', e);
                }
            } else {
                console.warn('imageUploader plugin not yet loaded, retrying...');
                setTimeout(initializeComprehensiveGalleryUploader, 200);
            }
        }
        
        setTimeout(initializeComprehensiveGalleryUploader, 100);
        
        // Initialize Select2 for etiket/product selection
        $('#comprehensive-etiket-select').select2({
            placeholder: 'جستجو و انتخاب محصولات (با موجودی)',
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
                url: '{{ route("products.ajax.search.comprehensive") }}',
                dataType: 'json',
                delay: 250,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    if (data && data.results && Array.isArray(data.results)) {
                        return { results: data.results };
                    }
                    return { results: [] };
                },
                cache: false
            }
        });

        // Setup form submission
        $('#create-comprehensive-product-form').on('submit', function(e) {
            e.preventDefault();
            
            // Validate category_ids (required)
            const categoryIds = $('#comprehensive-product-categories').val();
            if (!categoryIds || categoryIds.length === 0) {
                $('#comprehensive-product-categories').addClass('is-invalid');
                $('#comprehensive-product-categories').next('.invalid-feedback').remove();
                $('#comprehensive-product-categories').after('<div class="invalid-feedback">لطفاً حداقل یک دسته بندی انتخاب کنید</div>');
                return false;
            } else {
                $('#comprehensive-product-categories').removeClass('is-invalid');
                $('#comprehensive-product-categories').next('.invalid-feedback').remove();
            }
            
            // Validate product_ids (required)
            const productIds = $('#comprehensive-etiket-select').val();
            if (!productIds || productIds.length === 0) {
                $('#comprehensive-etiket-select').addClass('is-invalid');
                $('#comprehensive-etiket-select').next('.invalid-feedback').remove();
                $('#comprehensive-etiket-select').after('<div class="invalid-feedback">لطفاً حداقل یک محصول انتخاب کنید</div>');
                return false;
            } else {
                $('#comprehensive-etiket-select').removeClass('is-invalid');
                $('#comprehensive-etiket-select').next('.invalid-feedback').remove();
            }
            
            // Submit form
            this.submit();
        });
        
        // Function to load parent product data and fill form
        function loadComprehensiveParentProductData(productId) {
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
                        $('#comprehensive-parent-product-url-link').attr('href', product.frontend_url).text(product.frontend_url);
                        $('#comprehensive-parent-product-url').show();
                    } else {
                        $('#comprehensive-parent-product-url').hide();
                    }
                    
                    // Fill name
                    if (product.name) {
                        $('#comprehensive-product-name').val(product.name);
                    }
                    
                    // Fill description
                    if (product.description) {
                        $('#comprehensive-product-description').val(product.description);
                    }
                    
                    // Fill categories
                    if (product.category_ids && product.category_ids.length > 0) {
                        $('#comprehensive-product-categories').val(product.category_ids).trigger('change');
                    }
                    
                    // Load cover image
                    if (product.image) {
                        const $preview = $('#comprehensive-cover-image-preview');
                        $('#comprehensive-cover-image-placeholder').remove();
                        $preview.find('img').remove();
                        $preview.prepend('<img src="' + product.image + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; position: absolute; top: 0; left: 0; z-index: 1;">');
                        $('#comprehensive-cover-image-actions').show();
                        $preview.data('parent-image-url', product.image);
                    }
                    
                    // Load gallery images
                    if (typeof $.fn.imageUploader !== 'undefined') {
                        console.log('Loading comprehensive gallery images:', product.gallery);
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
                        
                        console.log('Prepared comprehensive gallery images:', preloadedGallery);
                        
                        $('#comprehensive-gallery-uploader').empty();
                        $('#comprehensive-gallery-uploader').imageUploader({
                            label: 'تصاویر گالری را انتخاب کنید یا اینجا بکشید و رها کنید',
                            imagesInputName: 'gallery',
                            maxFiles: 10,
                            maxSize: 2 * 1024 * 1024,
                            preloaded: preloadedGallery,
                            extensions: ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.JPG', '.JPEG', '.webp', '.WEBP'],
                            mimes: ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp']
                        });
                        console.log('Comprehensive gallery uploader reinitialized with', preloadedGallery.length, 'images');
                    } else {
                        console.warn('imageUploader plugin not available for comprehensive gallery update');
                    }
                    
                },
                error: function(xhr) {
                    console.error('Error loading parent product data:', xhr);
                }
            });
        }
        window.loadComprehensiveParentProductData = loadComprehensiveParentProductData;
    });

    // Preview comprehensive cover image
    window.previewComprehensiveCoverImage = function(input) {
        if (input && input.files && input.files[0]) {
            const reader = new FileReader();
            const $preview = $('#comprehensive-cover-image-preview');
            const $input = $(input);
            
            reader.onload = function(e) {
                console.log('File loaded, creating preview');
                $('#comprehensive-cover-image-placeholder').remove();
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
                $('#comprehensive-cover-image-actions').show();
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
    
    // Edit comprehensive cover image
    window.editComprehensiveCoverImage = function() {
        $('#comprehensive-cover-image-input').click();
    };
    
    // Delete comprehensive cover image
    window.deleteComprehensiveCoverImage = function() {
        if (confirm('آیا از حذف تصویر کاور اطمینان دارید؟')) {
            $('#comprehensive-cover-image-preview').find('img').remove();
            $('#comprehensive-cover-image-actions').hide();
            $('#comprehensive-cover-image-input').val('');
            if ($('#comprehensive-cover-image-placeholder').length === 0) {
                $('#comprehensive-cover-image-preview').prepend('<div class="text-center" id="comprehensive-cover-image-placeholder"><i class="fas fa-image fa-3x text-muted mb-2"></i><p class="text-muted mb-0">برای آپلود تصویر کلیک کنید</p></div>');
            }
        }
    };
</script>
@endpush

