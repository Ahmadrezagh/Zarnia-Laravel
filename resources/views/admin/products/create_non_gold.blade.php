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
                                <label for="import-product" class="font-weight-bold">ایمپورت محصول</label>
                                <select id="import-product" class="form-control">
                                    <option value="">-- انتخاب محصول برای بارگذاری داده‌ها --</option>
                                </select>
                                <small class="form-text text-muted">با انتخاب یک محصول موجود، داده‌های آن در فرم بارگذاری می‌شود. مقدار این فیلد هنگام ذخیره ارسال نمی‌شود.</small>
                                <div id="import-product-url" class="mt-2" style="display: none;">
                                    <small class="text-muted">لینک محصول: </small>
                                    <a href="#" id="import-product-url-link" target="_blank" class="text-primary"></a>
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
                                <label for="product-categories" class="font-weight-bold">دسته بندی</label>
                                <select name="category_ids[]" id="product-categories" class="form-control" multiple>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            
                            <div class="form-group">
                                <label for="product-description" class="font-weight-bold">توضیحات</label>
                                <textarea class="form-control" id="product-description" name="description" rows="3" placeholder="توضیحات محصول"></textarea>
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
    $(document).ready(function() {
        // Initialize Select2 for categories
        $('#product-categories').select2({
            placeholder: 'دسته‌بندی‌ها را انتخاب کنید',
            allowClear: true,
            width: '100%'
        });

        // Initialize Select2 for import product
        $('#import-product').select2({
            placeholder: 'جستجو و انتخاب محصول برای ایمپورت',
            allowClear: true,
            width: '100%',
            minimumInputLength: 1,
            language: {
                inputTooShort: function() { return 'حداقل 1 کاراکتر وارد کنید'; },
                noResults:     function() { return 'نتیجه‌ای یافت نشد'; },
                searching:     function() { return 'در حال جستجو...'; }
            },
            ajax: {
                url: '{{ route("products.ajax.search.parents") }}',
                dataType: 'json',
                type: 'GET',
                delay: 250,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) {
                    let results = [];
                    if (data && data.results && Array.isArray(data.results)) results = data.results;
                    else if (Array.isArray(data)) results = data;
                    else if (data && data.data && Array.isArray(data.data)) results = data.data;
                    const products = results.filter(function(item) {
                        return item && item.id && item.id.toString().startsWith('Product:');
                    });
                    return {
                        results: products.map(function(item) {
                            return { id: item.id.toString().replace('Product:', ''), text: item.text || item.name || 'محصول' };
                        })
                    };
                },
                cache: true
            }
        });

        $('#import-product').on('select2:select', function (e) {
            loadImportProductData(e.params.data.id);
        });
        $('#import-product').on('select2:clear', function () {
            $('#import-product-url').hide();
            $('#import-product-url-link').attr('href', '#').text('');
        });

        function loadImportProductData(productId) {
            $.ajax({
                url: '{{ route("products.index") }}/' + productId,
                method: 'GET',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    const product = response.data || response;

                    if (product.frontend_url) {
                        $('#import-product-url-link').attr('href', product.frontend_url).text(product.frontend_url);
                        $('#import-product-url').show();
                    } else {
                        $('#import-product-url').hide();
                    }
                    if (product.name)             $('#product-name').val(product.name);
                    if (product.discount_percentage != null) $('#discount-percentage').val(product.discount_percentage);
                    if (product.description)      $('#product-description').val(product.description);
                    if (product.attribute_group_str) $('#attribute-group').val(product.attribute_group_str);
                    if (product.category_ids && product.category_ids.length > 0) {
                        $('#product-categories').val(product.category_ids).trigger('change');
                    }
                    if (product.image) {
                        const $preview = $('#cover-image-preview');
                        $('#cover-image-placeholder').remove();
                        $preview.find('img').remove();
                        $preview.prepend('<img src="' + product.image + '" style="width:100%;height:100%;object-fit:cover;border-radius:6px;position:absolute;top:0;left:0;z-index:1;">');
                        $('#cover-image-actions').show();
                        $preview.data('parent-image-url', product.image);
                    }
                    if (typeof $.fn.imageUploader !== 'undefined') {
                        let preloadedGallery = [];
                        if (product.gallery && Array.isArray(product.gallery)) {
                            preloadedGallery = product.gallery.map(function(item) {
                                const url = typeof item === 'string' ? item : (item && (item.src || item.url));
                                return url ? { src: url } : null;
                            }).filter(Boolean);
                        }
                        $('#gallery-uploader').empty().imageUploader({
                            label: 'تصاویر گالری را انتخاب کنید یا اینجا بکشید و رها کنید',
                            imagesInputName: 'gallery',
                            maxFiles: 10,
                            maxSize: 2 * 1024 * 1024,
                            preloaded: preloadedGallery,
                            extensions: ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.JPG', '.JPEG', '.webp', '.WEBP'],
                            mimes: ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp']
                        });
                    }
                },
                error: function(xhr) { console.error('Error loading import product data:', xhr); }
            });
        }
        
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

        // Setup form submission
        $('#create-product-form').on('submit', function(e) {
            e.preventDefault();
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

    
</script>
@endpush

