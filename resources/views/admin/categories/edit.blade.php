@extends('layouts.panel')

@section('css')
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
    <x-breadcrumb :title="'ویرایش دسته بندی'" :items="[
        ['label' => 'خانه', 'url' => route('home')],
        ['label' => 'دسته بندی ها', 'url' => route('categories.index')],
        ['label' => $category->title]
    ]" />

    <x-page>
        <x-slot name="header">
            <h4>ویرایش دسته بندی: {{ $category->title }}</h4>
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">بازگشت</a>
        </x-slot>

        <form method="POST" action="{{ route('categories.update', $category->slug) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <x-form.input title="نام" name="title" :value="old('title', $category->title)" />
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="slug">نامک (Slug)</label>
                        <input type="text" id="slug" name="slug" class="form-control" dir="ltr"
                               value="{{ old('slug', $category->slug) }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <x-form.select-option title="دسته بندی والد" name="parent_id">
                        <option value="">بدون والد</option>
                        @foreach($allCategories as $parent_category)
                            @if($parent_category->id !== $category->id)
                                <option value="{{ $parent_category->id }}"
                                        @selected(old('parent_id', $category->parent_id) == $parent_category->id)>
                                    {{ $parent_category->title }}
                                </option>
                            @endif
                        @endforeach
                    </x-form.select-option>
                </div>

                <div class="col-md-6">
                    <x-form.file-input title="تصویر دسته بندی" name="cover_image" />
                </div>

                <div class="col-md-12">
                    <x-form.select-option title="گروه ویژگی" name="attribute_group_ids[]" multiple="true">
                        @foreach($attribute_groups as $attribute_group)
                            <option value="{{ $attribute_group->id }}"
                                    @selected(in_array($attribute_group->id, old('attribute_group_ids', $category->attributeGroups->pluck('id')->toArray())))>
                                {{ $attribute_group->name }}
                            </option>
                        @endforeach
                    </x-form.select-option>
                </div>

                <div class="col-md-6">
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
                            <input type="hidden" name="related_products" value="{{ old('related_products') }}">
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
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
                            <input type="hidden" name="complementary_products" value="{{ old('complementary_products') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-success">ذخیره تغییرات</button>
            </div>
        </form>
    </x-page>
@endsection

@section('js')
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

                const updateHiddenInput = () => {
                    hiddenInput.value = JSON.stringify([...selected]);
                };

            const renderOptions = () => {
                optionsContainer.innerHTML = '';
                options.forEach((opt) => {
                    const checked = selected.has(opt.id) ? 'checked' : '';
                    const label = document.createElement('label');
                    label.innerHTML = `<input type="checkbox" value="${opt.id}" ${checked}> ${opt.text}`;
                    optionsContainer.appendChild(label);
                });
            };

            const updateDisplay = () => {
                const selectedTexts = options
                    .filter((o) => selected.has(o.id))
                    .map((o) => o.text);
                display.textContent = selectedTexts.length
                    ? selectedTexts.join(', ')
                    : 'انتخاب کنید...';
                updateHiddenInput();
            };

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

            display.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('.custom-multiselect.open')
                    .forEach(el => el !== ms && el.classList.remove('open'));
                ms.classList.toggle('open');
                if (ms.classList.contains('open')) searchInput.focus();
            });

            document.addEventListener('click', (e) => {
                if (!ms.contains(e.target)) ms.classList.remove('open');
            });

            optionsContainer.addEventListener('change', (e) => {
                const id = e.target.value;
                if (e.target.checked) selected.add(id);
                else selected.delete(id);
                updateDisplay();
            });

            searchInput.addEventListener('input', (e) => fetchOptions(e.target.value));

            options = preselected;
            renderOptions();
            updateDisplay();
            });
        });
    </script>
@endsection

