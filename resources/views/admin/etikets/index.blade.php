@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'اتیکت‌ها'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'اتیکت‌ها']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <div class="row mb-3">
                <form id="filterForm" action="" class="col-12">
                    <div class="row">
                        <x-form.input title="اسم" name="name" value="{{request('name')}}" col="col-3 mb-3" />
                        <x-form.input title="کد" name="code" value="{{request('code')}}" col="col-3 mb-3" />
                        <x-form.input title="وزن" name="weight" value="{{request('weight')}}" type="number" step="0.01" col="col-3 mb-3" />
                        <x-form.input title="از وزن" name="weight_from" value="{{request('weight_from')}}" type="number" step="0.01" col="col-3 mb-3" />
                        <x-form.input title="تا وزن" name="weight_to" value="{{request('weight_to')}}" type="number" step="0.01" col="col-3 mb-3" />
                        <x-form.select-option title="دسته بندی ها" id="category_ids" multiple="multiple" name="category_ids[]" col="col-3 mb-3">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                        @if(in_array($category->id, request()->input('category_ids', []))) selected @endif>
                                    {{ $category->title }}
                                </option>
                            @endforeach
                        </x-form.select-option>
                        <div class="col-12">
                            <button type="button" onclick="filterEtikets()" class="btn btn-success" style="width:100%">فیلتر</button>
                        </div>
                    </div>
                </form>
            </div>
        </x-slot>
        <x-dataTable
            :url="route('table.etikets')"
            id="etikets-table"
            :columns="[
                            ['label' => 'کد', 'key' => 'code', 'type' => 'text', 'sortable' => true],
                            ['label' => 'اسم', 'key' => 'name', 'type' => 'text', 'sortable' => true],
                            ['label' => 'وزن', 'key' => 'weight', 'type' => 'text', 'sortable' => true],
                            ['label' => 'قیمت', 'key' => 'price', 'type' => 'text'],
                            ['label' => 'محصول', 'key' => 'product_name', 'type' => 'text'],
                            ['label' => 'دسته بندی ها', 'key' => 'categories', 'type' => 'text'],
                            ['label' => 'موجودی', 'key' => 'is_mojood', 'type' => 'text'],
                            ['label' => 'درصد اجرت', 'key' => 'ojrat', 'type' => 'text'],
                            ['label' => 'درصد خرید', 'key' => 'darsad_kharid', 'type' => 'text'],
                            ['label' => 'تاریخ ایجاد', 'key' => 'created_at', 'type' => 'text', 'sortable' => true],
                        ]"
        >
        </x-dataTable>
    </x-page>

@endsection

@push('scripts')
<script>
    function filterEtikets() {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        // Get all form values
        const name = formData.get('name') || '';
        const code = formData.get('code') || '';
        const weight = formData.get('weight') || '';
        const weightFrom = formData.get('weight_from') || '';
        const weightTo = formData.get('weight_to') || '';
        
        // Get all selected categories
        const categorySelect = document.getElementById('category_ids');
        const selectedCategories = Array.from(categorySelect.selectedOptions).map(option => option.value);
        
        // Build query string
        if (name) params.append('name', name);
        if (code) params.append('code', code);
        if (weight) params.append('weight', weight);
        if (weightFrom) params.append('weight_from', weightFrom);
        if (weightTo) params.append('weight_to', weightTo);
        selectedCategories.forEach(catId => {
            params.append('category_ids[]', catId);
        });
        
        const queryString = params.toString();
        const url = '{{ route("table.etikets") }}' + (queryString ? '?' + queryString : '');
        
        if (typeof window.loadDataWithNewUrl === 'function') {
            window.loadDataWithNewUrl(url);
        } else if ($('#etikets-table').length) {
            $('#etikets-table').DataTable().ajax.url(url).load();
        }
    }
</script>
@endpush

