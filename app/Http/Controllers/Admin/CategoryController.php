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
        $attribute_groups = AttributeGroup::query()->orderBy('name')->get(['id','name']);
        $categories = Category::query()
            ->select('id','title','slug','parent_id','show_in_nav')
            ->latest()
            ->paginate(15);

        $allCategories = Category::query()
            ->select('id','title','parent_id')
            ->orderBy('title')
            ->get();

        return view('admin.categories.index', compact('categories', 'attribute_groups', 'allCategories'));
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

        // Handle checkbox - boolean() returns false if not present
        $validated['show_in_nav'] = $request->boolean('show_in_nav');

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
    public function edit(Category $category)
    {
        $attribute_groups = AttributeGroup::query()->orderBy('name')->get(['id','name']);
        $allCategories = Category::query()
            ->select('id','title','parent_id')
            ->orderBy('title')
            ->get();

        $category->load([
            'attributeGroups:id',
            'relatedProducts:id,name',
            'complementaryProducts:id,name',
            'parent:id,title'
        ]);

        return view('admin.categories.edit', compact('category','attribute_groups','allCategories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validated = $request->validated();
        
        // Handle checkbox - boolean() returns false if not present
        $validated['show_in_nav'] = $request->boolean('show_in_nav');
        
        $category->update($validated);
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

        $category->syncComplementary($complementary);
        $category->syncRelated($related);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => 'با موفقیت انجام شد']);
        }

        return redirect()->route('categories.index')->with('success', 'دسته بندی با موفقیت به‌روزرسانی شد');

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
        $categories = Category::query()
            ->select('id','title','slug')
            ->latest()
            ->paginate(15);

        $slotContent = view('admin.categories.partials.destroy-modals', compact('categories'))->render();

        return view('components.table', [
            'id' => 'categories-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'title', 'type' => 'text', 'url' => setting('url') . '/product-category/{slug}'],
            ],
            'url' => route('table.categories'),
            'items' => $categories,
            'actions' => [
                ['label' => 'ویرایش', 'route' => ['categories.edit', ['category' => '{slug}']]],
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
