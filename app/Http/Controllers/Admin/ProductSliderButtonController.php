<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductSlider\StoreProductSliderButtonRequest;
use App\Http\Requests\Admin\ProductSlider\UpdateProductSliderButtonRequest;
use App\Models\Category;
use App\Models\ProductSlider;
use App\Models\ProductSliderButton;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class ProductSliderButtonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ProductSlider $product_slider)
    {
        $product_slider_buttons = $product_slider->buttons()->paginate();
        $categories = Category::query()->select('id','title','parent_id')->orderBy('title')->get();
        return view('admin.product_slider_buttons.index', compact('product_slider', 'product_slider_buttons', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ProductSlider $product_slider)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductSliderButtonRequest $request, ProductSlider $product_slider)
    {
        $product_slider->buttons()->create($request->validated());
        return response()->json();
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductSlider $product_slider, ProductSliderButton $product_slider_button)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductSlider $product_slider, ProductSliderButton $product_slider_button)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductSliderButtonRequest $request, ProductSlider $product_slider, ProductSliderButton $product_slider_button)
    {
        $product_slider_button->update($request->validated());
        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductSlider $product_slider, ProductSliderButton $product_slider_button)
    {
        $product_slider_button->delete();
        return response()->json();
    }


    public function table(ProductSlider $product_slider)
    {
        $buttons = $product_slider->buttons()->paginate();
        $categories = Category::query()->select('id','title','parent_id')->orderBy('title')->get();
        $slotContent = '';

        // Loop through users and render the Blade string for each
        foreach ($buttons as $button) {
            $slotContent .= Blade::render(
                <<<'BLADE'
                <x-modal.destroy id="modal-destroy-{{$product_slider_button->id}}" title="حذف اسلایدر" action="{{route('product_sliders.product_slider_buttons.destroy',['product_slider' => $product_slider->id,'product_slider_button' => $product_slider_button->id])}}" title="{{$product_slider_button->title}}" />

                <x-modal.update id="modal-edit-{{$product_slider_button->id}}" title="ویرایش اسلایدر" action="{{route('product_sliders.product_slider_buttons.update',['product_slider' => $product_slider->id,'product_slider_button' => $product_slider_button->id])}}" >
                    <x-form.input title="عنوان"  name="title" :value="$product_slider_button->title" />
                    
                    <x-query.generator :categories="$categories" targetInputId="query-edit-{{$product_slider_button->id}}" />
                    
                    <x-form.input title="کوئری"  name="query" id="query-edit-{{$product_slider_button->id}}" :value="$product_slider_button->query" />
                </x-modal.update>
            BLADE,
                ['product_slider_button' => $button, 'product_slider' => $product_slider, 'categories' => $categories]
            );
        }

        return view('components.table', [
            'id' => 'product_sliders-table',
            'columns' => [
                ['label' => 'عنوان', 'key' => 'title', 'type' => 'text'],
                ['label' => 'کوئری', 'key' => 'query', 'type' => 'text'],
                ['label' => 'لیست دکمه ها', 'key' => 'buttonsTitle', 'type' => 'route', 'route' => ['product_sliders.product_slider_buttons.index', ['product_slider' => '{id}']],],

            ],
            'url' => route('table.product_slider_buttons',$product_slider->id),
            'items' => $buttons,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
