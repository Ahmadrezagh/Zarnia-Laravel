<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\storeComprehensiveProductRequest;
use App\Http\Requests\Admin\Product\updateComprehensiveProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Http\Resources\Admin\Product\EditProductResource;
use App\Http\Resources\Admin\Table\AdminProductResource;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\ComprehensiveProduct;
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
    public function notAvailable()
    {
        $products = Product::query()->paginate();
        $categories = Category::query()->get();
        return view('admin.products.index_not_available', compact('products','categories'));
    }
    public function withoutCategory()
    {
        $products = Product::query()->paginate();
        $categories = Category::query()->get();
        return view('admin.products.index_without_categoy', compact('products','categories'));
    }
    public function productsComprehensive()
    {
        $products = Product::query()->paginate();
        $categories = Category::query()->get();
        return view('admin.products.index_comprehensive', compact('products','categories'));
    }
    public function productsChildrenOf(Product $product)
    {

        $products = Product::query()->childrenOf($product->id)->orWhere('id','=',$product->id)->paginate();
        $categories = Category::query()->get();
        return view('admin.products.products_children_of', compact('products','categories','product'));
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
        if ($request->has('attributes')) {
            // Get existing attribute values for this product and group
            $existingAttributeIds = AttributeValue::where('product_id', $product->id)
                ->pluck('attribute_id')
                ->toArray();

            $newAttributeIds = [];

            foreach ($request->get('attributes') as  $attr) {

                if (!empty($attr['value'])) {
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
        $query = Product::query()->available()->main()->select('*'); // Assuming your model is Product

        // Get total records before applying filters
        $totalRecords = $query->count();

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('etikets', function ($q2) use ($search) {
                        $q2->where('code', '=', "{$search}"); // Search in current product's etiket code
                    })
                    ->orWhereHas('children.etikets', function ($q2) use ($search) {
                        $q2->where('code', '=', "{$search}"); // Search in children's etiket code
                    });
            });
        }


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

            ->multipleSearch([$request->searchKey,$request->searchVal])
            ->WithMojoodCount($count_dir)
            ->WithImageStatus($image_dir)
            ->SortMojood($is_mojood_dir)
            ->FilterProduct($request->filter)
            ->categories($request->category_ids);


        // Get filtered records count after search
        $filteredRecords = $data->count();

        $data = $data->skip($start)
        ->take($length)
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
    public function products_children_of_table(Request $request,Product $product)
    {
        $query = Product::query()->childrenOf($product->id)->orWhere('id','=',$product->id)->select('*'); // Assuming your model is Product
//        return $query->get();
        // Get total records before applying filters
        $totalRecords = $query->count();

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('etikets', function ($q2) use ($search) {
                        $q2->where('code', '=', "{$search}"); // Search in current product's etiket code
                    })
                    ->orWhereHas('children.etikets', function ($q2) use ($search) {
                        $q2->where('code', '=', "{$search}"); // Search in children's etiket code
                    });
            });
        }


        // Handle pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10); // Default to 10 if length is missing or invalid
        if ($length <= 0) {
            $length = 10; // Ensure length is positive to avoid SQL error
        }

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
        $data = $query;


        // Get filtered records count after search
        $filteredRecords = $data->count();

        $data = $data->skip($start)
        ->take($length)
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
    public function not_available_table(Request $request)
    {
        $query = Product::query()->notAvailable()->main()->select('*'); // Assuming your model is Product

        // Get total records before applying filters
        $totalRecords = $query->count();

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('etikets', function ($q2) use ($search) {
                        $q2->where('code', '=', "{$search}"); // Search in current product's etiket code
                    })
                    ->orWhereHas('children.etikets', function ($q2) use ($search) {
                        $q2->where('code', '=', "{$search}"); // Search in children's etiket code
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
    public function products_without_category_table(Request $request)
    {
        $query = Product::query()->wihtoutCategory()->main()->select('*'); // Assuming your model is Product

        // Get total records before applying filters
        $totalRecords = $query->count();

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('etikets', function ($q2) use ($search) {
                        $q2->where('code', '=', "{$search}"); // Search in current product's etiket code
                    })
                    ->orWhereHas('children.etikets', function ($q2) use ($search) {
                        $q2->where('code', '=', "{$search}"); // Search in children's etiket code
                    });
            });
        }


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

            ->multipleSearch([$request->searchKey,$request->searchVal])
            ->WithMojoodCount($count_dir)
            ->WithImageStatus($image_dir)
            ->SortMojood($is_mojood_dir)
            ->FilterProduct($request->filter)
            ->categories($request->category_ids);



        // Get filtered records count after search
        $filteredRecords = $query->count();
        $data = $data
            ->skip($start)
            ->take($length)
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
    public function products_comprehensive_table(Request $request)
    {
        $query = Product::query()->comprehensive()->main()->select('*'); // Assuming your model is Product

        // Get total records before applying filters
        $totalRecords = $query->count();

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('etikets', function ($q2) use ($search) {
                        $q2->where('code', '=', "{$search}"); // Search in current product's etiket code
                    })
                    ->orWhereHas('children.etikets', function ($q2) use ($search) {
                        $q2->where('code', '=', "{$search}"); // Search in children's etiket code
                    });
            });
        }


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

            ->multipleSearch([$request->searchKey,$request->searchVal])
            ->WithMojoodCount($count_dir)
            ->WithImageStatus($image_dir)
            ->SortMojood($is_mojood_dir)
            ->FilterProduct($request->filter)
            ->categories($request->category_ids);

        // Get filtered records count after search
        $filteredRecords = $query->count();
        $data = $data
            ->skip($start)
            ->take($length)
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

    public function storeComprehensiveProduct(storeComprehensiveProductRequest $request)
    {
        $validated = $request->validated();
        $validated['is_comprehensive'] = 1;
        $validated['weight'] = 0;

        $validated['price'] = 0;
        foreach ($request->product_ids as $productId) {
            $pr = Product::find($productId);
            if($pr){
                $validated['price'] = $validated['price'] + ( $pr->price * 10 );
                $validated['weight'] = $validated['weight'] + $pr->weight;
            }
        }
        $product = Product::create($validated);
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

        foreach ($request->product_ids as $productId) {
            ComprehensiveProduct::create([
                'comprehensive_product_id' => $product->id,
                'product_id' => $productId,
            ]);
        }
        return back();
    }
    public function updateComprehensiveProduct(updateComprehensiveProductRequest $request)
    {

    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $products = Product::where('name', 'LIKE', "%{$query}%")
            ->limit(20)
            ->get(['id', 'name']);

        $results = $products->map(function($product) {
            return [
                'id' => "Product:{$product->id}",
                'text' => $product->name,
            ];
        });

        return response()->json($results);
    }


    public function ajaxSearch(Request $request)
    {

        $query = $request->input('q');

        // Search products
        $products = Product::where('name', 'LIKE', "%{$query}%")
            ->select('id', 'name as text')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => "Product:{$product->id}",
                    'text' => $product->text
                ];
            });

        // Search categories
        $categories = Category::where('title', 'LIKE', "%{$query}%")
            ->select('id', 'title as text')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => "Category:{$category->id}",
                    'text' => $category->text
                ];
            });
        // Combine results
        $results = $products->concat($categories);

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => false]
        ]);
    }
}
