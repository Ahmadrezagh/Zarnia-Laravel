<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductSlider\StoreProductSliderRequest;
use App\Http\Requests\Admin\ProductSlider\UpdateProductSliderRequest;
use App\Models\ProductSlider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class ProductSliderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $product_sliders = ProductSlider::query()->latest()->paginate();
        return view('admin.product_sliders.index', compact('product_sliders'));
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
    public function store(StoreProductSliderRequest $request)
    {
        ProductSlider::query()->create($request->validated());
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
    public function update(UpdateProductSliderRequest $request, ProductSlider $product_slider)
    {
        $product_slider->update($request->validated());
        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductSlider $product_slider)
    {
        $product_slider->delete();
        return response()->json();
    }


    public function table()
    {
        $product_sliders = ProductSlider::query()->paginate();


        // Loop through users and render the Blade string for each
        foreach ($product_sliders as $product_slider) {
            $slotContent .= Blade::render(
                <<<'BLADE'
             <x-modal.destroy id="modal-destroy-{{$product_slider->id}}" title="حذف نقش" action="{{route('product_sliders.destroy', $product_slider->id)}}" title="{{$product_slider->title}}" />

                <x-modal.update id="modal-edit-{{$product_slider->id}}" title="ویرایش اسلایدر" action="{{route('product_sliders.update',$product_slider->id)}}" >
                     <x-form.input title="عنوان"  name="title" :value="$product_slider->title" />
                    <x-form.input title="کوئری"  name="query" :value="$product_slider->query" />
                    <x-form.input title="قبل از دسته بندی اسلایدر"  name="before_category_slider" type="number" :value="$product_slider->before_category_slider ?? 0" />
                    <x-form.input title="بعد از دسته بندی اسلایدر"  name="after_category_slider" type="number" :value="$product_slider->after_category_slider ?? 0" />
                </x-modal.update>
            BLADE,
                ['product_slider' => $product_slider, 'permissions']
            );
        }

        return view('components.table', [
            'id' => 'product_sliders-table',
            'columns' => [
                ['label' => 'عنوان', 'key' => 'title', 'type' => 'text'],
                ['label' => 'کوئری', 'key' => 'query', 'type' => 'text'],
                ['label' => 'لیست دکمه ها', 'key' => 'buttonsTitle', 'type' => 'route', 'route' => ['product_sliders.product_slider_buttons.index', ['product_slider' => '{id}']],],

            ],
            'url' => route('table.product_sliders'),
            'items' => $product_sliders,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
