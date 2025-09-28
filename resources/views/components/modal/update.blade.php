@props([
'id' => $id,
'title' => $title,
'action' => $action,
'confirmation' => ''
])
<x-modal.modal id="{{$id}}" title="{{$title}}">
    <form action="{{$action}}" method="post" enctype="multipart/form-data" class="ajax-form"
          data-id="{{ $id }}"
          data-method="PUT" >
        <div class="modal-body">
            @csrf
            @method('PUT')

            {{ $slot }}

        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary" @if($confirmation == 'true')  onclick="return confirmation()" @endif >ویرایش</button>
        </div>
    </form>
</x-modal.modal>
