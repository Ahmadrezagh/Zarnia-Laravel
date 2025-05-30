<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pages\StorePageRequest;
use App\Http\Requests\Admin\Pages\UpdatePageRequest;
use App\Models\Category;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;


class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $pages = Page::query()->paginate();
        return view('admin.pages.index',compact('pages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePageRequest $request)
    {
        Page::create($request->validated());
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Page $page)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePageRequest $request, Page $page)
    {
        $page->update($request->validated());
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        $page->delete();
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }


    public function table()
    {
        $pages = Page::query()->paginate();

        // Initialize slot content
        $slotContent = '';

        // Loop through users and render the Blade string for each
        foreach ($pages as $page) {
            $slotContent .= Blade::render(
                <<<'BLADE'
                <x-modal.destroy id="modal-destroy-{{$page->id}}" title="حذف صفحه" action="{{route('pages.destroy', $page->slug)}}" title="{{$page->title}}" />
                <x-modal.update id="modal-edit-{{$page->id}}" title="ویرایش صفحه" action="{{route('pages.update',$page->slug)}}" class="modal-lg" >
                    <x-form.input title="عنوان"  name="title" value="{{$page->title}}" />
                    <x-form.textarea name="content" title="محتوا" :value="$page->content" />
                    <x-form.select-option title="منتشر شود" name="published" >
                        <option value="0" @if($page->published == 0) selected @endif >خیر</option>
                        <option value="1" @if($page->published == 1) selected @endif >بله</option>
                    </x-form.select-option>
                </x-modal.update>
            BLADE,
                ['page' => $page, 'pages' => $pages]
            );
        }
        return view('components.table', [
            'id' => 'pages-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
                ['label' => 'لینک', 'key' => 'url', 'type' => 'copiableText'],
                [
                    'label' => 'وضعیت', 'key' => 'published',
                    'type' => 'binaryCondition',
                    'texts'=>['true' => 'منتشر شده', 'false' => 'منتشر نشده']
                ],
            ],
            'url' => route('table.pages'),
            'items' => $pages,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
