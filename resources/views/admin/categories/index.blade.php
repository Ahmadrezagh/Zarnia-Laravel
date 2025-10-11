@extends('layouts.panel')
@section('css')
    <style>
        .select2-container {
            width: 100% !important;
        }
        .select2-search__field {
            direction: rtl;
        }


    </style>
    <style>
        .custom-multiselect {
            position: relative;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            width: 100%;
        }
        .custom-multiselect-display {
            padding: 8px 10px;
        }
        .custom-multiselect-dropdown {
            display: none;
            position: absolute;
            z-index: 9999;
            background: #fff;
            border: 1px solid #ddd;
            width: 100%;
            max-height: 250px;
            overflow-y: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .custom-multiselect.open .custom-multiselect-dropdown {
            display: block;
        }
        .custom-multiselect-search {
            width: 100%;
            border: none;
            border-bottom: 1px solid #eee;
            padding: 6px 8px;
        }
        .custom-multiselect-options label {
            display: flex;
            align-items: center;
            padding: 6px 10px;
            cursor: pointer;
        }
        .custom-multiselect-options label:hover {
            background: #f3f3f3;
        }
        .custom-multiselect-options input {
            margin-left: 8px;
        }
    </style>

@endsection
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'دسته بندی ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'دسته بندی ها']
      ]" />
    <!-- End Page Header -->

    <!-- Row -->
    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن دسته بندی</button>

            <x-modal.create id="modal-create" title="ساخت دسته بندی" action="{{route('categories.store')}}" >
                <x-form.input title="نام"  name="title" />
                <x-form.select-option title="دسته بندی والد" name="parent_id" >
                    @foreach($categories as $parent_category)
                        <option value="{{$parent_category->id}}">{{$parent_category->title}}</option>
                    @endforeach
                </x-form.select-option>
                <x-form.file-input title="تصویر دسته بندی" name="cover_image" />
                <x-form.select-option title="گروه ویژگی" name="attribute_group_ids[]" multiple="true" >
                    @foreach($attribute_groups as $attribute_group)
                        <option value="{{ $attribute_group->id }}">{{ $attribute_group->name }}</option>
                    @endforeach
                </x-form.select-option>
                <div class="mb-3">
                    <label class="form-label">محصولات مرتبط</label>
                    <div class="custom-multiselect"
                         data-ajax-url="{{ route('products.search') }}"
                    >
                        <div class="custom-multiselect-display">انتخاب کنید...</div>
                        <div class="custom-multiselect-dropdown">
                            <input type="text" class="custom-multiselect-search" placeholder="جستجو...">
                            <div class="custom-multiselect-options"></div>
                        </div>
                        <input type="hidden" name="related_products">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">محصولات مکمل</label>
                    <div class="custom-multiselect"
                         data-ajax-url="{{ route('products.search') }}"
                    >
                        <div class="custom-multiselect-display">انتخاب کنید...</div>
                        <div class="custom-multiselect-dropdown">
                            <input type="text" class="custom-multiselect-search" placeholder="جستجو...">
                            <div class="custom-multiselect-options"></div>
                        </div>
                        <input type="hidden" name="complementary_products">
                    </div>
                </div>
            </x-modal.create>
        </x-slot>
        <x-table
            :url="route('table.categories')"
            id="categories-table"
            :columns="[
                            ['label' => 'تصویر', 'key' => 'image', 'type' => 'image'],
                            ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
                        ]"
            :items="$categories"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >

            @foreach($categories as $category)
                <!-- Modal -->
                <x-modal.destroy id="modal-destroy-{{$category->id}}" title="حذف دسته بندی" action="{{route('categories.destroy', $category->id)}}" title="{{$category->title}}" />

                <x-modal.update id="modal-edit-{{$category->id}}" title="ساخت دسته بندی" action="{{route('categories.update',$category->slug)}}" >
                    <x-form.input title="نام"  name="title" :value="$category->title" />
                    <x-form.select-option title="دسته بندی والد" name="parent_id" >
                        @foreach($categories as $parent_category)
                            @if( ($parent_category->id != $category->id) && (!$category->isParentOfCategory($parent_category) ))
                                <option value="{{$category->id}}" @if($category->parent_id == $parent_category->id) selected @endif >{{$parent_category->title}}</option>
                            @endif
                        @endforeach
                    </x-form.select-option>
                    <x-form.file-input title="تصویر دسته بندی" name="cover_image" />
                    <x-form.select-option title="گروه ویژگی" name="attribute_group_ids[]" multiple="true" >
                        @foreach($attribute_groups as $attribute_group)
                            <option value="{{ $attribute_group->id }}" @if($category->attributeGroups()->where('attribute_group_id','=',$attribute_group->id)->exists()) selected @endif >{{ $attribute_group->name }}</option>
                        @endforeach
                    </x-form.select-option>
                    <div class="mb-3">
                        <label class="form-label">محصولات مرتبط</label>
                        <div class="custom-multiselect"
                             data-ajax-url="{{ route('products.search') }}"
                             data-preselected='@json($category->relatedProducts->map(fn($item) => ["id" => "Product:{$item->id}", "text" => $item->name]))'>
                            <div class="custom-multiselect-display">انتخاب کنید...</div>
                            <div class="custom-multiselect-dropdown">
                                <input type="text" class="custom-multiselect-search" placeholder="جستجو...">
                                <div class="custom-multiselect-options"></div>
                            </div>
                            <input type="hidden" name="related_products">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">محصولات مکمل</label>
                        <div class="custom-multiselect"
                             data-ajax-url="{{ route('products.search') }}"
                             data-preselected='@json($category->complementaryProducts->map(fn($item) => ["id" => "Product:{$item->id}", "text" => $item->name]))'>
                            <div class="custom-multiselect-display">انتخاب کنید...</div>
                            <div class="custom-multiselect-dropdown">
                                <input type="text" class="custom-multiselect-search" placeholder="جستجو...">
                                <div class="custom-multiselect-options"></div>
                            </div>
                            <input type="hidden" name="complementary_products">
                        </div>
                    </div>


                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

    <script>
        // document.addEventListener('DOMContentLoaded', () => {
        //     document.querySelectorAll('.custom-multiselect').forEach((ms) => {
        //         const display = ms.querySelector('.custom-multiselect-display');
        //         const dropdown = ms.querySelector('.custom-multiselect-dropdown');
        //         const optionsContainer = ms.querySelector('.custom-multiselect-options');
        //         const searchInput = ms.querySelector('.custom-multiselect-search');
        //         const hiddenInput = ms.querySelector('input[type="hidden"]');
        //
        //         const ajaxUrl = ms.dataset.ajaxUrl;
        //         const preselected = JSON.parse(ms.dataset.preselected || '[]');
        //         let options = [];
        //         let selected = new Set(preselected.map((i) => i.id));
        //
        //         // ✅ Render dropdown for this instance only
        //         const renderOptions = () => {
        //             optionsContainer.innerHTML = '';
        //             options.forEach((opt) => {
        //                 const checked = selected.has(opt.id) ? 'checked' : '';
        //                 const label = document.createElement('label');
        //                 label.innerHTML = `<input type="checkbox" value="${opt.id}" ${checked}> ${opt.text}`;
        //                 optionsContainer.appendChild(label);
        //             });
        //         };
        //
        //         // ✅ Update display text and hidden input for this instance
        //         const updateDisplay = () => {
        //             const selectedTexts = options
        //                 .filter((o) => selected.has(o.id))
        //                 .map((o) => o.text);
        //             display.textContent = selectedTexts.length
        //                 ? selectedTexts.join(', ')
        //                 : 'انتخاب کنید...';
        //             hiddenInput.value = JSON.stringify([...selected]);
        //         };
        //
        //         // ✅ Fetch options for this instance
        //         const fetchOptions = async (query = '') => {
        //             try {
        //                 const res = await fetch(`${ajaxUrl}?q=${encodeURIComponent(query)}`);
        //                 const data = await res.json();
        //                 options = data;
        //                 renderOptions();
        //                 updateDisplay();
        //             } catch (e) {
        //                 console.error('Fetch failed:', e);
        //             }
        //         };
        //
        //         // ✅ Toggle dropdown (only one open at a time)
        //         display.addEventListener('click', (e) => {
        //             e.stopPropagation();
        //             document.querySelectorAll('.custom-multiselect.open')
        //                 .forEach(el => el !== ms && el.classList.remove('open'));
        //             ms.classList.toggle('open');
        //             if (ms.classList.contains('open')) searchInput.focus();
        //         });
        //
        //         // ✅ Close dropdown when clicking outside
        //         document.addEventListener('click', (e) => {
        //             if (!ms.contains(e.target)) ms.classList.remove('open');
        //         });
        //
        //         // ✅ Handle search
        //         searchInput.addEventListener('input', (e) => {
        //             fetchOptions(e.target.value);
        //         });
        //
        //         // ✅ Handle checkbox change (only within this multiselect)
        //         optionsContainer.addEventListener('change', (e) => {
        //             const id = e.target.value;
        //             if (e.target.checked) selected.add(id);
        //             else selected.delete(id);
        //             updateDisplay();
        //         });
        //
        //         // ✅ Initial load
        //         fetchOptions();
        //     });
        // });
    </script>
    <script>
        // document.addEventListener('DOMContentLoaded', () => {
        //     document.querySelectorAll('.custom-multiselect').forEach((ms) => {
        //         const display = ms.querySelector('.custom-multiselect-display');
        //         const dropdown = ms.querySelector('.custom-multiselect-dropdown');
        //         const optionsContainer = ms.querySelector('.custom-multiselect-options');
        //         const searchInput = ms.querySelector('.custom-multiselect-search');
        //         const hiddenInput = ms.querySelector('input[type="hidden"]');
        //
        //         const ajaxUrl = ms.dataset.ajaxUrl;
        //         const preselected = JSON.parse(ms.dataset.preselected || '[]');
        //         let options = [];
        //         let selected = new Set(preselected.map((i) => i.id));
        //
        //         // ✅ Render options
        //         const renderOptions = () => {
        //             optionsContainer.innerHTML = '';
        //             options.forEach((opt) => {
        //                 const checked = selected.has(opt.id) ? 'checked' : '';
        //
        //                 // 👇 Extract the type from ID (e.g., "Product:1" → "Product")
        //                 const typeMatch = opt.id.includes(':') ? opt.id.split(':')[0] : '';
        //                 const label = document.createElement('label');
        //                 label.innerHTML = `
        //   <input type="checkbox" value="${opt.id}" ${checked}>
        //   <span>(${typeMatch}) ${opt.text}</span>
        // `;
        //                 optionsContainer.appendChild(label);
        //             });
        //         };
        //
        //         // ✅ Update selected display
        //         const updateDisplay = () => {
        //             const selectedTexts = options
        //                 .filter((o) => selected.has(o.id))
        //                 .map((o) => {
        //                     const type = o.id.includes(':') ? o.id.split(':')[0] : '';
        //                     return `(${type}) ${o.text}`;
        //                 });
        //
        //             display.textContent = selectedTexts.length
        //                 ? selectedTexts.join(', ')
        //                 : 'انتخاب کنید...';
        //             hiddenInput.value = JSON.stringify([...selected]);
        //         };
        //
        //         // ✅ Fetch options
        //         const fetchOptions = async (query = '') => {
        //             try {
        //                 const res = await fetch(`${ajaxUrl}?q=${encodeURIComponent(query)}`);
        //                 const data = await res.json();
        //                 options = data;
        //                 renderOptions();
        //                 updateDisplay();
        //             } catch (e) {
        //                 console.error('Fetch failed:', e);
        //             }
        //         };
        //
        //         // ✅ Toggle dropdown (isolated per element)
        //         display.addEventListener('click', (e) => {
        //             e.stopPropagation();
        //             document.querySelectorAll('.custom-multiselect.open')
        //                 .forEach(el => el !== ms && el.classList.remove('open'));
        //             ms.classList.toggle('open');
        //             if (ms.classList.contains('open')) searchInput.focus();
        //         });
        //
        //         // ✅ Close on outside click
        //         document.addEventListener('click', (e) => {
        //             if (!ms.contains(e.target)) ms.classList.remove('open');
        //         });
        //
        //         // ✅ Handle search
        //         searchInput.addEventListener('input', (e) => {
        //             fetchOptions(e.target.value);
        //         });
        //
        //         // ✅ Handle checkbox change
        //         optionsContainer.addEventListener('change', (e) => {
        //             const id = e.target.value;
        //             if (e.target.checked) selected.add(id);
        //             else selected.delete(id);
        //             updateDisplay();
        //         });
        //
        //         // ✅ Initial load
        //         fetchOptions();
        //     });
        // });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.custom-multiselect').forEach((ms) => {
                const display = ms.querySelector('.custom-multiselect-display');
                const dropdown = ms.querySelector('.custom-multiselect-dropdown');
                const optionsContainer = ms.querySelector('.custom-multiselect-options');
                const searchInput = ms.querySelector('.custom-multiselect-search');
                const hiddenInput = ms.querySelector('input[type="hidden"]');

                const ajaxUrl = ms.dataset.ajaxUrl;
                const preselected = JSON.parse(ms.dataset.preselected || '[]');
                let options = [];
                let selected = new Set(preselected.map((i) => i.id));

                // ✅ English → Persian mapping
                const typeMap = {
                    'Product': 'محصول',
                    'Category': 'دسته‌بندی'
                };

                // ✅ Render options
                const renderOptions = () => {
                    optionsContainer.innerHTML = '';
                    options.forEach((opt) => {
                        const checked = selected.has(opt.id) ? 'checked' : '';

                        // 👇 Extract the type from ID (e.g., "Product:1" → "محصول")
                        const typeKey = opt.id.includes(':') ? opt.id.split(':')[0] : '';
                        const type = typeMap[typeKey] || typeKey;

                        const label = document.createElement('label');
                        label.innerHTML = `
          <input type="checkbox" value="${opt.id}" ${checked}>
          <span>${opt.text} (${type})</span>
        `;
                        optionsContainer.appendChild(label);
                    });
                };

                // ✅ Update selected display
                const updateDisplay = () => {
                    const selectedTexts = options
                        .filter((o) => selected.has(o.id))
                        .map((o) => {
                            const typeKey = o.id.includes(':') ? o.id.split(':')[0] : '';
                            const type = typeMap[typeKey] || typeKey;
                            return `${o.text} (${type})`;
                        });

                    display.textContent = selectedTexts.length
                        ? selectedTexts.join(', ')
                        : 'انتخاب کنید...';
                    hiddenInput.value = JSON.stringify([...selected]);
                };

                // ✅ Fetch options
                const fetchOptions = async (query = '') => {
                    try {
                        const res = await fetch(`${ajaxUrl}?q=${encodeURIComponent(query)}`);
                        const data = await res.json();
                        options = data;
                        renderOptions();
                        updateDisplay();
                    } catch (e) {
                        console.error('Fetch failed:', e);
                    }
                };

                // ✅ Toggle dropdown (isolated per element)
                display.addEventListener('click', (e) => {
                    e.stopPropagation();
                    document.querySelectorAll('.custom-multiselect.open')
                        .forEach(el => el !== ms && el.classList.remove('open'));
                    ms.classList.toggle('open');
                    if (ms.classList.contains('open')) searchInput.focus();
                });

                // ✅ Close on outside click
                document.addEventListener('click', (e) => {
                    if (!ms.contains(e.target)) ms.classList.remove('open');
                });

                // ✅ Handle search
                searchInput.addEventListener('input', (e) => {
                    fetchOptions(e.target.value);
                });

                // ✅ Handle checkbox change
                optionsContainer.addEventListener('change', (e) => {
                    const id = e.target.value;
                    if (e.target.checked) selected.add(id);
                    else selected.delete(id);
                    updateDisplay();
                });

                // ✅ Initial load
                fetchOptions();
            });
        });
    </script>





@endsection
@section('js')


@endsection