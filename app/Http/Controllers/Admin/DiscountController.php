<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDiscountCodeRequest;
use App\Http\Requests\Admin\UpdateDiscountCodeRequest;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\QA;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

class DiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $discounts = Discount::query()
            ->with(['users', 'products', 'categories'])
            ->latest()
            ->paginate();
        $users = User::all();
        $products = Product::main()->get();
        $categories = Category::all();
        
        return view('admin.discounts.index', compact('discounts', 'users', 'products', 'categories'));
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
    public function store(StoreDiscountCodeRequest $request)
    {
        $validated = $request->validated();
        if (empty($validated['code'])) {
            $validated['code'] = Discount::generateUniqueCode();
        }
        
        // Create discount first
        $discount = Discount::query()->create($validated);
        
        // Attach users, products, and categories
        $this->syncDiscountables($discount, $request);
        
        // Auto-generate description if not provided
        if (empty($discount->description)) {
            $discount->load(['users', 'products', 'categories']);
            $discount->update(['description' => $discount->summary_description]);
        }
        
        return response()->json($discount);
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
    public function update(UpdateDiscountCodeRequest $request, Discount $discount)
    {
        $validated = $request->validated();
        
        // Update discount
        $discount->update($validated);
        
        // Sync users, products, and categories
        $this->syncDiscountables($discount, $request);
        
        // Auto-generate description if not provided
        if (empty($discount->description)) {
            $discount->load(['users', 'products', 'categories']);
            $discount->update(['description' => $discount->summary_description]);
        }
        
        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Discount $discount)
    {
        $discount->delete();
        return response()->json();
    }


    public function table()
    {
        $qas = QA::query()->paginate();


        // Loop through users and render the Blade string for each
        foreach ($qas as $qa) {
            $slotContent .= Blade::render(
                <<<'BLADE'
             <x-modal.destroy id="modal-destroy-{{$qa->id}}" title="حذف نقش" action="{{route('qas.destroy', $qa->id)}}" title="{{$qa->title}}" />

                <x-modal.update id="modal-edit-{{$qa->id}}" title="ویرایش نقش" action="{{route('qas.update',$qa->id)}}" >
                     <x-form.input title="سوال"  name="question" :value="$qa->question" />
                    <x-form.input title="جواب"  name="answer" :value="$qa->answer" />
                </x-modal.update>
            BLADE,
                ['qa' => $qa, 'permissions']
            );
        }

        return view('components.table', [
            'id' => 'qas-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
            ],
            'url' => route('table.qas'),
            'items' => $qas,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }

    /**
     * Sync discountable relationships (users, products, categories)
     */
    private function syncDiscountables(Discount $discount, Request $request)
    {
        // Sync users
        if ($request->has('user_ids')) {
            $discount->users()->sync($request->user_ids ?? []);
        }
        
        // Sync products
        if ($request->has('product_ids')) {
            $discount->products()->sync($request->product_ids ?? []);
        }
        
        // Sync categories
        if ($request->has('category_ids')) {
            $discount->categories()->sync($request->category_ids ?? []);
        }
    }
}
