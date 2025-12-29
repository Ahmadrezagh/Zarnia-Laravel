@extends('layouts.panel')
@section('content')

    <!-- Page Header -->
    <x-breadcrumb :title="'محصولات حذف شده'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'محصولات', 'url' => route('products.index')],
            ['label' => 'محصولات حذف شده']
      ]" />
    <!-- End Page Header -->

    <x-page>
        <x-slot name="header">
            <a href="{{ route('products.index') }}" class="btn btn-secondary mb-3">
                <i class="fas fa-arrow-right"></i> بازگشت به لیست محصولات
            </a>
        </x-slot>

        <x-dataTable
            :title="'محصولات حذف شده'"
            :url="route('table.products.deleted')"
            :columns="[
                            ['label' => 'شناسه', 'key' => 'id', 'type' => 'text'],
                            ['label' => 'نام محصول', 'key' => 'nameUrl', 'type' => 'text', 'sortable' => true],
                            ['label' => 'قیمت', 'key' => 'price', 'type' => 'text'],
                            ['label' => 'قیمت تخفیف', 'key' => 'discounted_price', 'type' => 'text'],
                            ['label' => 'موجودی', 'key' => 'count', 'type' => 'text'],
                            ['label' => 'تعداد بازدید محصول', 'key' => 'view_count', 'type' => 'text'],
                            ['label' => 'بازدیدها', 'key' => 'visits', 'type' => 'text', 'sortable' => true],
                        ]"
            :items="$products"
            :actions="[
                            ['label' => 'بازیابی', 'type' => 'restore', 'class' => 'btn-success'],
                        ]"
        >
        </x-dataTable>
    </x-page>

@endsection

@push('scripts')
<script>
    // Handle restore action
    $(document).on('click', '[data-action="restore"]', function(e) {
        e.preventDefault();
        const productId = $(this).data('id');
        
        if (confirm('آیا از بازیابی این محصول اطمینان دارید؟')) {
            $.ajax({
                url: '{{ route("products.restore", ":id") }}'.replace(':id', productId),
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        if (typeof window.refreshTable === 'function') {
                            window.refreshTable();
                        } else {
                            location.reload();
                        }
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    alert(response?.message || 'خطا در بازیابی محصول');
                }
            });
        }
    });

    // Handle force delete action
    $(document).on('click', '[data-action="forceDelete"]', function(e) {
        e.preventDefault();
        const productId = $(this).data('id');
        
        if (confirm('آیا از حذف دائمی این محصول اطمینان دارید؟ این عمل قابل بازگشت نیست!')) {
            $.ajax({
                url: '{{ route("products.force-delete", ":id") }}'.replace(':id', productId),
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        if (typeof window.refreshTable === 'function') {
                            window.refreshTable();
                        } else {
                            location.reload();
                        }
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    alert(response?.message || 'خطا در حذف دائمی محصول');
                }
            });
        }
    });
</script>
@endpush

