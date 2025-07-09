<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HeaderLinks\StoreHeaderLinkRequest;
use App\Http\Requests\Admin\HeaderLinks\UpdateHeaderLinkRequest;
use App\Models\HeaderLink;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;


class HeaderLinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $header_links = HeaderLink::query()->paginate();
        return view('admin.header_links.index', compact('header_links'));
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
    public function store(StoreHeaderLinkRequest $request)
    {
        HeaderLink::create($request->validated());
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
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
    public function update(UpdateHeaderLinkRequest $request, HeaderLink $header_link)
    {
        $header_link->update($request->validated());
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HeaderLink $header_link)
    {
        $header_link->delete();
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }


    public function table()
    {
        $header_links = HeaderLink::query()->paginate();

        // Initialize slot content
        $slotContent = '';

        // Loop through users and render the Blade string for each
        foreach ($header_links as $header_link) {
            $slotContent .= Blade::render(
                <<<'BLADE'
                 <!-- Modal -->
                <x-modal.destroy id="modal-destroy-{{$header_link->id}}" title="حذف لینک" action="{{route('header_links.destroy', $header_link->id)}}" title="{{$header_link->title}}" />
                <x-modal.update id="modal-edit-{{$header_link->id}}" title="ویرایش لینک" action="{{route('header_links.update',$header_link->id)}}" class="modal-lg" >
                    <x-form.input title="عنوان"  name="title" value="{{$header_link->title}}" />
                    <x-form.input title="لینک"  name="url" value="{{$header_link->url}}" />
                </x-modal.update>
            BLADE,
                ['header_link' => $header_link, 'header_links' => $header_links]
            );
        }
        return view('components.table', [
            'id' => 'header-links-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
                ['label' => 'لینک', 'key' => 'url', 'type' => 'copiableText'],
            ],
            'url' => route('table.header_links'),
            'items' => $header_links,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
