<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexBannder\StoreIndexBannerRequest;
use App\Http\Requests\Admin\IndexBannder\UpdateIndexBannerRequest;
use App\Models\IndexBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class IndexBannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $index_banners = IndexBanner::query()->latest()->paginate();
        return view('admin.index_banners.index', compact('index_banners'));
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
    public function store(Request $request)
    {
        return $request->file('cover_image');
        $banner = IndexBanner::query()->create($request->validated());
        if ($request->hasFile('cover_image')) {
            $banner->clearMediaCollection('cover_image');
            $banner->addMedia($request->file('cover_image'))
                ->toMediaCollection('cover_image');
        }
        return response()->json();
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
    public function update(UpdateIndexBannerRequest $request, IndexBanner $index_banner)
    {
        $index_banner->update($request->validated());
        if ($request->hasFile('cover_image')) {
            $index_banner->clearMediaCollection('cover_image');
            $index_banner->addMedia($request->file('cover_image'))
                ->toMediaCollection('cover_image');
        }
        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IndexBanner $index_banner)
    {
        $index_banner->delete();
        return response()->json();
    }


    public function table()
    {
        $index_banners = IndexBanner::query()->latest()->paginate();


        // Loop through users and render the Blade string for each
        foreach ($index_banners as $index_banner) {
            $slotContent .= Blade::render(
                <<<'BLADE'
             <x-modal.destroy id="modal-destroy-{{$index_banner->id}}" title="حذف نقش" action="{{route('index_banners.destroy', $index_banner->id)}}" title="{{$index_banner->title}}" />

                <x-modal.update id="modal-edit-{{$index_banner->id}}" title="ویرایش نقش" action="{{route('index_banners.update',$index_banner->id)}}" >
                     <x-form.input title="سوال"  name="question" :value="$index_banner->question" />
                    <x-form.input title="جواب"  name="answer" :value="$index_banner->answer" />
                </x-modal.update>
            BLADE,
                ['index_banner' => $index_banner, 'permissions']
            );
        }

        return view('components.table', [
            'id' => 'index_banners-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
            ],
            'url' => route('table.index_banners'),
            'items' => $index_banners,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
