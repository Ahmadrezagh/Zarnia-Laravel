<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Models\AttributeGroup;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next){
            if(auth()->user()->isSuperadmin() || (auth()->user()->isAdmin() && auth()->user()->can('category')) ){
                return $next($request);
            }
            else {
                abort(404);
            }
        });
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $attribute_groups = AttributeGroup::query()->latest()->get();
        $categories = Category::query()->paginate();
        return view('admin.categories.index',compact('categories','attribute_groups'));
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
    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        if (empty($validated['slug'])) {
            unset($validated['slug']);
        }

        $category = Category::create($validated);
        if ($request->hasFile('cover_image')) {
            $category->clearMediaCollection('cover_image');
            $category->addMedia($request->file('cover_image'))
                ->toMediaCollection('cover_image');
        }
        $category->attributeGroups()->sync($request->attribute_group_ids);
        $complementary = $request->input('complementary_products', []);
        if (is_string($complementary)) {
            $complementary = json_decode($complementary, true) ?? [];
        }

        $related = $request->input('related_products', []);
        if (is_string($related)) {
            $related = json_decode($related, true) ?? [];
        }

        // Then use the arrays as before
        $category->syncComplementary($complementary);
        $category->syncRelated($related);
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
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());
        if ($request->hasFile('cover_image')) {
            $category->clearMediaCollection('cover_image');
            $category->addMedia($request->file('cover_image'))
                ->toMediaCollection('cover_image');
        }
        $category->attributeGroups()->sync($request->attribute_group_ids);
        $complementary = $request->input('complementary_products', []);
if (is_string($complementary)) {
    $complementary = json_decode($complementary, true) ?? [];
}

$related = $request->input('related_products', []);
if (is_string($related)) {
    $related = json_decode($related, true) ?? [];
}

// Then use the arrays as before
$category->syncComplementary($complementary);
$category->syncRelated($related);
        return response()->json(['message' => 'با موفقیت انجام شد']);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        // Set children's parent_id to null before deleting
        $category->children()->update(['parent_id' => null]);
        
        // Detach products from this category
        $category->products()->detach();
        
        // Detach attribute groups
        $category->attributeGroups()->detach();
        
        // Delete polymorphic pivot table entries (complementary_products and related_products)
        DB::table('complementary_products')
            ->where('source_type', Category::class)
            ->where('source_id', $category->id)
            ->delete();
        
        DB::table('related_products')
            ->where('source_type', Category::class)
            ->where('source_id', $category->id)
            ->delete();
        
        // Delete the category
        $category->delete();
        
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }


    public function table()
    {
        $categories = Category::query()->paginate();

        $attribute_groups = AttributeGroup::query()->latest()->get();
        // Initialize slot content
        $slotContent = '';

        // Loop through users and render the Blade string for each
        foreach ($categories as $category) {
            $slotContent .= Blade::render(
                <<<'BLADE'
                 <!-- Modal -->
                <x-modal.destroy id="modal-destroy-{{$category->id}}" title="حذف دسته بندی" action="{{route('categories.destroy', $category->slug)}}" title="{{$category->title}}" />

                <x-modal.update id="modal-edit-{{$category->id}}" title="ساخت دسته بندی" action="{{route('categories.update',$category->slug)}}" >
                    <x-form.input title="نام"  name="title" :value="$category->title" />
                    <div class="form-group">
                        <label for="category-slug-edit-{{$category->id}}">اسلاگ</label>
                        <input type="text" id="category-slug-edit-{{$category->id}}" name="slug" class="form-control" dir="ltr" value="{{$category->slug}}" required>
                    </div>
                    <x-form.select-option title="دسته بندی والد" name="parent_id" >
                        @foreach($categories as $parent_category)
                            @if( ($parent_category->id != $category->id) && (!$category->isParentOfCategory($parent_category) ))
                                <option value="{{$category->id}}" @if($category->parent_id == $parent_category->id) selected @endif >{{$parent_category->title}}</option>
                            @endif
                        @endforeach
                    </x-form.select-option>
                    <x-form.select-option title="گروه ویژگی" name="attribute_group_ids[]" multiple="true" >
                        @foreach($attribute_groups as $attribute_group)
                            <option value="{{ $attribute_group->id }}" @if($category->attributeGroups()->where('attribute_group_id','=',$attribute_group->id)->exists()) selected @endif >{{ $attribute_group->name }}</option>
                        @endforeach
                    </x-form.select-option>
                </x-modal.update>
            BLADE,
                ['category' => $category, 'categories' => $categories,'attribute_groups' => $attribute_groups]
            );
        }
        return view('components.table', [
            'id' => 'categories-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
            ],
            'url' => route('table.categories'),
            'items' => $categories,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }

    public function getComplementaryProducts(Category $category)
    {
        $products = $category->complementaryProducts()->get(['id', 'name']);

        $results = $products->map(function($product) {
            return [
                'id' => "Product:{$product->id}",
                'text' => $product->name,
            ];
        });

        return response()->json($results);
    }

    public function getRelatedProducts(Category $category)
    {
        $products = $category->relatedProducts()->get(['id', 'name']);

        $results = $products->map(function($product) {
            return [
                'id' => "Product:{$product->id}",
                'text' => $product->name,
            ];
        });

        return response()->json($results);
    }

}
