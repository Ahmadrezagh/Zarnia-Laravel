<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FooterTitle\StoreFooterTitleRequest;
use App\Http\Requests\Admin\FooterTitle\UpdateFooterTitleRequest;
use App\Models\FooterTitle;
use App\Models\HeaderLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class FooterTitleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $footer_titles = FooterTitle::query()->paginate();
        return view('admin.footer_titles.index', compact('footer_titles'));
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
    public function store(StoreFooterTitleRequest $request)
    {
        if(FooterTitle::query()->count() >= 3){
            toastr()->warning('ساخت بیش از ستون برای فوتر مجاز نیست');
            return back();
        }
        FooterTitle::create($request->validated());
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
    public function update(UpdateFooterTitleRequest $request, FooterTitle $footer_title)
    {
        $footer_title->update($request->validated());
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FooterTitle $footer_title)
    {
        $footer_title->delete();
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }


    public function table()
    {
        $footer_titles = FooterTitle::query()->paginate();

        // Initialize slot content
        $slotContent = '';

        // Loop through users and render the Blade string for each
        foreach ($footer_titles as $footer_title) {
            $slotContent .= Blade::render(
                <<<'BLADE'
                 <!-- Modal -->
                <x-modal.destroy id="modal-destroy-{{$footer_title->id}}" title="حذف ستون فوتر" action="{{route('footer_titles.destroy', $footer_title->id)}}" title="{{$footer_title->title}}" />
                <x-modal.update id="modal-edit-{{$footer_title->id}}" title="ویرایش ستون فوتر" action="{{route('footer_titles.update',$footer_title->id)}}" class="modal-lg" >
                    <x-form.input title="عنوان"  name="title" value="{{$footer_title->title}}" />
                </x-modal.update>
            BLADE,
                ['footer_title' => $footer_title, 'footer_titles' => $footer_titles]
            );
        }
        return view('components.table', [
            'id' => 'footer-titles-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
            ],
            'url' => route('table.footer_titles'),
            'items' => $footer_titles,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
