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
            <button class="btn btn-primary mb-3"  href="#">افزودن محصول جامع</button>
            <button class="btn btn-primary mb-3"  id="refreshTable">refresh</button>
        </x-slot>
        <x-dataTable
            :url="route('table.products')"
            id="products-table"
            hasCheckbox="true"
            :columns="[
                            ['label' => 'تصویر محصول', 'key' => 'image', 'type' => 'image'],
                            ['label' => 'نام محصول', 'key' => 'name', 'type' => 'text','url' => 'https://google.com'],
                            ['label' => 'وزن', 'key' => 'weight', 'type' => 'text'],
                            ['label' => 'درصد اجرت', 'key' => 'ojrat_percentage', 'type' => 'text'],
                            ['label' => 'موجودی', 'key' => 'count', 'type' => 'text'],
                            ['label' => 'درصد تخفیف', 'key' => 'discount_percentage', 'type' => 'text'],
                            ['label' => 'دسته بندی ها', 'key' => 'categories_title', 'type' => 'text'],
                        ]"
            :items="$products"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modalEdit'],
                        ]"
        >
        </x-dataTable>
    </x-page>

    <!-- Dynamic modal -->
    <div class="modal fade" id="dynamic-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                    <div class="form-group">
                        <label for="product-name">نام محصول</label>
                        <input type="text" class="form-control" id="product-name" value="${product.name || ''}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="product-price">قیمت</label>
                        <input type="number" class="form-control" id="product-price" value="${product.price || ''}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="product-price">قیمت با تخفیف</label>
                        <input type="number" class="form-control" id="discounted_price" value="${product.discounted_price || ''}" >
                    </div>
                    <div class="form-group">
                        <label for="product-description">توضیحات</label>
                        <textarea class="form-control" id="product-description" rows="4">${product.description || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="product-categories">دسته بندی</label>
                        <select name="categories" id="product-categories" class="form-control" multiple>
                            ${categoryOptions}
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="product-cover-image">تصویر کاور</label>
                        <input type="file" class="form-control-file" id="product-cover-image" name="cover_image" accept="image/*">
                        ${product.image ? `<img src="${product.image}" alt="Current Cover Image" class="img-thumbnail mt-2" style="max-width: 100px;">` : ''}
                    </div>
                    <div class="form-group">
                        <label for="product-gallery">گالری تصاویر</label>
                        <div id="product-gallery"></div>
                    </div>
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
                        preloaded: product.gallery || []
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
                        formData.append('name', $('#product-name').val());
                        formData.append('price', $('#product-price').val());
                        formData.append('weight', $('#product-weight').val());
                        formData.append('discount_percentage', $('#product-discount').val());
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
                                window.refreshTable()
                                toastr.success('محصول با موفقیت به‌روزرسانی شد!');
                                $('#dynamic-modal').modal('hide');
                                // Optionally refresh your product list here
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
    </script>
@endsection