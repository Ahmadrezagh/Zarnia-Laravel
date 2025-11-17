@extends('layouts.panel')
@section('content')


    <!-- Page Header -->
    <x-breadcrumb :title="'کد های تخفیف'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'کد های تخفیف']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <button class="btn btn-primary mb-3"  data-toggle="modal" data-target="#modal-create">افزودن کد تخفیف</button>
            <x-modal.create id="modal-create" title="ساخت کد تخفیف" action="{{route('discounts.store')}}" >
                <x-form.input title="کد تخفیف"  name="code" />
                <x-form.input title="درصد تخفیف"  name="percentage" />
                <x-form.input title="مبلغ تخفیف"  name="amount" />
                <x-form.input title="حداقل مبلغ خرید برای اعمال تخفیف"  name="min_price" />
                <x-form.input title="حداکثر مبلغ خرید برای اعمال تخفیف"  name="max_price" />
                <x-form.input title="تعداد دفعات اعمال تخفیف"  name="quantity" />
                <x-form.input title="تعداد دفعات اعمال تخفیف به ازای هر کاربر"  name="quantity_per_user" />
                <x-form.input title="تاریخ شروع کد تخفیف"  name="start_at" type="datetime-local" />
                <x-form.input title="تاریخ پایان کد تخفیف"  name="expires_at" type="datetime-local" />
                
                <div class="form-group">
                    <label for="create-user-ids">کاربران مجاز (اختیاری - خالی = همه کاربران)</label>
                    <select name="user_ids[]" id="create-user-ids" class="form-control select2-users" multiple="multiple">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->phone }})</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="create-product-ids">محصولات مجاز (اختیاری - خالی = همه محصولات)</label>
                    <select name="product_ids[]" id="create-product-ids" class="form-control select2-products" multiple="multiple">
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="create-category-ids">دسته‌بندی‌های مجاز (اختیاری - خالی = همه دسته‌بندی‌ها)</label>
                    <select name="category_ids[]" id="create-category-ids" class="form-control select2-categories" multiple="multiple">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->title }}</option>
                        @endforeach
                    </select>
                </div>
            </x-modal.create>
        </x-slot>

        <x-table
            :url="route('table.discounts')"
            id="discounts-table"
            :columns="[
                            ['label' => 'کد تخفیف', 'key' => 'code', 'type' => 'text'],
                            ['label' => 'توضیح کد تخفیف', 'key' => 'description', 'type' => 'text'],
                        ]"
            :items="$discounts"
            :actions="[
                            ['label' => 'ویرایش', 'type' => 'modal-edit'],
                            ['label' => 'حذف', 'type' => 'modal-destroy']
                        ]"
        >
            @foreach($discounts as $discount)

                <x-modal.destroy id="modal-destroy-{{$discount->id}}" title="حذف کد تخفیف" action="{{route('discounts.destroy', $discount->id)}}" title="{{$discount->title}}" />

                <x-modal.update id="modal-edit-{{$discount->id}}" title="ویرایش کد تخفیف" action="{{route('discounts.update',$discount->id)}}" >
                    <x-form.input title="کد تخفیف"  name="code" :value="$discount->code" />
                    <x-form.input title="درصد تخفیف"  name="percentage" :value="$discount->percentage" />
                    <x-form.input title="مبلغ تخفیف"  name="amount" :value="$discount->amount"  />
                    <x-form.input title="حداقل مبلغ خرید برای اعمال تخفیف"  name="min_price" :value="$discount->min_price" />
                    <x-form.input title="حداکثر مبلغ خرید برای اعمال تخفیف"  name="max_price" :value="$discount->max_price"  />
                    <x-form.input title="تعداد دفعات اعمال تخفیف"  name="quantity"  :value="$discount->quantity" />
                    <x-form.input title="تعداد دفعات اعمال تخفیف به ازای هر کاربر"  name="quantity_per_user"  :value="$discount->quantity_per_user" />
                    <x-form.input title="تاریخ شروع کد تخفیف"  name="start_at"  :value="$discount->StartAtYMD" type="datetime-local"  />
                    <x-form.input title="تاریخ پایان کد تخفیف"  name="expires_at"  :value="$discount->ExpiresAtYMD" type="datetime-local" />
                    
                    <div class="form-group">
                        <label for="edit-user-ids-{{$discount->id}}">کاربران مجاز (اختیاری - خالی = همه کاربران)</label>
                        <select name="user_ids[]" id="edit-user-ids-{{$discount->id}}" class="form-control select2-users" multiple="multiple">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" 
                                    {{ $discount->users->contains($user->id) ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->phone }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-product-ids-{{$discount->id}}">محصولات مجاز (اختیاری - خالی = همه محصولات)</label>
                        <select name="product_ids[]" id="edit-product-ids-{{$discount->id}}" class="form-control select2-products" multiple="multiple">
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" 
                                    {{ $discount->products->contains($product->id) ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-category-ids-{{$discount->id}}">دسته‌بندی‌های مجاز (اختیاری - خالی = همه دسته‌بندی‌ها)</label>
                        <select name="category_ids[]" id="edit-category-ids-{{$discount->id}}" class="form-control select2-categories" multiple="multiple">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                    {{ $discount->categories->contains($category->id) ? 'selected' : '' }}>
                                    {{ $category->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

@endsection

@section('scripts')
    <style>
        /* Ensure Select2 dropdowns appear above modal */
        .select2-container {
            z-index: 9999 !important;
        }
        
        .select2-dropdown {
            z-index: 9999 !important;
        }
        
        /* Style Select2 for better appearance */
        .select2-container--default .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff;
            border-color: #006fe6;
            color: white;
            padding: 2px 8px;
            margin-top: 5px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-left: 5px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #ffdddd;
        }
        
        /* RTL support for Select2 */
        .select2-container--default[dir="rtl"] .select2-selection--multiple .select2-selection__choice {
            float: right;
            margin-left: 5px;
            margin-right: 0;
        }
        
        .select2-search__field {
            direction: rtl !important;
        }
    </style>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for create modal
            $('#modal-create').on('shown.bs.modal', function () {
                initializeSelect2InModal('#modal-create');
            });

            // Initialize Select2 for edit modals when they are shown
            $('[id^="modal-edit-"]').on('shown.bs.modal', function () {
                initializeSelect2InModal('#' + $(this).attr('id'));
            });

            // Function to initialize Select2 in a specific modal
            function initializeSelect2InModal(modalSelector) {
                // Destroy existing Select2 instances first
                $(modalSelector + ' .select2-users, ' + modalSelector + ' .select2-products, ' + modalSelector + ' .select2-categories').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });

                // Initialize Select2 for users
                $(modalSelector + ' .select2-users').select2({
                    placeholder: 'انتخاب کاربران (خالی = همه کاربران)',
                    allowClear: true,
                    dir: 'rtl',
                    dropdownParent: $(modalSelector),
                    width: '100%',
                    language: {
                        noResults: function() {
                            return "نتیجه‌ای یافت نشد";
                        },
                        searching: function() {
                            return "در حال جستجو...";
                        }
                    }
                });

                // Initialize Select2 for products
                $(modalSelector + ' .select2-products').select2({
                    placeholder: 'انتخاب محصولات (خالی = همه محصولات)',
                    allowClear: true,
                    dir: 'rtl',
                    dropdownParent: $(modalSelector),
                    width: '100%',
                    language: {
                        noResults: function() {
                            return "نتیجه‌ای یافت نشد";
                        },
                        searching: function() {
                            return "در حال جستجو...";
                        }
                    }
                });

                // Initialize Select2 for categories
                $(modalSelector + ' .select2-categories').select2({
                    placeholder: 'انتخاب دسته‌بندی‌ها (خالی = همه دسته‌بندی‌ها)',
                    allowClear: true,
                    dir: 'rtl',
                    dropdownParent: $(modalSelector),
                    width: '100%',
                    language: {
                        noResults: function() {
                            return "نتیجه‌ای یافت نشد";
                        },
                        searching: function() {
                            return "در حال جستجو...";
                        }
                    }
                });
            }

            // Clean up Select2 when modal is hidden
            $('.modal').on('hidden.bs.modal', function () {
                $(this).find('.select2-users, .select2-products, .select2-categories').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });
            });
        });
    </script>
@endsection
