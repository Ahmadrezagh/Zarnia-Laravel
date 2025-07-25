<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Http\Resources\Admin\Product\EditProductResource;
use App\Http\Resources\Admin\Table\AdminProductResource;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::query()->paginate();
        $categories = Category::query()->get();
        return view('admin.products.index', compact('products','categories'));
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::query()->findOrFail($id);
        if($product){
            return EditProductResource::make($product);
        }
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
    public function update(UpdateProductRequest $request, string $id)
    {
        $product = Product::query()->findOrFail($id);
        $validated = $request->validated();
        $validated['discount_percentage'] = $request->input('discount_percentage') ?? 0;
        $product->update($validated);

        // Handle cover image
        if ($request->hasFile('cover_image')) {
            $product->clearMediaCollection('cover_image');
            $product->addMedia($request->file('cover_image'))
                ->toMediaCollection('cover_image');
        }

        // Handle gallery images
        $existingGallery = $request->existing_gallery ? json_decode($request->existing_gallery, true) : [];
        // Clear gallery collection except for retained images
        $currentMedia = $product->getMedia('gallery');
        foreach ($currentMedia as $media) {
            if (!in_array($media->getUrl(), $existingGallery)) {
                $media->delete();
            }
        }

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $image) {
                $product->addMedia($image)
                    ->toMediaCollection('gallery');
            }
        }

        // Handle categories
        $product->categories()->sync($request->category_ids);

        // Handle attribute group and attributes
        if ($request->has('attribute_group') && $request->has('attributes')) {
            $group = AttributeGroup::firstOrCreate(
                ['name' => $request->attribute_group],
                ['name' => $request->attribute_group]
            );
            $product->update(['attribute_group_id' => $group->id]);
            // Get existing attribute values for this product and group
            $existingAttributeIds = AttributeValue::where('product_id', $product->id)
                ->whereIn('attribute_id', Attribute::where('attribute_group_id', $group->id)->pluck('id'))
                ->pluck('attribute_id')
                ->toArray();

            $newAttributeIds = [];
            foreach ($request['attributes'] as $index => $attr) {

                if (!empty($attr['name']) && !empty($attr['value'])) {
                    if (isset($attr['attribute_id']) && $attr['attribute_id']) {
                        // Update existing attribute value
                        AttributeValue::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'attribute_id' => $attr['attribute_id']
                            ],
                            ['value' => $attr['value']]
                        );
                        $newAttributeIds[] = $attr['attribute_id'];
                    } else {
                        // Create new attribute and value
                        $attribute = Attribute::create([
                            'attribute_group_id' => $group->id,
                            'name' => $attr['name']
                        ]);
                        AttributeValue::create([
                            'product_id' => $product->id,
                            'attribute_id' => $attribute->id,
                            'value' => $attr['value']
                        ]);
                        $newAttributeIds[] = $attribute->id;
                    }
                }
            }

            // Delete attribute values that are no longer in the request
            $attributesToDelete = array_diff($existingAttributeIds, $newAttributeIds);
            if (!empty($attributesToDelete)) {
                AttributeValue::where('product_id', $product->id)
                    ->whereIn('attribute_id', $attributesToDelete)
                    ->delete();
            }
        }

        return response()->json($product);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function table(Request $request)
    {
        $query = Product::query()->main()->select('*'); // Assuming your model is Product

        // Get total records before applying filters
        $totalRecords = $query->count();

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhereHas('etikets', function ($q2) use ($search) {
                    $q2->where('code', '=', "{$search}"); // Search in etiket code
                });
            });
        }

        // Get filtered records count after search
        $filteredRecords = $query->count();

        // Handle pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10); // Default to 10 if length is missing or invalid
        if ($length <= 0) {
            $length = 10; // Ensure length is positive to avoid SQL error
        }

        $is_mojood_dir = 'desc';
        $image_dir = null;
        $count_dir = null;
        // Apply sorting if provided
        if ($request->has('order') && !empty($request->input('order'))) {
            $order = $request->input('order')[0];
            $columnIndex = $order['column'];
            $direction = $order['dir'] === 'asc' ? 'asc' : 'desc';
            $column = $request->input("columns.{$columnIndex}.data");
            if($columnIndex == 1){
                $is_mojood_dir = $direction;
            }
            if($columnIndex == 2){
                $image_dir = $direction;
            }
            if($columnIndex == 6){
                $count_dir = $direction;
            }
            if ($column && Schema::hasColumn('products', $column)) {
                $query->orderBy($column, $direction);
            }
        }else{
            $query = $query->latest('id');
        }

        // Fetch paginated data
        $data = $query
            ->skip($start)
            ->take($length)

            ->multipleSearch([$request->searchKey,$request->searchVal])
            ->WithMojoodCount($count_dir)
            ->WithImageStatus($image_dir)
            ->SortMojood($is_mojood_dir)
            ->FilterProduct($request->filter)
            ->categories($request->category_ids)
            ->get()
            ->map(function ($item) {
                return AdminProductResource::make($item); // Ensure all necessary fields are included
            });
        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }






    public function bulkUpdate(Request $request)
    {
        \Log::info('Uploaded files:', $_FILES);

        if (is_string($request->product_ids)) {
            $productIds = json_decode($request->product_ids, true);
            $request->merge(['product_ids' => $productIds]);
        }

        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'cover_image' => 'nullable|file|image|max:2048',
        ]);

        $products = Product::whereIn('id', $request->product_ids)->get();

        // Store the uploaded file in Laravel storage
        $tempFilePath = null;
        $originalPath = null;
        if ($request->hasFile('cover_image')) {
            $tempFilePath = $request->file('cover_image')->store('temp', 'public');
            $originalPath = storage_path('app/public/' . $tempFilePath);
            \Log::info('Temporary file stored at: ' . $originalPath);
            if (!file_exists($originalPath)) {
                \Log::error('Temporary file does not exist: ' . $originalPath);
                return response()->json(['status' => 'error', 'message' => 'Stored file not found'], 500);
            }
        }

        foreach ($products as $product) {
            if ($request->filled('discount_percentage')) {
                $product->update([
                    'discount_percentage' => $request->discount_percentage
                ]);
            }

            if ($request->filled('category_ids')) {
                $product->categories()->sync($request->category_ids);
            }

            if ($tempFilePath) {
                // Create a unique copy for this product
                $fileName = basename($tempFilePath);
                $copyPath = storage_path('app/public/temp/' . $product->id . '_' . $fileName);
                if (!copy($originalPath, $copyPath)) {
                    \Log::error('Failed to copy file to: ' . $copyPath);
                    return response()->json(['status' => 'error', 'message' => 'File copy failed'], 500);
                }
                \Log::info('Processing cover_image for product ID: ' . $product->id . ' using file: ' . $copyPath);

                try {
//                    $product->clearMediaCollection('cover_image');
                    $product->addMedia($copyPath)
                        ->toMediaCollection('gallery');
                    // Delete the copied file
                    \Storage::disk('public')->delete('temp/' . $product->id . '_' . $fileName);
                    \Log::info('Copied file deleted: ' . 'temp/' . $product->id . '_' . $fileName);
                } catch (\Exception $e) {
                    \Log::error('Error processing cover_image for product ID ' . $product->id . ': ' . $e->getMessage());
                    return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }
            }
        }

        // Clean up original temporary file
        if ($tempFilePath) {
            \Storage::disk('public')->delete($tempFilePath);
            \Log::info('Original temporary file deleted: ' . $tempFilePath);
        }

        return response()->json(['status' => 'success']);
    }


    public function assignCategory(Request $request)
    {
        if (is_string($request->product_ids)) {
            $productIds = json_decode($request->product_ids, true);
            $request->merge(['product_ids' => $productIds]);
        }
        $fromPrice = $request->from_price ?? null;
        $toPrice = $request->to_price ?? null;
        $category_ids = $request->category_ids ?? null;
        if($category_ids && ($fromPrice || $toPrice)){
            $query = Product::query();
            if($fromPrice){
                $fromPrice = $fromPrice * 10;
                $query = $query->where('price' , '>=', $fromPrice);
            }
            if($toPrice){
                $toPrice = $toPrice * 10;
                $query = $query->where('price' , '<=', $toPrice);
            }
            $products = $query->get();
            foreach ($products as $product) {
                $product->categories()->sync($category_ids);
            }
        }
        return response()->json($products);
    }
}
