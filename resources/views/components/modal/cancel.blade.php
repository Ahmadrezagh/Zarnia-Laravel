@props([
'id' => $id,
'title' => $title,
'action' => $action,
])
<x-modal.modal id="{{$id}}" title="{{$title}}">
    <form action="{{$action}}" method="GET" class="ajax-form" data-method="GET" >
        <div class="modal-body">
            @csrf
            <h5>
                آیا در لغو '<b>{{$title}}</b>' مطمئن هستید؟</h5>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">حذف</button>
        </div>
    </form>
</x-modal.modal>
