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
            <div class="row">
                <x-form.select-option title="فیلتر" id="test" name="filters" col="col-3" onChange="filterProductsSelectOption(this)">
                    <option value="?filter=only_images">محصولات عکس دار</option>
                    <option value="?filter=only_without_images">محصولات غیر عکس دار</option>
                    <option value="?filter=only_without_gallery">محصولات بدون گالری</option>
                    <option value="?filter=only_unavilables">محصولات ناموجود</option>
                    <option value="?filter=only_main_products">محصولات متغییر</option>
                    <option value="?filter=only_discountables">محصولات تخفیف دار</option>
                </x-form.select-option>
                <x-form.select-option title="دسته بندی" id="test" multiple="multiple" name="categories" col="col-3" onChange="changeCategorySelectOption(this)">
                    @foreach($categories as $category)
                        <option value="{{$category->id}}">{{$category->title}}</option>
                    @endforeach
                </x-form.select-option>
                <x-form.select-option title="جستجو بر اساس" id="search_key" name="search_key" col="col-3" >
                    <option value="name">اسم محصول</option>
                    <option value="weight">وزن</option>
                    <option value="ojrat">درصد اجرت</option>
                    <option value="count">موجودی</option>
                    <option value="discount_percentage">درصد تخفیف</option>
                    <option value="etiket_code">اتیکت</option>
                </x-form.select-option>
                <x-form.input title="جستجو" name="search" id="search" />
                <div class="col-3">
                    <button type="button" class="btn btn-primary" onclick="showBulkUpdateModal()">ویرایش دسته جمعی </button>
                </div>
            </div>
        </x-slot>
        <x-dataTable
            :url="route('table.products')"
            id="products-table"
            hasCheckbox="true"
            :columns="[
                            ['label' => 'کد اتیکت', 'key' => 'etiketsCodeAsArray', 'type' => 'ajax','route' =>'product.etikets','ajax_key' => 'slug'],
                            ['label' => 'تصویر محصول', 'key' => 'image', 'type' => 'image'],
                            ['label' => 'نام محصول', 'key' => 'name', 'type' => 'text','url' => 'https://google.com'],
                            ['label' => 'وزن', 'key' => 'weight', 'type' => 'text'],
                            ['label' => 'قیمت', 'key' => 'price', 'type' => 'text'],
                            ['label' => 'درصد اجرت', 'key' => 'ojrat', 'type' => 'text'],
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
                    <input type="hidden" id="productId" value="${product.id}">
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
                        <select name="categories" id="product-categories" class="form-control" multiple>
                            ${categoryOptions}
                        </select>
                    </div>
                    <div class="custom-file mt-5 mb-5">
                        <label for="product-cover-image" class="custom-file-label">تصویر کاور</label>
                        <input type="file" class="custom-file-input" id="product-cover-image" name="cover_image" accept="image/*" onchange="showImagePreview('product-cover-image', 'image-preview', '${product.image || ''}')">
                        <div id="image-preview" class="d-flex justify-content-center mt-2">
                            ${product.image ? `<img src="${product.image}" alt="Current Cover Image" class="img-thumbnail" style="max-width: 150px;" onclick="changeSize(this)">` : ''}
                        </div>
                    </div>
                    <div class="form-group mt-5">
                        <label for="product-gallery">گالری تصاویر</label>
                        <div id="product-gallery"></div>
                    </div>

                    <div class="form-group">
                        <label for="attributeGroup">گروه ویژگی</label>
                        <input type="text" name="attribute_group" class="form-control" id="attributeGroup" name="attribute_group" placeholder="نام گروه ویژگی را وارد کنید" onkeydown="handleGroupKeydown(event)">
                        <div id="loadingSpinner" class="spinner-border spinner-border-sm d-none" role="status">
                            <span class="sr-only">در حال بارگذاری...</span>
                        </div>
                    </div>
                    <div id="attributeInputs" class="mt-3"></div>

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
                        extensions: ['.jpg', '.jpeg', '.png', '.gif', '.svg','.JPG','.JPEG'],
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
                        formData.append('discount_percentage', $('#discount_percentage').val());
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

        const createAttributeRow = (attributeId, name, value, index) => `
    <div class="attribute-row row mt-2 mb-2">
        <div class="col-5">
            <input type="text" class="form-control attribute-name"
                   name="attributes[${index}][name]"
                   data-attribute-id="${attributeId || ''}"
                   value="${name || ''}"
                   placeholder="نام ویژگی"
                   ${attributeId ? 'readonly' : ''}>
        </div>
        <div class="col-6">
            <input type="text" class="form-control attribute-value"
                   name="attributes[${index}][value]"
                   value="${value || ''}"
                   placeholder="مقدار ویژگی">
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-danger btn-sm remove-btn" onclick="removeAttributeRow(this)">-</button>
        </div>
    </div>
`;

        const addAddButton = () => {
            const addButton = $('<button type="button" class="btn btn-success btn-sm mt-2 mb-2">+ افزودن ویژگی</button>');
            addButton.on('click', () => addAttributeInput(null, '', '', $('.attribute-row').length));
            $('#attributeInputs').append(addButton);
        };

        const addAttributeInput = (attributeId, name, value, index) => {
            $('#attributeInputs').append(createAttributeRow(attributeId, name, value, index));
        };

        const loadAttributes = (attributes, attributeValues) => {
            attributes.forEach((attr, index) => {
                const value = attributeValues.find(val => val.attribute_id === attr.id)?.value || '';
                addAttributeInput(attr.id, attr.name, value, index);
            });
        };

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


    </script>
@endsection