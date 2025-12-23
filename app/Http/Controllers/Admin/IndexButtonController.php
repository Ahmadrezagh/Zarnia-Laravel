<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexButton\StoreIndexButtonRequest;
use App\Http\Requests\Admin\IndexButton\UpdateIndexButtonRequest;
use App\Models\Category;
use App\Models\IndexButton;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class IndexButtonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $index_buttons = IndexButton::query()->latest()->paginate();
        $categories = Category::query()->select('id','title','parent_id')->orderBy('title')->get();
        return view('admin.index_buttons.index', compact('index_buttons', 'categories'));
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
    public function store(StoreIndexButtonRequest $request)
    {
        IndexButton::query()->create($request->validated());
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
    public function update(UpdateIndexButtonRequest $request, IndexButton $index_button)
    {
        $index_button->update($request->validated());
        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IndexButton $index_button)
    {
        $index_button->delete();
        return response()->json();
    }

    public function table()
    {
        $index_buttons = IndexButton::query()->latest()->paginate();
        $categories = Category::query()->select('id','title','parent_id')->orderBy('title')->get();
        $slotContent = '';

        // Loop through index buttons and render the Blade string for each
        foreach ($index_buttons as $index_button) {
            $slotContent .= Blade::render(
                <<<'BLADE'
             <x-modal.destroy id="modal-destroy-{{$index_button->id}}" title="حذف دکمه" action="{{route('index_buttons.destroy', $index_button->id)}}" title="{{$index_button->title}}" />

                <x-modal.update id="modal-edit-{{$index_button->id}}" title="ویرایش دکمه" action="{{route('index_buttons.update',$index_button->id)}}" >
                     <x-form.input title="عنوان"  name="title" :value="$index_button->title" />
                    
                    <x-query.generator :categories="$categories" targetInputId="query-edit-{{$index_button->id}}" />
                    
                    <x-form.input title="کوئری"  name="query" id="query-edit-{{$index_button->id}}" :value="$index_button->query" />
                </x-modal.update>
            BLADE,
                ['index_button' => $index_button, 'categories' => $categories]
            );
        }

        return view('components.table', [
            'id' => 'index_buttons-table',
            'columns' => [
                ['label' => 'عنوان', 'key' => 'title', 'type' => 'text'],
                ['label' => 'کوئری', 'key' => 'query', 'type' => 'text'],
            ],
            'url' => route('table.index_buttons'),
            'items' => $index_buttons,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}

