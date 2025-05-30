<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FooterTitleLink\StoreFooterTitleLinkRequest;
use App\Http\Requests\Admin\FooterTitleLink\UpdateFooterTitleLinkRequest;
use App\Models\FooterTitle;
use App\Models\FooterTitleLink;
use App\Models\HeaderLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class FooterTitleLinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FooterTitle $footer_title)
    {
        $footer_title_links = $footer_title->links()->paginate();
        return view('admin.footer_title_links.index', compact('footer_title', 'footer_title_links'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(FooterTitle $footer_title)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFooterTitleLinkRequest $request, FooterTitle $footer_title)
    {
        $footer_title->links()->create($request->validated());
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }

    /**
     * Display the specified resource.
     */
    public function show(FooterTitle $footer_title, FooterTitleLink $footer_title_link)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FooterTitle $footer_title, FooterTitleLink $footer_title_link)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFooterTitleLinkRequest $request, FooterTitle $footer_title, FooterTitleLink $footer_title_link)
    {
        $footer_title_link->update($request->validated());
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FooterTitle $footer_title, FooterTitleLink $footer_title_link)
    {
        $footer_title_link->delete();
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }


    public function table(FooterTitle $footer_title)
    {
        $footer_title_links = $footer_title->links()->paginate();

        // Initialize slot content
        $slotContent = '';

        // Loop through users and render the Blade string for each
        foreach ($footer_title_links as $footer_title_link) {
            $slotContent .= Blade::render(
                <<<'BLADE'
                 <!-- Modal -->
                <x-modal.destroy id="modal-destroy-{{$footer_title_link->id}}" title="حذف لینک" action="{{route('footer_title.footer_title_links.destroy', ['footer_title' => $footer_title->id,'footer_title_link' => $footer_title_link->id])}}" title="{{$footer_title_link->title}}" />
                <x-modal.update id="modal-edit-{{$footer_title_link->id}}" title="ویرایش لینک" action="{{route('footer_title.footer_title_links.update',['footer_title' => $footer_title->id,'footer_title_link' => $footer_title_link->id])}}" class="modal-lg" >
                    <x-form.input title="عنوان"  name="title" value="{{$footer_title_link->title}}" />
                    <x-form.input title="لینک"  name="url" value="{{$footer_title_link->url}}" />
                </x-modal.update>
            BLADE,
                ['footer_title' => $footer_title,'footer_title_link' => $footer_title_link, 'footer_title_links' => $footer_title_links]
            );
        }
        return view('components.table', [
            'id' => 'footer-title-links-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
                ['label' => 'لینک', 'key' => 'url', 'type' => 'copiableText'],
            ],
            'url' => route('table.footer_title_links',$footer_title->id),
            'items' => $footer_title_links,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
