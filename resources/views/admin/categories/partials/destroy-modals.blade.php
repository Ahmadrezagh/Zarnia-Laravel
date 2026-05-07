@foreach($categories as $category)
    <x-modal.destroy
        id="modal-destroy-{{$category->id}}"
        title="حذف دسته بندی"
        action="{{ route('categories.destroy', $category->slug) }}"
        title="{{ $category->title }}" />
@endforeach

