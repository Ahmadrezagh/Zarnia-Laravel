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
                    <label for="related_products" class="form-label">محصولات مرتبط</label>
                    <select id="related_products" style="width: 100% !important;" name="related_products[]" class="form-select select2-ajax" multiple="true" data-ajax-url="{{ route('products.ajax.search') }}"></select>
                </div>
                <div class="mb-3">
                    <label for="complementary_products" class="form-label">محصولات مکمل</label>
                    <select id="complementary_products" style="width: 100% !important;" name="complementary_products[]" class="form-select select2-ajax" multiple="true" data-ajax-url="{{ route('products.ajax.search') }}"></select>
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


                </x-modal.update>
            @endforeach
        </x-table>
    </x-page>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-ajax').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        ajax: {
                            url: $(this).data('ajax-url'),
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    q: params.term,
                                    page: params.page || 1
                                };
                            },
                            processResults: function(data) {
                                console.log('AJAX response:', data);
                                // Transform response to remove prefixes and categorize
                                var results = (data.results || data).map(function(item) {
                                    var cleanId = item.id
                                    return {
                                        id: cleanId, // Clean ID for form submission
                                        text: item.text + (item.id.startsWith('Product:') ? ' (محصول)' : ' (دسته‌بندی)')
                                    };
                                });
                                return {
                                    results: results,
                                    pagination: { more: (data.pagination && data.pagination.more) || false }
                                };
                            },
                        },
                        placeholder: 'جستجو کنید...',
                        minimumInputLength: 1,
                        allowClear: true,
                        language: {
                            searching: function() { return 'در حال جستجو...'; },
                            noResults: function() { return 'نتیجه‌ای یافت نشد'; }
                        }
                    });
                }
            });
        });
    </script>
@endsection
@section('js')


@endsection