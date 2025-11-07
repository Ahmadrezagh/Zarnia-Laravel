@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'محصولات جامع ناموجود'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'محصولات جامع ناموجود']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <div class="row mb-3">

                <form id="filterForm" action="" class="col-12 ">
                    <div class="row">

                        <x-form.select-option title="فیلتر" id="test" name="filter" col="col-3 mb-3 ">
                            <option value="only_images" @if(request('filter')  == 'only_images' ) selected @endif >محصولات عکس دار</option>
                            <option value="only_without_images" @if(request('filter')  == 'only_without_images' ) selected @endif >محصولات غیر عکس دار</option>
                            <option value="only_without_gallery" @if(request('filter')  == 'only_without_gallery' ) selected @endif >محصولات بدون گالری</option>
                            <option value="only_unavilables" @if(request('filter')  == 'only_unavilables' ) selected @endif >محصولات ناموجود</option>
                            <option value="only_main_products" @if(request('filter')  == 'only_main_products' ) selected @endif >محصولات متغییر</option>
                            <option value="only_discountables" @if(request('filter')  == 'only_discountables') selected @endif >محصولات تخفیف دار</option>
                        </x-form.select-option>
                        <x-form.select-option title="دسته بندی" id="test" multiple="multiple" name="category_ids[]" col="col-3">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                        @if(in_array($category->id, request()->input('categories', []))) selected @endif>
                                    {{ $category->title }}
                                </option>
                            @endforeach
                        </x-form.select-option>

                        <x-form.select-option title="جستجو بر اساس" id="search_key" name="searchKey" col="col-3" >

                            <option value="name" @if(request('searchKey')  == 'name' ) selected @endif >اسم محصول</option>
                            <option value="weight" @if(request('searchKey')  == 'weight' ) selected @endif >وزن</option>
                            <option value="ojrat" @if(request('searchKey')  == 'ojrat' ) selected @endif >درصد اجرت</option>
                            <option value="count" @if(request('searchKey')  == 'count' ) selected @endif >موجودی</option>
                            <option value="discount_percentage" @if(request('searchKey')  == 'discount_percentage' ) selected @endif >درصد تخفیف</option>
                            <option value="etiket_code" @if(request('searchKey')  == 'etiket_code' ) selected @endif >اتیکت</option>
                        </x-form.select-option>
                        <x-form.input title="جستجو" name="searchVal" value="{{request('searchVal')}}" />
                        <div class="col-12">
                            <button type="button" onclick="filterProducts('filterForm')" class="btn btn-success" style="width:100%">فیلتر</button>
                        </div>
                    </div>
                </form>

                <div class="col-3">
                    <button type="button" class="btn btn-primary" onclick="showBulkUpdateModal()">ویرایش دسته جمعی </button>
                </div>
                <div class="col-3">
                    <button type="button" class="btn btn-primary" onclick="showAssignCategoryModal()">ویرایش دسته بندی </button>
                </div>
            </div>
        </x-slot>
        <x-dataTable
            :url="route('table.products_comprehensive_not_available')"
            id="products-table"
            hasCheckbox="true"
            changeColorKey="parent_id"
            changeColorHasValue="true"
            changeColorToColor="#dbd7d7"
            :columns="[
                            ['label' => 'کد اتیکت', 'key' => 'etiketsCodeAsArray', 'type' => 'ajax','route' =>'product.etikets','ajax_key' => 'slug'],
                            ['label' => 'تصویر محصول', 'key' => 'image', 'type' => 'image'],
                            ['label' => 'نام محصول', 'key' => 'nameUrl', 'type' => 'text'],
                            ['label' => 'وزن', 'key' => 'weight', 'type' => 'text'],
                            ['label' => 'قیمت', 'key' => 'price', 'type' => 'text'],
                            ['label' => 'درصد خرید', 'key' => 'darsad_kharid', 'type' => 'text'],
                            ['label' => 'درصد اجرت', 'key' => 'ojrat', 'type' => 'text'],
                            ['label' => 'موجودی', 'key' => 'count', 'type' => 'text'],
                            ['label' => 'درصد تخفیف', 'key' => 'discount_percentage', 'type' => 'text'],
                            ['label' => 'دسته بندی ها', 'key' => 'categories_title', 'type' => 'text'],
                            ['label' => 'تعداد بازدید محصول', 'key' => 'view_count', 'type' => 'text'],
                        ]"
            :items="$products"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modalEdit'],
                            ['label' => 'حذف', 'type' => 'modalDestroy'],
                        ]"
        >
        </x-dataTable>
    </x-page>

    <!-- Dynamic modal -->
    <div class="modal fade" id="dynamic-modal" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-name" id="dynamic-modal-title">ویرایش محصول</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modal-body-content">
                </div>
            </div>
        </div>
    </div>

    <script>

        function filterProducts(formId) {
            // Get the form element by ID
            const form = document.getElementById(formId);

            // Create a FormData object from the form
            const formData = new FormData(form);

            // Convert FormData to URL-encoded query string
            const query = new URLSearchParams(formData).toString();

            // Construct the URL with the query string
            const url = "<?php echo e(route('table.products_comprehensive_not_available')); ?>?" + query;

            // Call the loadDataWithNewUrl function with the constructed URL
            window.loadDataWithNewUrl(url);
        }

        function modalEdit(id){
            eraseModalContent()
            // Set modal title
            $('#dynamic-modal-title').text('ویرایش محصول');
            getApiResult(id)
            showDynamicModal()
        }

        function showDynamicModal(){
            const dModal = $("#dynamic-modal")
            dModal.modal("show")
        }

        function eraseModalContent(){
            $("#modal-body-content").empty()
        }

        function appendToModalContent(content){
            $("#modal-body-content").append(content)
        }

        function getApiResult(id) {
            // Add cache busting parameter
            const cacheBuster = '?t=' + new Date().getTime();
            $.ajax({
                url: `{{route('products.index')}}/${id}${cacheBuster}`,
                method: 'GET',
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Cache-Control': 'no-cache'
                },
                success: function(response) {
                    console.log('Product data loaded:', response);
                    const product = response.data || response;
                    
                    console.log('Comprehensive products:', product.comprehensive_products);

                    const categoryOptions = product.categories.map(category =>
                        `<option value="${category.id}" ${product.category_ids.includes(category.id) ? 'selected' : ''}>${category.title}</option>`
                    ).join('');

                    // Create form HTML with fields (same as comprehensive products page)
                    const formContent = `
                <form id="edit-product-form" data-id="${product.id}">
                    <input type="hidden" id="productId" value="${product.id}">
                    <div class="form-group">
                        <label for="product-name">نام محصول</label>
                        <input type="text" class="form-control" id="product-name" value="${product.name || ''}" ${product.is_comprehensive ? '' : 'disabled'}>
                        ${!product.is_comprehensive ? '<small class="text-muted د-block mt-1"><i class="fas fa-info-circle"></i> فقط محصولات جامع می‌توانند نام خود را تغییر دهند.</small>' : ''}
                    </div>
                    <div class="form-group">
                        <label for="product-slug">نامک (Slug)</label>
                        <input type="text" class="form-control" id="product-slug" value="${product.slug || ''}" dir="ltr" required>
                    </div>
                    <div class="form-group">
                        <label for="product-link">لینک</label>
                        <input type="text" dir="ltr" class="form-control" id="product-link" value="${product.urlOfProduct || ''}" >
                    </div>
                    <div class="form-group">
                        <label for="product-price">قیمت</label>
                        <input type="number" class="form-control" id="product-price" value="${product.price || ''}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="product-price">قیمت با تخفیف</label>
                        <input type="number" class="form-control" id="product-discounted-price" value="${product.discounted_price || ''}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="product-price">درصد تخفیف</label>
                        <input type="number" class="form-control" id="discount_percentage" value="${product.discount_percentage || ''}" >
                    </div>
                    <div class="form-group">
                        <label for="product-description">توضیحات</label>
                        <textarea class="form-control" id="product-description" rows="4">${product.description || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="product-categories">دسته بندی</label>
                        <select name="categories" id="product-categories" class="form-control" onchange="categoryChanged(this,${product.id})" multiple>
                            ${categoryOptions}
                        </select>
                    </div>
                    <div class="custom-file mt-5 mb-5">
                        <label for="product-cover-image" class="custom-file-label">تصویر کاور</label>
                        <input type="file" class="custom-file-input" id="product-cover-image" name="cover_image" accept="image/*" onchange="showImagePreview('product-cover-image', 'image-preview', '${product.image || ''}')">
                        <div id="image-preview" class="d-flex justify-content-center mt-2">
                            ${product.image ? `
                                <div class="position-relative d-inline-block">
                                    <img src="${product.image}" alt="Current Cover Image" class="img-thumbnail" style="max-width: 150px;" onclick="changeSize(this)">
                                    <button type="button" class="btn btn-sm btn-danger position-absolute" style="top: 5px; right: 5px;" onclick="deleteCoverImage(${product.id})" title="حذف تصویر کاور">
                                        <i class="fas fa-times"></i>
                                    </button>
                        </div>
                            ` : ''}
                        </div>
                        <input type="hidden" id="delete-cover-image" name="delete_cover_image" value="0">
                    </div>
                    <div class="form-group mt-5">
                        <label for="product-gallery">گالری تصاویر</label>
                        <div id="product-gallery"></div>
                    </div>

                    <div class="form-group">
                        <label for="attributeGroup">گروه ویژگی</label>
                        <select name="attribute_group" class="form-control" id="attributeGroup" onchange="attributeGroupChanged(this, ${product.id})">
                            <option value="">انتخاب گروه ویژگی</option>
                        </select>
                        <div id="loadingSpinner" class="spinner-border spinner-border-sm d-none" role="status">
                            <span class="sr-only">در حال بارگذاری...</span>
                        </div>
                    </div>
                    <div id="attributeInputs" class="mt-3"></div>
                    
                    ${product.is_comprehensive ? `
                    <div class="form-group mt-4">
                        <label class="font-weight-bold">محصولات تشکیل‌دهنده (جامع)</label>
                        <div class="alert alert-info">
                            <small>این محصول یک محصول جامع است و از محصولات زیر تشکیل شده است:</small>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>تصویر</th>
                                        <th>نام محصول</th>
                                        <th>وزن</th>
                                        <th>قیمت</th>
                                        <th>موجودی</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${product.comprehensive_products.map(p => `
                                        <tr data-product-id="${p.id}">
                                            <td>
                                                ${p.image ? `<img src="${p.image}" alt="${p.name}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">` : '<span class="text-muted">بدون تصویر</span>'}
                                            </td>
                                            <td>${p.name}</td>
                                            <td>${p.weight} گرم</td>
                                            <td>${Number(p.price).toLocaleString('fa-IR')} تومان</td>
                                            <td>
                                                <span class="badge badge-${p.count > 0 ? 'success' : 'danger'}">
                                                    ${p.count}
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeProductFromComprehensive(${product.id}, ${p.id})">
                                                    <i class="fas fa-trash"></i> حذف
                                                </button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 p-3 border-top" id="add-product-section-${product.id}">
                            <h6>افزودن محصول جدید</h6>
                            <div class="row">
                                <div class="col-12">
                                    <div class="custom-product-search" style="position: relative;">
                                        <input 
                                            type="text" 
                                            id="product-search-input-${product.id}" 
                                            class="form-control" 
                                            placeholder="جستجو و انتخاب محصول (با کلیک روی محصول، بلافاصله اضافه می‌شود)..."
                                            autocomplete="off"
                                            oninput="searchProductsForComprehensive(${product.id}, this.value)"
                                            onfocus="showProductDropdown(${product.id})"
                                            onblur="setTimeout(() => hideProductDropdown(${product.id}), 200)"
                                        />
                                        <div 
                                            id="product-dropdown-${product.id}" 
                                            class="custom-product-dropdown" 
                                            style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 300px; overflow-y: auto; z-index: 9999; box-shadow: 0 2px 8px rgba(0,0,0,0.15); margin-top: 2px;"
                                        >
                                            <div class="text-center p-3 text-muted" style="display: none;" id="product-search-loading-${product.id}">
                                                <i class="fas fa-spinner fa-spin"></i> در حال جستجو...
                                            </div>
                                            <div class="text-center p-3 text-muted" style="display: none;" id="product-search-empty-${product.id}">
                                                نتیجه‌ای یافت نشد
                                            </div>
                                            <div id="product-search-results-${product.id}"></div>
                                        </div>
                                    </div>
                                    <small class="text-muted mt-1 d-block">
                                        <i class="fas fa-info-circle"></i> با کلیک روی محصول از لیست، به صورت خودکار به محصول جامع اضافه می‌شود.
                                    </small>
                                </div>
                        </div>
                    </div>
                    ` : ''}

                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                </form>
            `;

                    // Append form to modal
                    appendToModalContent(formContent);
                    
                    // Initialize Select2 for categories dropdown
                    $('#product-categories').select2({
                        placeholder: 'دسته‌بندی‌ها را انتخاب کنید',
                        allowClear: true,
                        width: '100%'
                    });

                    // Load attribute groups based on selected categories (if any)
                    const selectedCategoryIds = $('#product-categories').val();
                    if (selectedCategoryIds && selectedCategoryIds.length > 0) {
                        loadAttributeGroupsByCategories(selectedCategoryIds, product.id);
                    } else {
                        // If no categories selected, load all attribute groups
                        loadAttributeGroups(product.attribute_group_id);
                    }
                    
                    // Set current attribute group if exists
                    if (product.attribute_group_id) {
                        $('#attributeGroup').data('product-attribute-group-id', product.attribute_group_id);
                    }

                    $('#product-categories').change();
                    // Check if imageUploader is available
                    if (typeof $.fn.imageUploader === 'undefined') {
                        console.error('imageUploader is not defined. Ensure the image-uploader library is loaded.');
                        $('.gallery-preview').before('<p class="text-danger">خطا: کتابخانه آپلود تصاویر بارگذاری نشده است.</p>');
                        return;
                    }

                    // Initialize image-uploader for gallery
                    $('#product-gallery').imageUploader({
                        label: 'تصاویر را انتخاب کنید یا اینجا بکشید و رها کنید',
                        imagesInputName: 'gallery',
                        maxFiles: 10,
                        maxSize: 2 * 1024 * 1024,
                        preloaded: product.gallery || [],
                        extensions: ['.jpg', '.jpeg', '.png', '.gif', '.svg','.JPG','.JPEG','.webp','.WEBP'],
                        mimes: ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp']

                    });

                    // Client-side file size validation
                    $('#product-cover-image, #product-gallery').on('change', function() {
                        const maxSize = 2 * 1024 * 1024; // 2MB
                        for (let file of this.files) {
                            if (file.size > maxSize) {
                                alert('فایل‌ها باید کمتر از ۲ مگابایت باشند.');
                                this.value = '';
                            }
                        }
                    });

                    // Handle form submission
                    $('#edit-product-form').on('submit', function(e) {
                        e.preventDefault();
                        const formData = new FormData();
                        formData.append('_method', 'PUT');
                        const slugValue = ($('#product-slug').val() || '').trim();
                        if (!slugValue) {
                            toastr.error('لطفاً نامک (Slug) محصول را وارد کنید.');
                            return;
                        }
                        formData.append('slug', slugValue);
                        formData.append('name', $('#product-name').val());
                        formData.append('price', $('#product-price').val());
                        formData.append('weight', $('#product-weight').val());
                        formData.append('discount_percentage', $('#discount_percentage').val());
                        formData.append('count', $('#product-count').val());
                        formData.append('description', $('#product-description').val());
                        const categoryIds = $('#product-categories').val() ? $('#product-categories').val().map(Number) : [];
                        categoryIds.forEach(id => formData.append('category_ids[]', id));
                        const coverImage = $('#product-cover-image')[0].files[0];
                        if (coverImage) {
                            formData.append('cover_image', coverImage);
                        }
                        // Handle cover image deletion
                        if ($('#delete-cover-image').val() === '1') {
                            formData.append('delete_cover_image', '1');
                        }

                        // Get files from image-uploader
                        const $galleryInput = $('#product-gallery').find('input[name="gallery[]"]');
                        const galleryFiles = $galleryInput[0] ? $galleryInput[0].files : [];
                        const $uploadedImages = $('#product-gallery').find('.uploaded-image');
                        const existingImageUrls = [];

                        // Collect existing (preloaded) images
                        $uploadedImages.each(function() {
                            const $img = $(this).find('img');
                            const src = $img.attr('src');
                            if (src && !src.startsWith('blob:')) {
                                existingImageUrls.push(src);
                            }
                        });
                        formData.append('existing_gallery', JSON.stringify(existingImageUrls));

                        // Append new gallery files
                        for (let i = 0; i < galleryFiles.length; i++) {
                            formData.append('gallery[]', galleryFiles[i]);
                        }

                        // Append attribute group and attributes from modal
                        const attributeGroup = $('#attributeGroup').val();
                        if (attributeGroup) {
                            formData.append('attribute_group', attributeGroup);
                        }

                        const attributes = $('.attribute-row').get().map((row, index) => ({
                            attribute_id: $(row).find('.attribute-name').data('attribute-id') || '',
                            name: $(row).find('.attribute-name').val().trim(),
                            value: $(row).find('.attribute-value').val().trim()
                        })).filter(attr => attr.name && attr.value); // Only include attributes with both name and value

                        attributes.forEach((attr, index) => {
                            formData.append(`attributes[${index}][attribute_id]`, attr.attribute_id);
                            formData.append(`attributes[${index}][name]`, attr.name);
                            formData.append(`attributes[${index}][value]`, attr.value);
                        });

                        $.ajax({
                            url: `{{route('products.index')}}/${id}`,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                window.refreshTable();
                                toastr.success('محصول با موفقیت به‌روزرسانی شد!');
                                $('#dynamic-modal').modal('hide');
                            },
                            error: function(xhr) {
                                console.error('Error updating product:', xhr);
                                toastr.error('خطایی در به‌روزرسانی محصول رخ داد.');
                            }
                        });
                    });
                },
                error: function(xhr) {
                    console.error('Error fetching product:', xhr);
                    appendToModalContent('<p class="text-danger">خطا در بارگذاری اطلاعات محصول.</p>');
                }
            });
        }
        function showBulkUpdateModal() {
            eraseModalContent();

            const formHtml = `
        <form id="bulkUpdateForm" enctype="multipart/form-data">
            <input type="hidden" name="product_ids" id="product_ids">

            <div class="form-group">
                <label>درصد تخفیف</label>
                <input type="number" name="discount_percentage" class="form-control">
            </div>

            <div class="form-group">
                <label>دسته بندی</label>
                <select name="category_ids[]" multiple class="form-control s2" style="width:100%">
                    @foreach($categories as $category)
                        <option value="{{$category->id}}">{{$category->title}}</option>
                    @endforeach
                </select>
            </div>

            <div class="custom-file mt-5 mb-5">
                <label for="product-cover-image" class="custom-file-label">تصویر کاور</label>
                <input type="file" class="custom-file-input" name="cover_image" accept="image/*" >
            </div>

            <button type="button" onclick="submitBulkUpdate(this)" class="btn btn-primary">ویرایش</button>
        </form>
    `;

            appendToModalContent(formHtml);

            // Set the selected product IDs from hidden input
            $('#product_ids').val($('#selectedValues').val());

            showDynamicModal();

            $('.s2').select2({
                placeholder: 'دسته‌بندی‌ها را انتخاب کنید',
                allowClear: true,
                width: '100%'
            });
        }

        function showAssignCategoryModal() {
            eraseModalContent();

            const formHtml = `
        <form id="bulkUpdateForm" enctype="multipart/form-data">
            <input type="hidden" name="product_ids" id="product_ids">

            <div class="form-group">
                <label>از قیمت</label>
                <input type="number" name="from_price" class="form-control">
            </div>


            <div class="form-group">
                <label>تا قیمت قیمت</label>
                <input type="number" name="to_price" class="form-control">
            </div>

            <div class="form-group">
                <label>دسته بندی</label>
                <select name="category_ids[]" multiple class="form-control s2" style="width:100%">
                    @foreach($categories as $category)
                        <option value="{{$category->id}}">{{$category->title}}</option>
                    @endforeach
                </select>
            </div>

            <button type="button" onclick="submitAssignCategory(this)" class="btn btn-primary">ویرایش</button>
        </form>
    `;

            appendToModalContent(formHtml);

            // Set the selected product IDs from hidden input
            $('#product_ids').val($('#selectedValues').val());

            showDynamicModal();

            $('.s2').select2({
                placeholder: 'دسته‌بندی‌ها را انتخاب کنید',
                allowClear: true,
                width: '100%'
            });
        }

        function modalDestroy(id) {
            if (!confirm('آیا از حذف این محصول جامع مطمئن هستید؟')) {
                return;
            }
            
            $.ajax({
                url: `{{route('products.index')}}/${id}`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                success: function(response) {
                    toastr.success('محصول با موفقیت حذف شد');
                    window.refreshTable();
                },
                error: function(xhr) {
                    toastr.error('خطا در حذف محصول');
                    console.error(xhr);
                }
            });
        }

    </script>
    <script>
        function submitBulkUpdate(button) {
            const form = $(button).closest('form')[0];
            const formData = new FormData(form);

            const file = formData.get('cover_image');
            console.log('Selected file:', file);
            if (file && file.size > 0) {
                console.log('File name:', file.name, 'File size:', file.size);
            } else {
                console.log('No file selected or file is empty');
            }

            formData.append('_token', '{{ csrf_token() }}');

            $.ajax({
                url: '{{ route('products.bulk_update') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    window.refreshTable();
                    $('#dynamic-modal').modal('hide');
                },
                error: function (xhr) {
                    alert('An error occurred: ' + xhr.responseText);
                }
            });
        }
        function submitAssignCategory(button) {
            const form = $(button).closest('form')[0];
            const formData = new FormData(form);

            formData.append('_token', '{{ csrf_token() }}');

            $.ajax({
                url: '{{ route('products.assign_category') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    window.refreshTable();
                    $('#dynamic-modal').modal('hide');
                },
                error: function (xhr) {
                    alert('An error occurred: ' + xhr.responseText);
                }
            });
        }

    </script>
    <script>
        function showImagePreview(inputId, previewContainerId, originalImage) {
            const input = document.getElementById(inputId);
            const previewContainer = document.getElementById(previewContainerId);

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.innerHTML = `<img src="${e.target.result}" alt="Image Preview" class="img-thumbnail" style="max-width: 150px;">`;
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                // Restore original image or clear preview if no file is selected
                previewContainer.innerHTML = originalImage ? `<img src="${originalImage}" alt="Current Cover Image" class="img-thumbnail" style="max-width: 150px;">` : '';
            }
        }

        function changeSize(img){
                if (img.style.maxWidth === '150px') {
                    img.style.maxWidth = '100%';
                    img.style.height = 'auto';
                    img.style.width = '100%';
                } else {
                    img.style.maxWidth = '150px';
                    img.style.height = '';
                    img.style.width = '';
                }
        }
    </script>

    <script>
        const attributeState = {
            groupId: null
        };

        const showLoading = () => $('#loadingSpinner').removeClass('d-none');
        const hideLoading = () => $('#loadingSpinner').addClass('d-none');

        const checkAttributeGroup = (groupName, productId) => {
            if (!groupName) return;

            showLoading();
            $.ajax({
                url: '{{ route('load_attribute_group') }}',
                method: 'POST',
                data: {
                    name: groupName,
                    product_id: productId,
                    _token: '{{ csrf_token() }}'
                },
                success: (response) => {
                    hideLoading();
                    $('#attributeInputs').empty();
                    addAddButton();
                    attributeState.groupId = response.attributeGroup?.id || null;

                    if (response.attributeGroup) {
                        loadAttributes(response.attributes, response.attributeValues || []);
                    } else {
                        addAttributeInput(null, '', '', 0);
                    }
                },
                error: () => {
                    hideLoading();
                    alert('خطا در بررسی گروه ویژگی');
                }
            });
        };

        function categoryChanged(element,productId) {
            // Convert selected options to an array of values
            const category_ids = Array.from(element.selectedOptions).map(option => option.value);
            
            // Show loading
            showLoading();
            
            $.ajax({
                url: '{{ route('load_attribute_group') }}',
                method: 'POST',
                data: {
                    category_ids: category_ids,
                    product_id: productId,
                    _token: '{{ csrf_token() }}'
                },
                success: (response) => {
                    hideLoading();
                    $('#attributeInputs').empty();
                    addAddButton();
                    
                    // Load attribute groups based on categories
                    if (category_ids && category_ids.length > 0) {
                        loadAttributeGroupsByCategories(category_ids, productId);
                    } else {
                        // If no categories selected, clear attribute group dropdown
                        const $select = $('#attributeGroup');
                        $select.empty();
                        $select.append('<option value="">انتخاب گروه ویژگی</option>');
                    }
                    
                    // Load attributes if available
                    if (response.attributes) {
                        loadAttributes(response.attributes, response.attributeValues || []);
                    }
                },
                error: () => {
                    hideLoading();
                    alert('خطا در بررسی گروه ویژگی');
                }
            });
        }
        
        function loadAttributeGroupsByCategories(categoryIds, productId) {
            // Get attribute groups directly from categories via backend
            $.ajax({
                url: '{{ route('load_attribute_group') }}',
                method: 'POST',
                data: {
                    category_ids: categoryIds,
                    product_id: productId,
                    _token: '{{ csrf_token() }}',
                    get_attribute_groups: true // Flag to get groups
                },
                success: function(response) {
                    const $select = $('#attributeGroup');
                    $select.empty();
                    $select.append('<option value="">انتخاب گروه ویژگی</option>');
                    
                    // If response includes attribute groups, use them
                    if (response.attributeGroups && response.attributeGroups.length > 0) {
                        response.attributeGroups.forEach(function(group) {
                            $select.append(`<option value="${group.id}" data-name="${group.name}">${group.name}</option>`);
                        });
                    } else {
                        // Fallback: Load from API endpoint if groups not in response
                        loadAttributeGroupsFromApi(null);
                    }
                },
                error: function() {
                    console.error('Failed to load attribute groups by categories');
                    // Fallback to loading all groups
                    loadAttributeGroupsFromApi(null);
                }
            });
        }

        const removeAttributeRow = (button) => {
            $(button).closest('.attribute-row').remove();
        };

        // Load attribute groups from database
        function loadAttributeGroups(selectedId = null) {
            $.ajax({
                url: '{{ route("attribute_groups.index") }}',
                method: 'GET',
                success: function(response) {
                    // If response is HTML, parse it. Otherwise assume JSON
                    let groups = [];
                    if (typeof response === 'string') {
                        // Extract from HTML if needed, or use a dedicated API endpoint
                        // For now, we'll create an API endpoint
                        loadAttributeGroupsFromApi(selectedId);
                    } else {
                        groups = response.data || response;
                    }
                },
                error: function() {
                    loadAttributeGroupsFromApi(selectedId);
                }
            });
        }

        function loadAttributeGroupsFromApi(selectedId = null) {
            $.ajax({
                url: '/admin/attribute_groups/api/list', // We'll create this endpoint
                method: 'GET',
                success: function(response) {
                    const $select = $('#attributeGroup');
                    $select.empty();
                    $select.append('<option value="">انتخاب گروه ویژگی</option>');
                    
                    if (response.data && response.data.length > 0) {
                        response.data.forEach(function(group) {
                            const selected = (selectedId && group.id == selectedId) ? 'selected' : '';
                            $select.append(`<option value="${group.id}" ${selected} data-name="${group.name}">${group.name}</option>`);
                        });
                    }
                    
                    // Trigger change to load attributes
                    if (selectedId) {
                        $select.trigger('change');
                    }
                },
                error: function() {
                    console.error('Failed to load attribute groups');
                    $('#attributeGroup').append('<option value="">خطا در بارگذاری گروه‌های ویژگی</option>');
                }
            });
        }

        function attributeGroupChanged(element, productId) {
            const groupId = $(element).val();
            if (!groupId) {
                $('#attributeInputs').empty();
                return;
            }
            
            const groupName = $(element).find('option:selected').data('name') || '';
                checkAttributeGroup(groupName, productId);
            }

        function deleteCoverImage(productId) {
            if (!confirm('آیا از حذف تصویر کاور مطمئن هستید؟')) {
                return;
            }
            $('#delete-cover-image').val('1');
            $('#image-preview').empty();
            $('#product-cover-image').val('');
            toastr.success('تصویر کاور برای حذف علامت‌گذاری شد. پس از ذخیره، حذف خواهد شد.');
        }

        function removeProductFromComprehensive(comprehensiveProductId, productId) {
            if (!confirm('آیا از حذف این محصول از محصول جامع مطمئن هستید؟')) {
                return;
            }
            
            $.ajax({
                url: '{{ route("comprehensive_product.remove") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    comprehensive_product_id: comprehensiveProductId,
                    product_id: productId
                },
                success: function(response) {
                    toastr.success('محصول با موفقیت از محصول جامع حذف شد');
                    // Reload product data
                    getApiResult(comprehensiveProductId);
                },
                error: function(xhr) {
                    toastr.error('خطا در حذف محصول');
                    console.error(xhr);
                }
            });
        }

        let searchTimeout = {};
        
        function searchProductsForComprehensive(comprehensiveProductId, searchTerm) {
            const inputId = `#product-search-input-${comprehensiveProductId}`;
            const dropdownId = `#product-dropdown-${comprehensiveProductId}`;
            const resultsId = `#product-search-results-${comprehensiveProductId}`;
            const loadingId = `#product-search-loading-${comprehensiveProductId}`;
            const emptyId = `#product-search-empty-${comprehensiveProductId}`;
            
            // Clear previous timeout
            if (searchTimeout[comprehensiveProductId]) {
                clearTimeout(searchTimeout[comprehensiveProductId]);
            }
            
            // Show dropdown
            showProductDropdown(comprehensiveProductId);
            
            // If search term is empty or less than 1 character, hide dropdown
            if (!searchTerm || searchTerm.trim().length < 1) {
                $(dropdownId).hide();
                return;
            }
            
            // Show loading
            $(loadingId).show();
            $(emptyId).hide();
            $(resultsId).empty();
            
            // Debounce search
            searchTimeout[comprehensiveProductId] = setTimeout(function() {
                $.ajax({
                    url: '{{ route("products.ajax.search") }}',
                    method: 'GET',
                    data: {
                        q: searchTerm,
                        available_only: '1'
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $(loadingId).hide();
                        
                        console.log('AJAX Response:', response);
                        
                        // Handle different response formats
                        let data = [];
                        
                        // If response has 'results' property (Select2 format), use it
                        if (response && response.results && Array.isArray(response.results)) {
                            data = response.results;
                        }
                        // If response is already an array, use it directly
                        else if (Array.isArray(response)) {
                            data = response;
                        }
                        // If response has a 'data' property, use it
                        else if (response && response.data && Array.isArray(response.data)) {
                            data = response.data;
                        }
                        
                        console.log('Extracted data:', data);
                        
                        // Filter only products (items with id starting with "Product:")
                        const products = data.filter(function(item) {
                            if (!item || !item.id) return false;
                            const itemId = item.id.toString();
                            return itemId.startsWith('Product:');
                        });
                        
                        console.log('Filtered products:', products);
                        
                        if (!products || products.length === 0) {
                            $(emptyId).show();
                            $(resultsId).empty();
                            return;
                        }
                        
                        $(emptyId).hide();
                        let html = '';
                        
                        products.forEach(function(item) {
                            // Extract product ID - handle both "Product:123" format and direct ID
                            let productId = item.id;
                            if (typeof productId === 'string' && productId.startsWith('Product:')) {
                                productId = productId.replace('Product:', '');
                            }
                            
                            const productName = item.text || item.name || 'نامشخص';
                            
                            // Escape single quotes and other special characters for onclick
                            // Also escape backslashes and other special JS characters
                            const escapedName = productName
                                .replace(/\\/g, '\\\\')
                                .replace(/'/g, "\\'")
                                .replace(/"/g, '&quot;')
                                .replace(/\n/g, ' ')
                                .replace(/\r/g, '');
                            
                            html += `
                                <div 
                                    class="custom-product-item" 
                                    style="padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; transition: background 0.2s;"
                                    onmouseover="this.style.background='#f5f5f5'"
                                    onmouseout="this.style.background='white'"
                                    data-product-id="${productId}"
                                    onclick="selectProductForComprehensive(${comprehensiveProductId}, '${productId}', '${escapedName}')"
                                >
                                    <strong>${productName}</strong>
                                </div>
                            `;
                        });
                        
                        $(resultsId).html(html);
                    },
                    error: function(xhr) {
                        $(loadingId).hide();
                        $(resultsId).html('<div class="text-center p-3 text-danger">خطا در جستجو</div>');
                        console.error('Search error:', xhr);
                    }
                });
            }, 300); // 300ms debounce
        }
        
        function showProductDropdown(comprehensiveProductId) {
            const dropdownId = `#product-dropdown-${comprehensiveProductId}`;
            $(dropdownId).show();
        }
        
        function hideProductDropdown(comprehensiveProductId) {
            const dropdownId = `#product-dropdown-${comprehensiveProductId}`;
            // Don't hide if mouse is over dropdown
            if (!$(dropdownId).is(':hover') && !$(`#product-search-input-${comprehensiveProductId}`).is(':focus')) {
                $(dropdownId).hide();
            }
        }
        
        function selectProductForComprehensive(comprehensiveProductId, productId, productName) {
            console.log('Selecting product:', {
                comprehensiveProductId: comprehensiveProductId,
                productId: productId,
                productName: productName
            });
            
            // Ensure productId is a string/number (remove any "Product:" prefix if still present)
            if (typeof productId === 'string' && productId.startsWith('Product:')) {
                productId = productId.replace('Product:', '');
            }
            productId = String(productId).trim();
            
            // Validate product ID is numeric
            if (!/^\d+$/.test(productId)) {
                toastr.error('شناسه محصول نامعتبر است');
                console.error('Invalid product ID format:', productId);
                return;
            }
            
            // Clear search input and hide dropdown immediately for better UX
            $(`#product-search-input-${comprehensiveProductId}`).val('');
            $(`#product-dropdown-${comprehensiveProductId}`).hide();
            $(`#product-search-results-${comprehensiveProductId}`).empty();
            
            // Show loading indicator in search input
            const $searchInput = $(`#product-search-input-${comprehensiveProductId}`);
            const originalPlaceholder = $searchInput.attr('placeholder');
            $searchInput.prop('disabled', true).attr('placeholder', 'در حال افزودن...');
            
            // Immediately send AJAX request to add product
            $.ajax({
                url: '{{ route("comprehensive_product.add") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    comprehensive_product_id: comprehensiveProductId,
                    product_id: productId
                },
                success: function(response) {
                    console.log('Add product response:', response);
                    
                    if (response.success) {
                        toastr.success(response.message || 'محصول با موفقیت به محصول جامع اضافه شد');
                        
                        // Reload product data to refresh the table
                        setTimeout(function() {
                            eraseModalContent();
                            getApiResult(comprehensiveProductId);
                        }, 300);
                    } else {
                        toastr.error(response.message || 'خطا در افزودن محصول');
                        // Re-enable search input on error
                        $searchInput.prop('disabled', false).attr('placeholder', originalPlaceholder);
                    }
                },
                error: function(xhr) {
                    console.error('AJAX error:', xhr);
                    console.error('Response:', xhr.responseJSON);
                    
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors || xhr.responseJSON?.message;
                        if (errors) {
                            const errorMsg = typeof errors === 'string' ? errors : 
                                          (errors.message || (Array.isArray(errors) ? errors.join(', ') : 'خطا در افزودن محصول'));
                            toastr.error(errorMsg);
                        } else {
                            toastr.error(xhr.responseJSON?.message || 'این محصول قبلا اضافه شده است');
                        }
                    } else if (xhr.status === 500) {
                        toastr.error('خطای سرور: ' + (xhr.responseJSON?.message || 'خطا در افزودن محصول'));
                    } else {
                        toastr.error('خطا در برقراری ارتباط با سرور');
                    }
                    
                    // Re-enable search input on error
                    $searchInput.prop('disabled', false).attr('placeholder', originalPlaceholder);
                },
                complete: function() {
                    // Re-enable search input after request completes (whether success or error)
                    // Only if it wasn't already re-enabled in error handler
                    setTimeout(function() {
                        if ($searchInput.prop('disabled')) {
                            $searchInput.prop('disabled', false).attr('placeholder', originalPlaceholder);
                        }
                    }, 100);
                }
            });
        }
    </script>
@endsection

