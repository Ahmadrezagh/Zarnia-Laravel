@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'محصولات'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'محصولات']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-warning mb-3"  type="button" onclick="createGoldProduct()" >افزودن محصول طلا</button>
            <button class="btn btn-info mb-3"  type="button" onclick="createNonGoldProduct()" >افزودن محصول غیر طلا</button>
            <button class="btn btn-primary mb-3"  type="button" onclick="createAssembledProduct()" >افزودن محصول جامع</button>

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
                <div class="col-3">
                    <button type="button" class="btn btn-success" onclick="exportProducts()">
                        <i class="fas fa-file-excel"></i> خروجی اکسل
                    </button>
                </div>
                <div class="col-3">
                    <button type="button" class="btn btn-warning" onclick="recalculateDiscounts()">
                        <i class="fas fa-calculator"></i> محاسبه مجدد تخفیف ها
                    </button>
                </div>
            </div>
        </x-slot>
        <x-dataTable
            :url="route('table.products')"
            id="products-table"
            hasCheckbox="true"
            changeColorKey="parent_id"
            changeColorHasValue="true"
            changeColorToColor="#dbd7d7"
            :columns="[
                            ['label' => 'کد اتیکت', 'key' => 'etiketsCodeAsArray', 'type' => 'ajax','route' =>'product.etikets','ajax_key' => 'slug'],
                            ['label' => 'تصویر محصول', 'key' => 'image', 'type' => 'image'],
                            ['label' => 'نام محصول', 'key' => 'nameUrl', 'type' => 'text'],
                            ['label' => 'وزن', 'key' => 'weight', 'type' => 'text', 'url' => '{frontend_url}'],
                            ['label' => 'قیمت', 'key' => 'price', 'type' => 'text'],
                            ['label' => 'قیمت طبق طلای تابان گوهر', 'key' => 'taban_gohar_price', 'type' => 'text'],
                            ['label' => 'درصد خرید', 'key' => 'darsad_kharid', 'type' => 'text'],
                            ['label' => 'درصد اجرت', 'key' => 'ojrat', 'type' => 'text'],
                            ['label' => 'موجودی', 'key' => 'count', 'type' => 'text'],
                            ['label' => 'درصد تخفیف', 'key' => 'discount_percentage', 'type' => 'text'],
                            ['label' => 'دسته بندی ها', 'key' => 'categories_title_truncated', 'type' => 'text'],
                            ['label' => 'تعداد بازدید محصول', 'key' => 'view_count', 'type' => 'text'],
                            ['label' => 'بازدیدها', 'key' => 'visits', 'type' => 'text', 'sortable' => true],
                        ]"
            :items="$products"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modalEdit'],
                            ['label' => 'مشاهده در سایت', 'url' => '{frontend_url}'],
                            ['label' => 'افزودن اتیکت', 'type' => 'generateEtiket', 'class' => 'btn-info'],
                        ]"
        >
        </x-dataTable>
    </x-page>

    <!-- Dynamic modal -->
    <div class="modal fade" id="dynamic-modal" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
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
        function modalEdit(id){
            eraseModalContent()
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
            $.ajax({
                url: `{{route('products.index')}}/${id}`, // Adjust to your Laravel API endpoint
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Include CSRF token if needed
                },
                success: function(response) {
                    // Assuming response contains product data like {id, name, price, description}
                    const product = response.data || response; // Adjust based on your API response structure

                    const categoryOptions = product.categories.map(category =>
                        `<option value="${category.id}" ${product.category_ids.includes(category.id) ? 'selected' : ''}>${category.title}</option>`
                    ).join('');

                    // Create form HTML with fields
                    const formContent = `
                <form id="edit-product-form" data-id="${product.id}">
                    <input type="hidden" id="productId" value="${product.id}">
                    <div class="form-group">
                        <label for="product-name">نام محصول</label>
                        <input type="text" class="form-control" id="product-name" value="${product.name || ''}" disabled>
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
                        <label for="product-meta-title">عنوان متا (Meta Title)</label>
                        <input type="text" class="form-control" id="product-meta-title" value="${product.meta_title || ''}">
                    </div>
                    <div class="form-group">
                        <label for="product-meta-description">توضیحات متا (Meta Description)</label>
                        <textarea class="form-control no_ck_editor" id="product-meta-description" rows="3" placeholder="Meta description...">${product.meta_description || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="product-meta-keywords">کلمات کلیدی متا (Meta Keywords)</label>
                        <textarea class="form-control no_ck_editor" id="product-meta-keywords" rows="2" placeholder="keyword1, keyword2, keyword3">${product.meta_keywords || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="product-canonical-url">آدرس Canonical</label>
                        <input type="url" class="form-control" id="product-canonical-url" value="${product.canonical_url || ''}" dir="ltr" placeholder="https://example.com/path">
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
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="apply_discount_to_children" name="apply_discount_to_children">
                            <label class="form-check-label mx-3" for="apply_discount_to_children">
                                اعمال برای زیر مجموعه ها
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="orderable_after_out_of_stock" name="orderable_after_out_of_stock" ${product.orderable_after_out_of_stock ? 'checked' : ''}>
                            <label class="form-check-label mx-3" for="orderable_after_out_of_stock">
                                قابلیت سفارش پس از اتمام موجودی
                            </label>
                        </div>
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
                        <div id="image-preview" class="d-flex justify-content-center align-items-center flex-column mt-2">
                            ${product.image && !product.image.includes('no_image.jpg') ? `
                                <img src="${product.image}" alt="Current Cover Image" class="img-thumbnail" style="max-width: 150px;" onclick="changeSize(this)">
                                <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeCoverImage(${product.id})">
                                    <i class="fas fa-trash"></i> حذف تصویر کاور
                                </button>
                            ` : '<p class="text-muted">تصویری وجود ندارد</p>'}
                        </div>
                    </div>
                    <div class="form-group mt-5">
                        <label for="product-gallery">گالری تصاویر</label>
                        <div id="product-gallery"></div>
                    </div>

                    <div class="form-group">
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
                                    </tr>
                                </thead>
                                <tbody>
                                    ${product.comprehensive_products.map(p => `
                                        <tr>
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
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    ` : ''}

                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                </form>
            `;

                    // Append form to modal
                    appendToModalContent(formContent);

                    $('#attributeGroup').click()
                    $('#attributeGroup').val(product.attribute_group_str)

                    $('#attributeGroup').change()
                    const tabEvent = new $.Event('keydown', {
                        key: 'Tab',
                        code: 'Tab',
                        keyCode: 9
                    });
                    $('#attributeGroup').trigger(tabEvent);

                    // Initialize Select2 for categories dropdown
                    $('#product-categories').select2({
                        placeholder: 'دسته‌بندی‌ها را انتخاب کنید',
                        allowClear: true,
                        width: '100%'
                    });
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
                        formData.append('meta_title', $('#product-meta-title').val());
                        formData.append('meta_description', $('#product-meta-description').val());
                        formData.append('meta_keywords', $('#product-meta-keywords').val());
                        formData.append('canonical_url', $('#product-canonical-url').val());
                        formData.append('name', $('#product-name').val());
                        formData.append('price', $('#product-price').val());
                        formData.append('weight', $('#product-weight').val());
                        formData.append('discount_percentage', $('#discount_percentage').val());
                        formData.append('apply_discount_to_children', $('#apply_discount_to_children').is(':checked') ? '1' : '0');
                        formData.append('orderable_after_out_of_stock', $('#orderable_after_out_of_stock').is(':checked') ? '1' : '0');
                        formData.append('count', $('#product-count').val());
                        formData.append('description', $('#product-description').val());
                        const categoryIds = $('#product-categories').val() ? $('#product-categories').val().map(Number) : [];
                        categoryIds.forEach(id => formData.append('category_ids[]', id));
                        const coverImage = $('#product-cover-image')[0].files[0];
                        if (coverImage) {
                            formData.append('cover_image', coverImage);
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

                        // Debug FormData
                        for (let [key, value] of formData.entries()) {
                            console.log(key, value);
                        }

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
                <select name="category_ids[]" multiple class="form-control s2" style="width:100%" >
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

        // Function to generate gold product creation modal HTML
        function getGoldProductCreationModalHTML() {
            const categoryOptions = '@foreach($categories as $category) <option value="{{$category->id}}" >{{$category->title}}</option> @endforeach'
            
            return `
                <form id="create-product-form" action="{{route('products.store')}}" method="POST" enctype="multipart/form-data">
{{ csrf_field() }}
                    
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
                            </div>
                            
                            <div id="gold-product-fields">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="product-weight" class="font-weight-bold">وزن (گرم) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="product-weight" name="weight" step="0.01" placeholder="وزن را وارد کنید">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">گرم</span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">وزن را وارد کنید و با Enter تایید کنید</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="product-price" class="font-weight-bold">قیمت (تومان)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="product-price" name="price" placeholder="قیمت محصول">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-primary" id="calculate-price-btn" onclick="calculateTabanGoharPrice()">
                                                        <i class="fas fa-calculator"></i> محاسبه قیمت
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
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
                            ${categoryOptions}
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
                                <div id="etikets-list" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
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
                        <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
                            انصراف
                        </button>
                    </div>
                </form>
            `;
        }
        
        // Function to generate non-gold product creation modal HTML
        function getNonGoldProductCreationModalHTML() {
            const categoryOptions = '@foreach($categories as $category) <option value="{{$category->id}}" >{{$category->title}}</option> @endforeach'
            
            return `
                <form id="create-product-form" action="{{route('products.store')}}" method="POST" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <input type="hidden" name="is_not_gold_product" value="1">
                    
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
                                <input type="text" class="form-control" id="product-name" name="name" required placeholder="مثال: جعبه طلا">
                            </div>
                            
                            <div class="form-group">
                                <label for="parent-product" class="font-weight-bold">محصول والد (اختیاری)</label>
                                <select name="parent_id" id="parent-product" class="form-control">
                                    <option value="">-- انتخاب محصول والد --</option>
                                </select>
                                <small class="form-text text-muted">در صورت نیاز به ایجاد محصول زیرمجموعه، محصول والد را انتخاب کنید</small>
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
                                    ${categoryOptions}
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
                                <div id="etikets-list" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
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
                        <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
                            انصراف
                        </button>
                    </div>
</form>
            `;
        }
        
        // Function to initialize gold product creation modal
        function initializeGoldProductCreationModal() {
            // Remove any existing event listeners to prevent duplicates
            $('#dynamic-modal').off('shown.bs.modal.createProduct');
            
            // Get gold price for calculation (from PHP setting) - make it global
            window.goldPrice = parseFloat('{{ (float) setting('gold_price') ?? 0 }}') || 0;
            
            // Function to calculate price from Taban Gohar formula (global scope for button onclick)
            window.calculateTabanGoharPrice = function() {
                const weight = parseFloat($('#product-weight').val()) || 0;
                const ojrat = parseFloat($('#ojrat').val()) || 0;
                const goldPrice = window.goldPrice || 0;
                
                if (weight > 0 && goldPrice > 0 && ojrat > 0) {
                    // Formula: price = weight * (goldPrice * 1.01) * (1 + (ojrat / 100))
                    const adjustedGoldPrice = goldPrice * 1.01;
                    let calculatedPrice = weight * adjustedGoldPrice * (1 + (ojrat / 100));
                    
                    // Round down to nearest thousand (last three digits become 0)
                    calculatedPrice = Math.floor(calculatedPrice / 1000) * 1000;
                    
                    // Update price field (price is stored divided by 10, so we show the actual price)
                    $('#product-price').val(calculatedPrice);
                } else {
                    // Clear price if required fields are missing
                    if (weight === 0 || ojrat === 0) {
                        $('#product-price').val('');
                    }
                }
            }
            
            // Wait for modal to be shown and DOM to be ready
            $('#dynamic-modal').on('shown.bs.modal.createProduct', function() {
                // Destroy existing Select2 instances if any
                if ($('#product-categories').hasClass('select2-hidden-accessible')) {
                    $('#product-categories').select2('destroy');
                }
                if ($('#parent-product').hasClass('select2-hidden-accessible')) {
                    $('#parent-product').select2('destroy');
                }
                
                // Initialize Select2 for categories
                $('#product-categories').select2({
                    placeholder: 'دسته‌بندی‌ها را انتخاب کنید',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#dynamic-modal')
                });
                
                // Initialize Select2 for parent product
                $('#parent-product').select2({
                    placeholder: 'جستجو و انتخاب محصول والد',
                    allowClear: true,
                    width: '100%',
                minimumInputLength: 1,
                    dropdownParent: $('#dynamic-modal'),
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
                                available_only: '0' // Get all products, not just available ones
                            };
                        },
                        processResults: function (data) {
                            // Handle different response formats
                            let results = [];
                            if (data && data.results && Array.isArray(data.results)) {
                                results = data.results;
                            } else if (Array.isArray(data)) {
                                results = data;
                            } else if (data && data.data && Array.isArray(data.data)) {
                                results = data.data;
                            }
                            
                            // Filter only products (items with id starting with "Product:")
                            const products = results.filter(function(item) {
                                if (!item || !item.id) return false;
                                const itemId = item.id.toString();
                                return itemId.startsWith('Product:');
                            });
                            
                            // Format for Select2
                            return {
                                results: products.map(function(item) {
                                    // Extract product ID from "Product:123" format
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
                
                // Add event listeners to weight and ojrat inputs for automatic price calculation
                $('#product-weight, #ojrat').off('input change.calculatePrice').on('input change.calculatePrice', function() {
                    if (typeof window.calculateTabanGoharPrice === 'function') {
                        window.calculateTabanGoharPrice();
                    }
                });
                
                // Handle weight input with Enter key
                $('#product-weight').off('keypress.weightEnter').on('keypress.weightEnter', function(e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        $(this).blur();
                        if (typeof window.calculateTabanGoharPrice === 'function') {
                            window.calculateTabanGoharPrice();
                        }
                    }
                });
            });
        }
        
        // Function to initialize non-gold product creation modal
        function initializeNonGoldProductCreationModal() {
            // Remove any existing event listeners to prevent duplicates
            $('#dynamic-modal').off('shown.bs.modal.createNonGoldProduct');
            
            // Wait for modal to be shown and DOM to be ready
            $('#dynamic-modal').on('shown.bs.modal.createNonGoldProduct', function() {
                // Destroy existing Select2 instances if any
                if ($('#product-categories').hasClass('select2-hidden-accessible')) {
                    $('#product-categories').select2('destroy');
                }
                if ($('#parent-product').hasClass('select2-hidden-accessible')) {
                    $('#parent-product').select2('destroy');
                }
                
                // Initialize Select2 for categories
                $('#product-categories').select2({
                    placeholder: 'دسته‌بندی‌ها را انتخاب کنید',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#dynamic-modal')
                });
                
                // Initialize Select2 for parent product
                $('#parent-product').select2({
                    placeholder: 'جستجو و انتخاب محصول والد',
                    allowClear: true,
                    width: '100%',
                    minimumInputLength: 1,
                    dropdownParent: $('#dynamic-modal'),
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
            });
        }
        
        // Function to setup product creation form submission
        function setupProductCreationFormSubmission() {
            // Remove any existing handlers to prevent duplicates
            $('#create-product-form').off('submit');
            
            // Handle form submission
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
                
                // Validate weight for gold products (if weight field exists)
                if ($('#product-weight').length > 0) {
                    const weight = $('#product-weight').val();
                    if (!weight || parseFloat(weight) <= 0) {
                        $('#product-weight').addClass('is-invalid');
                        $('#product-weight').next('.invalid-feedback').remove();
                        $('#product-weight').after('<div class="invalid-feedback">وزن برای محصولات طلایی الزامی است</div>');
                        return false;
                    } else {
                        $('#product-weight').removeClass('is-invalid');
                        $('#product-weight').next('.invalid-feedback').remove();
                    }
                }
                
                // Validate price for non-gold products (if non-gold price field exists)
                if ($('#product-price-non-gold').length > 0) {
                    const nonGoldPrice = $('#product-price-non-gold').val();
                    if (!nonGoldPrice || parseFloat(nonGoldPrice) <= 0) {
                        $('#product-price-non-gold').addClass('is-invalid');
                        $('#product-price-non-gold').next('.invalid-feedback').remove();
                        $('#product-price-non-gold').after('<div class="invalid-feedback">قیمت برای محصولات غیر طلایی الزامی است</div>');
                        return false;
                    } else {
                        $('#product-price-non-gold').removeClass('is-invalid');
                        $('#product-price-non-gold').next('.invalid-feedback').remove();
                    }
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
                            const id = $field.attr('id');
                            // Handle price field - use non-gold price if it exists
                            if (id === 'product-price-non-gold') {
                                formData.append('price', $field.val());
                            } else if (id !== 'product-price-non-gold' && $field.val()) {
                                formData.append(name, $field.val());
                            }
                        }
                    }
                });
                
                // Handle multiple select fields (categories - required)
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
                        $('#dynamic-modal').modal('hide');
                        if (typeof window.refreshTable === 'function') {
                            window.refreshTable();
                        }
                    },
                    error: function(xhr) {
                        // Silent error handling - no alerts
                        console.error('Error creating product:', xhr);
                        if (xhr.responseJSON) {
                            console.error('Error details:', xhr.responseJSON);
                        }
                    }
                });
            });
        }
        
        // Function to create gold product
        function createGoldProduct(){
            eraseModalContent();
            $('#dynamic-modal-title').text('ایجاد محصول طلا');
            
            // Get modal HTML
            const modalContent = getGoldProductCreationModalHTML();
            appendToModalContent(modalContent);
            
            // Initialize modal
            initializeGoldProductCreationModal();
            
            // Setup form submission
            setupProductCreationFormSubmission();
            
            // Show modal and trigger the event
            showDynamicModal();
            
            // Trigger the event if modal is already shown
            setTimeout(function() {
                if ($('#dynamic-modal').hasClass('show')) {
                    $('#dynamic-modal').trigger('shown.bs.modal.createProduct');
                }
            }, 300);
        }
        
        // Function to create non-gold product
        function createNonGoldProduct(){
            eraseModalContent();
            $('#dynamic-modal-title').text('ایجاد محصول غیر طلا');
            
            // Get modal HTML
            const modalContent = getNonGoldProductCreationModalHTML();
            appendToModalContent(modalContent);
            
            // Initialize modal
            initializeNonGoldProductCreationModal();
            
            // Setup form submission
            setupProductCreationFormSubmission();
            
            // Show modal and trigger the event
            showDynamicModal();
            
            // Trigger the event if modal is already shown
            setTimeout(function() {
                if ($('#dynamic-modal').hasClass('show')) {
                    $('#dynamic-modal').trigger('shown.bs.modal.createNonGoldProduct');
                }
            }, 300);
        }
        
        // Helper functions for product creation modal
        function previewCoverImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                const $preview = $('#cover-image-preview');
                const $input = $(input);
                
                reader.onload = function(e) {
                    // Keep the input in DOM, just update the preview
                    // Remove existing preview content but keep the input
                    $preview.find('div.text-center, img').remove();
                    
                    // Add preview image
                    $preview.prepend('<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; position: absolute; top: 0; left: 0; z-index: 1;">');
                    
                    // Ensure input stays in DOM with proper styling
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
        
        function previewGalleryImage(input, index) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                const $preview = $('#gallery-preview-' + index);
                const $input = $(input);
                
                reader.onload = function(e) {
                    // Keep the input in DOM, just hide it visually
                    $input.css({
                        'position': 'absolute',
                        'width': '100%',
                        'height': '100%',
                        'opacity': '0',
                        'z-index': '10',
                        'cursor': 'pointer'
                    });
                    
                    // Remove existing preview if any
                    $preview.find('img.preview-img, button.remove-gallery-btn').remove();
                    
                    // Add preview image
                    $preview.prepend('<img class="preview-img" src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px; position: absolute; top: 0; left: 0; z-index: 1;">');
                    
                    // Add remove button
                    $preview.append('<button type="button" class="btn btn-sm btn-danger remove-gallery-btn" style="position: absolute; top: 2px; right: 2px; padding: 2px 6px; z-index: 11;" onclick="removeGalleryImage(' + index + ')"><i class="fas fa-times"></i></button>');
                    
                    // Hide the plus icon
                    $preview.find('i.fa-plus').hide();
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function removeGalleryImage(index) {
            const $preview = $('#gallery-preview-' + index);
            // Remove preview image and button
            $preview.find('img.preview-img, button.remove-gallery-btn').remove();
            // Reset the file input
            const $input = $preview.find('input[type="file"]');
            $input.val('');
            // Reset input styling
            $input.css({
                'position': 'absolute',
                'width': '100%',
                'height': '100%',
                'opacity': '0',
                'cursor': 'pointer'
            });
            // Show the plus icon
            $preview.find('i.fa-plus').show();
        }
        
        let etiketCounter = 0;
        let insertedEtiketCodes = []; // Track codes in modal
        
        function addEtiket() {
            etiketCounter++;
            const etiketHtml = '<div class="etiket-item border rounded p-2 mb-2" data-index="' + etiketCounter + '">' +
                '<div class="row align-items-center">' +
                    '<div class="col-md-2">' +
                        '<button type="button" class="btn btn-sm btn-primary" onclick="printEtiket(' + etiketCounter + ')">' +
                            '<i class="fas fa-print"></i> Print' +
                        '</button>' +
                    '</div>' +
                    '<div class="col-md-2">' +
                        '<button type="button" class="btn btn-sm btn-danger" onclick="removeEtiket(' + etiketCounter + ')">' +
                            '<i class="fas fa-times"></i>' +
                        '</button>' +
                    '</div>' +
                    '<div class="col-md-8">' +
                        '<input type="text" class="form-control form-control-sm etiket-code-input" name="etikets[' + etiketCounter + '][code]" placeholder="کد اتیکت" data-index="' + etiketCounter + '" onblur="validateEtiketCode(' + etiketCounter + ')">' +
                        '<small class="text-danger etiket-error" id="etiket-error-' + etiketCounter + '" style="display:none;"></small>' +
                    '</div>' +
                '</div>' +
            '</div>';
            
            if ($('#etikets-list p.text-muted').length > 0) {
                $('#etikets-list').html('');
            }
            $('#etikets-list').append(etiketHtml);
        }
        
        function removeEtiket(index) {
            const etiketItem = $('.etiket-item[data-index="' + index + '"]');
            const codeInput = etiketItem.find('.etiket-code-input');
            const code = codeInput.val().trim();
            
            // Remove from inserted codes array
            if (code) {
                insertedEtiketCodes = insertedEtiketCodes.filter(c => c !== code);
            }
            
            etiketItem.remove();
            if ($('#etikets-list .etiket-item').length === 0) {
                $('#etikets-list').html('<p class="text-muted text-center mb-0">هیچ اتیکتی اضافه نشده است</p>');
            }
        }
        
        function validateEtiketCode(index) {
            const codeInput = $('.etiket-item[data-index="' + index + '"] .etiket-code-input');
            const errorElement = $('#etiket-error-' + index);
            const code = codeInput.val().trim();
            
            // Clear previous error
            errorElement.hide().text('');
            codeInput.removeClass('is-invalid');
            
            if (!code) {
                return true; // Empty is OK
            }
            
            // Check for duplicate in modal
            let duplicateInModal = false;
            $('.etiket-code-input').each(function() {
                const otherIndex = $(this).data('index');
                if (otherIndex !== index && $(this).val().trim() === code) {
                    duplicateInModal = true;
                    return false;
                }
            });
            
            if (duplicateInModal) {
                errorElement.text('این کد قبلاً در فرم وارد شده است').show();
                codeInput.addClass('is-invalid');
                return false;
            }
            
            // Check for duplicate in database
            $.ajax({
                url: '{{ route("products.search.by.etiket") }}',
                method: 'GET',
                data: { code: code },
                success: function(response) {
                    if (response && response.exists) {
                        errorElement.text('این کد در پایگاه داده موجود است').show();
                        codeInput.addClass('is-invalid');
                    } else {
                        // Code is valid, add to tracking array
                        insertedEtiketCodes = insertedEtiketCodes.filter(c => c !== code);
                        insertedEtiketCodes.push(code);
                        codeInput.removeClass('is-invalid');
                    }
                },
                error: function() {
                    // On error, assume it's OK (fail open)
                    insertedEtiketCodes = insertedEtiketCodes.filter(c => c !== code);
                    insertedEtiketCodes.push(code);
                    codeInput.removeClass('is-invalid');
                }
            });
            
            return true;
        }
        
        function printEtiket(index) {
            // Implement print functionality
            console.log('Print etiket:', index);
        }
        
        // Function to generate etikets for a product
        function generateEtiket(productId) {
            eraseModalContent();
            $('#dynamic-modal-title').text('افزودن اتیکت به محصول');
            
            // Get product info via AJAX (same as getApiResult)
            $.ajax({
                url: `{{route('products.index')}}/${productId}`,
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // Get product data from response
                    const product = response.data || response;
                    const productName = product.name || 'محصول';
                    
                    const modalContent = `
                        <form id="generate-etiket-form">
                            <div class="alert alert-info">
                                <strong>محصول:</strong> ${productName}
                            </div>
                            
                            <div class="form-group mt-4">
                                <label class="font-weight-bold">اتیکت‌ها</label>
                                <div id="generate-etikets-list" class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                    <p class="text-muted text-center mb-0">هیچ اتیکتی اضافه نشده است</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-info mt-2" onclick="addGenerateEtiket()">
                                    <i class="fas fa-plus"></i> افزودن اتیکت
                                </button>
                            </div>
                            
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save"></i> ایجاد اتیکت‌ها
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
                                    انصراف
                                </button>
                            </div>
                        </form>
                    `;
                    
                    appendToModalContent(modalContent);
                    
                    // Setup form submission
                    setupGenerateEtiketFormSubmission(productId);
                    
                    // Show modal
                    showDynamicModal();
                },
                error: function() {
                    // If AJAX fails, still show the form without product name
                    const modalContent = `
                        <form id="generate-etiket-form">
                            <div class="form-group mt-4">
                                <label class="font-weight-bold">اتیکت‌ها</label>
                                <div id="generate-etikets-list" class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                    <p class="text-muted text-center mb-0">هیچ اتیکتی اضافه نشده است</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-info mt-2" onclick="addGenerateEtiket()">
                                    <i class="fas fa-plus"></i> افزودن اتیکت
                                </button>
                            </div>
                            
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save"></i> ایجاد اتیکت‌ها
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
                                    انصراف
                                </button>
                            </div>
                        </form>
                    `;
                    
                    appendToModalContent(modalContent);
                    setupGenerateEtiketFormSubmission(productId);
                    showDynamicModal();
                }
            });
        }
        
        let generateEtiketCounter = 0;
        let generateInsertedEtiketCodes = [];
        
        function addGenerateEtiket() {
            generateEtiketCounter++;
            const etiketHtml = '<div class="etiket-item border rounded p-2 mb-2" data-index="' + generateEtiketCounter + '">' +
                '<div class="row align-items-center">' +
                    '<div class="col-md-2">' +
                        '<button type="button" class="btn btn-sm btn-danger" onclick="removeGenerateEtiket(' + generateEtiketCounter + ')">' +
                            '<i class="fas fa-times"></i>' +
                        '</button>' +
                    '</div>' +
                    '<div class="col-md-10">' +
                        '<input type="text" class="form-control form-control-sm etiket-code-input" name="etikets[' + generateEtiketCounter + '][code]" placeholder="کد اتیکت" data-index="' + generateEtiketCounter + '" onblur="validateGenerateEtiketCode(' + generateEtiketCounter + ')">' +
                        '<small class="text-danger etiket-error" id="generate-etiket-error-' + generateEtiketCounter + '" style="display:none;"></small>' +
                    '</div>' +
                '</div>' +
            '</div>';
            
            if ($('#generate-etikets-list p.text-muted').length > 0) {
                $('#generate-etikets-list').html('');
            }
            $('#generate-etikets-list').append(etiketHtml);
        }
        
        function removeGenerateEtiket(index) {
            const etiketItem = $('.etiket-item[data-index="' + index + '"]');
            const codeInput = etiketItem.find('.etiket-code-input');
            const code = codeInput.val().trim();
            
            // Remove from inserted codes array
            if (code) {
                generateInsertedEtiketCodes = generateInsertedEtiketCodes.filter(c => c !== code);
            }
            
            etiketItem.remove();
            if ($('#generate-etikets-list .etiket-item').length === 0) {
                $('#generate-etikets-list').html('<p class="text-muted text-center mb-0">هیچ اتیکتی اضافه نشده است</p>');
            }
        }
        
        function validateGenerateEtiketCode(index) {
            const codeInput = $('.etiket-item[data-index="' + index + '"] .etiket-code-input');
            const errorElement = $('#generate-etiket-error-' + index);
            const code = codeInput.val().trim();
            
            if (!code) {
                errorElement.hide();
                codeInput.removeClass('is-invalid');
                return false;
            }
            
            // Check for duplicate in modal
            const duplicateInModal = generateInsertedEtiketCodes.filter(c => c === code && c !== code).length > 0;
            if (duplicateInModal) {
                errorElement.text('این کد در لیست تکراری است').show();
                codeInput.addClass('is-invalid');
                return false;
            }
            
            // Check for duplicate in database
            $.ajax({
                url: '{{ route("products.search.by.etiket") }}',
                method: 'GET',
                data: { code: code },
                success: function(response) {
                    if (response && response.exists) {
                        errorElement.text('این کد در پایگاه داده موجود است').show();
                        codeInput.addClass('is-invalid');
                    } else {
                        generateInsertedEtiketCodes = generateInsertedEtiketCodes.filter(c => c !== code);
                        generateInsertedEtiketCodes.push(code);
                        codeInput.removeClass('is-invalid');
                        errorElement.hide();
                    }
                },
                error: function() {
                    // On error, assume it's OK (fail open)
                    generateInsertedEtiketCodes = generateInsertedEtiketCodes.filter(c => c !== code);
                    generateInsertedEtiketCodes.push(code);
                    codeInput.removeClass('is-invalid');
                    errorElement.hide();
                }
            });
            
            return true;
        }
        
        function setupGenerateEtiketFormSubmission(productId) {
            $('#generate-etiket-form').off('submit').on('submit', function(e) {
                e.preventDefault();
                
                // Validate at least one etiket
                const etiketInputs = $('#generate-etikets-list .etiket-code-input');
                let hasValidEtiket = false;
                
                etiketInputs.each(function() {
                    const code = $(this).val().trim();
                    if (code && !$(this).hasClass('is-invalid')) {
                        hasValidEtiket = true;
                        return false; // break
                    }
                });
                
                if (!hasValidEtiket) {
                    alert('لطفاً حداقل یک کد اتیکت معتبر وارد کنید');
                    return false;
                }
                
                const formData = new FormData();
                
                // Collect all etiket codes
                etiketInputs.each(function() {
                    const code = $(this).val().trim();
                    if (code && !$(this).hasClass('is-invalid')) {
                        formData.append('etikets[][code]', code);
                    }
                });
                
                // Add CSRF token
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                
                $.ajax({
                    url: '{{ route("products.etikets.store", ":product") }}'.replace(':product', productId),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#dynamic-modal').modal('hide');
                        if (typeof window.refreshTable === 'function') {
                            window.refreshTable();
                        }
                        if (response.message) {
                            // Show success message (you can use toastr here)
                            console.log(response.message);
                        }
                    },
                    error: function(xhr) {
                        console.error('Error creating etikets:', xhr);
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            alert(xhr.responseJSON.message);
                        } else {
                            alert('خطا در ایجاد اتیکت‌ها');
                        }
                    }
                });
            });
        }

        // Function to generate comprehensive product creation modal HTML
        function getComprehensiveProductCreationModalHTML() {
            const categoryOptions = '@foreach($categories as $category) <option value="{{$category->id}}" >{{$category->title}}</option> @endforeach'
            
            return `
                <form id="create-comprehensive-product-form" action="{{route('comprehensive_product.store')}}" method="POST" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    
                    <div class="row">
                        <!-- Left Column: Image Upload Section -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">تصویر اصلی محصول</label>
                                <div class="image-upload-container mb-3">
                                    <div class="image-preview-large" id="comprehensive-cover-image-preview" style="width: 100%; height: 250px; border: 2px dashed #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; cursor: pointer; position: relative;">
                                        <div class="text-center">
                                            <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">برای آپلود تصویر کلیک کنید</p>
                                        </div>
                                        <input type="file" id="comprehensive-cover-image-input" name="cover_image" accept="image/*" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer;" onchange="previewComprehensiveCoverImage(this)">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="font-weight-bold">گالری تصاویر</label>
                                <div class="gallery-preview-container d-flex gap-2 mb-2">
                                    <div class="gallery-item-preview" id="comprehensive-gallery-preview-1" style="width: 80px; height: 80px; border: 2px dashed #ddd; border-radius: 6px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; cursor: pointer; position: relative;">
                                        <i class="fas fa-plus text-muted"></i>
                                        <input type="file" name="gallery[]" accept="image/*" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer;" onchange="previewComprehensiveGalleryImage(this, 1)">
                                    </div>
                                    <div class="gallery-item-preview" id="comprehensive-gallery-preview-2" style="width: 80px; height: 80px; border: 2px dashed #ddd; border-radius: 6px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; cursor: pointer; position: relative;">
                                        <i class="fas fa-plus text-muted"></i>
                                        <input type="file" name="gallery[]" accept="image/*" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer;" onchange="previewComprehensiveGalleryImage(this, 2)">
                                    </div>
                                    <div class="gallery-item-preview" id="comprehensive-gallery-preview-3" style="width: 80px; height: 80px; border: 2px dashed #ddd; border-radius: 6px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; cursor: pointer; position: relative;">
                                        <i class="fas fa-plus text-muted"></i>
                                        <input type="file" name="gallery[]" accept="image/*" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer;" onchange="previewComprehensiveGalleryImage(this, 3)">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column: Form Fields -->
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="comprehensive-product-name" class="font-weight-bold">نام محصول جامع <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="comprehensive-product-name" name="name" required placeholder="مثال: ست طلا">
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
                                    ${categoryOptions}
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
                        <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
                            انصراف
                        </button>
                    </div>
                </form>
            `;
        }
        
        // Function to initialize comprehensive product creation modal
        function initializeComprehensiveProductCreationModal() {
            // Remove any existing event listeners to prevent duplicates
            $('#dynamic-modal').off('shown.bs.modal.createComprehensiveProduct');
            
            // Wait for modal to be shown and DOM to be ready
            $('#dynamic-modal').on('shown.bs.modal.createComprehensiveProduct', function() {
                // Destroy existing Select2 instances if any
                if ($('#comprehensive-product-categories').hasClass('select2-hidden-accessible')) {
                    $('#comprehensive-product-categories').select2('destroy');
                }
                if ($('#comprehensive-etiket-select').hasClass('select2-hidden-accessible')) {
                    $('#comprehensive-etiket-select').select2('destroy');
                }
                
                // Initialize Select2 for categories
                $('#comprehensive-product-categories').select2({
                    placeholder: 'دسته‌بندی‌ها را انتخاب کنید',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#dynamic-modal')
                });
                
                // Initialize Select2 for etiket/product selection
                $('#comprehensive-etiket-select').select2({
                    placeholder: 'جستجو و انتخاب محصولات',
                    allowClear: true,
                    width: '100%',
                    minimumInputLength: 1,
                    dropdownParent: $('#dynamic-modal'),
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
                    url: '{{route('etiket_search')}}',
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
            });
        }
        
        // Function to setup comprehensive product form submission
        function setupComprehensiveProductFormSubmission() {
            // Remove any existing handlers to prevent duplicates
            $('#create-comprehensive-product-form').off('submit');
            
            // Handle form submission
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
                
                // Handle multiple select fields
                $(this).find('select[multiple]').each(function() {
                    const $select = $(this);
                    const name = $select.attr('name');
                    if (name && $select.val() && $select.val().length > 0) {
                        $select.val().forEach(function(value) {
                            formData.append(name, value);
                        });
                    }
                });
                
                // Explicitly add cover image
                const coverImageInput = document.getElementById('comprehensive-cover-image-input');
                if (coverImageInput && coverImageInput.files && coverImageInput.files[0]) {
                    formData.append('cover_image', coverImageInput.files[0]);
                }
                
                // Explicitly add gallery images
                const galleryInputs = document.querySelectorAll('#create-comprehensive-product-form input[name="gallery[]"]');
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
                        $('#dynamic-modal').modal('hide');
                        if (typeof window.refreshTable === 'function') {
                            window.refreshTable();
                        }
                    },
                    error: function(xhr) {
                        console.error('Error creating comprehensive product:', xhr);
                        if (xhr.responseJSON) {
                            console.error('Error details:', xhr.responseJSON);
                        }
                    }
                });
            });
        }
        
        // Helper functions for comprehensive product image preview
        function previewComprehensiveCoverImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                const $preview = $('#comprehensive-cover-image-preview');
                const $input = $(input);
                
                reader.onload = function(e) {
                    // Keep the input in DOM, just update the preview
                    $preview.find('div.text-center, img').remove();
                    
                    // Add preview image
                    $preview.prepend('<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; position: absolute; top: 0; left: 0; z-index: 1;">');
                    
                    // Ensure input stays in DOM with proper styling
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
        
        function previewComprehensiveGalleryImage(input, index) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                const $preview = $('#comprehensive-gallery-preview-' + index);
                const $input = $(input);
                
                reader.onload = function(e) {
                    // Keep the input in DOM, just hide it visually
                    $input.css({
                        'position': 'absolute',
                        'width': '100%',
                        'height': '100%',
                        'opacity': '0',
                        'z-index': '10',
                        'cursor': 'pointer'
                    });
                    
                    // Remove existing preview if any
                    $preview.find('img.preview-img, button.remove-gallery-btn').remove();
                    
                    // Add preview image
                    $preview.prepend('<img class="preview-img" src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px; position: absolute; top: 0; left: 0; z-index: 1;">');
                    
                    // Add remove button
                    $preview.append('<button type="button" class="btn btn-sm btn-danger remove-gallery-btn" style="position: absolute; top: 2px; right: 2px; padding: 2px 6px; z-index: 11;" onclick="removeComprehensiveGalleryImage(' + index + ')"><i class="fas fa-times"></i></button>');
                    
                    // Hide the plus icon
                    $preview.find('i.fa-plus').hide();
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function removeComprehensiveGalleryImage(index) {
            const $preview = $('#comprehensive-gallery-preview-' + index);
            // Remove preview image and button
            $preview.find('img.preview-img, button.remove-gallery-btn').remove();
            // Reset the file input
            const $input = $preview.find('input[type="file"]');
            $input.val('');
            // Reset input styling
            $input.css({
                'position': 'absolute',
                'width': '100%',
                'height': '100%',
                'opacity': '0',
                'cursor': 'pointer'
            });
            // Show the plus icon
            $preview.find('i.fa-plus').show();
        }
        
        // Main function to create comprehensive product
        function createAssembledProduct(){
            eraseModalContent();
            $('#dynamic-modal-title').text('ایجاد محصول جامع');
            
            // Get modal HTML
            const modalContent = getComprehensiveProductCreationModalHTML();
            appendToModalContent(modalContent);
            
            // Initialize modal
            initializeComprehensiveProductCreationModal();
            
            // Setup form submission
            setupComprehensiveProductFormSubmission();
            
            // Show modal and trigger the event
            showDynamicModal();
            
            // Trigger the event if modal is already shown
            setTimeout(function() {
                if ($('#dynamic-modal').hasClass('show')) {
                    $('#dynamic-modal').trigger('shown.bs.modal.createComprehensiveProduct');
                }
            }, 300);
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
                    // alert('Products updated successfully!');

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
                    // alert('Products updated successfully!');

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

        const removeAttributeRow = (button) => {
            $(button).closest('.attribute-row').remove();
        };

        const handleGroupKeydown = (event) => {
            if (event.key === 'Tab') {
                event.preventDefault();
                const groupName = $('#attributeGroup').val().trim();
                const productId = $('#productId').val();
                checkAttributeGroup(groupName, productId);
            }
        };
    </script>

    <script>
        function filterProducts(formId) {
            // Get the form element by ID
            const form = document.getElementById(formId);

            // Create a FormData object from the form
            const formData = new FormData(form);

            // Convert FormData to URL-encoded query string
            const query = new URLSearchParams(formData).toString();

            // Construct the URL with the query string
            const url = "{{route('table.products')}}?" + query;

            // Call the loadDataWithNewUrl function with the constructed URL
            window.loadDataWithNewUrl(url);
        }

        function filterProductsSelectOption(element){
            let val = $(element).val()
            if(val !== '0'){
                window.loadDataWithNewUrl("{{route('table.products')}}"+val);
            }else{
                window.loadDataWithNewUrl("{{route('table.products')}}");
            }
        }

        function changeCategorySelectOption(element) {
            let val = $(element).val(); // This is an array of selected IDs
            let params = [];

            val.forEach((id, index) => {
                params.push(`category_ids[${index}]=${id}`);
            });

            let queryString = params.join('&');
            let baseUrl = "{{ route('table.products') }}";
            let finalUrl = baseUrl + '?' + queryString;

            window.loadDataWithNewUrl(finalUrl);
        }


        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('search');

            searchInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    let searchVal = $("#search").val()
                    let searchKey = $("#search_key").val()
                    if(searchKey == '0'){
                        toastr.error('ابتدا تعیین کنید جستجو بر اساس چه معیاری باشد')
                    }else{
                        window.loadDataWithNewUrl('{{ route('table.products') }}?searchKey='+searchKey+'&searchVal='+searchVal);
                    }
                }
            });
        });
        
        /**
         * Remove cover image from product
         */
        function removeCoverImage(productId) {
            if (!confirm('آیا از حذف تصویر کاور مطمئن هستید؟')) {
                return;
            }
            
            $.ajax({
                url: `/admin/products/remove-cover/${productId}`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        
                        // Update the image preview to show "no image" state
                        $('#image-preview').html('<p class="text-muted">تصویری وجود ندارد</p>');
                        
                        // Refresh the table
                        if (typeof window.refreshTable === 'function') {
                            window.refreshTable();
                        }
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    console.error('Error removing cover image:', xhr);
                    toastr.error('خطا در حذف تصویر کاور');
                }
            });
        }

        /**
         * Export products to Excel with current filters
         */
        function exportProducts() {
            // Get current filter values
            const filter = $('select[name="filter"]').val();
            const categoryIds = $('select[name="category_ids[]"]').val();
            const searchKey = $('select[name="searchKey"]').val();
            const searchVal = $('input[name="searchVal"]').val();

            // Build query string
            let params = new URLSearchParams();
            
            if (filter) params.append('filter', filter);
            if (categoryIds && categoryIds.length > 0) {
                categoryIds.forEach(id => params.append('category_ids[]', id));
            }
            if (searchKey) params.append('searchKey', searchKey);
            if (searchVal) params.append('searchVal', searchVal);

            // Construct export URL
            const exportUrl = '{{ route('products.export') }}' + (params.toString() ? '?' + params.toString() : '');
            
            // Download the file
            window.location.href = exportUrl;
            
            toastr.success('در حال تهیه فایل اکسل...');
        }

        /**
         * Recalculate discounts for all products with discount_percentage > 0
         */
        function recalculateDiscounts() {
            if (!confirm('آیا از محاسبه مجدد تخفیف تمام محصولات با تخفیف مطمئن هستید؟')) {
                return;
            }

            // Show loading message
            toastr.info('در حال محاسبه تخفیف ها...', 'لطفا صبر کنید');

            $.ajax({
                url: '{{ route('products.recalculate_discounts') }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        
                        // Refresh the table
                        if (typeof window.refreshTable === 'function') {
                            window.refreshTable();
                        } else if (typeof window.loadDataWithNewUrl === 'function') {
                            window.loadDataWithNewUrl('{{ route('table.products') }}');
                        }
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    console.error('Error recalculating discounts:', xhr);
                    let errorMessage = 'خطا در محاسبه مجدد تخفیف ها';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                }
            });
        }

    </script>
@endsection