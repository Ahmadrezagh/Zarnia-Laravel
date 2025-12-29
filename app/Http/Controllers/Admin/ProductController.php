<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\storeComprehensiveProductRequest;
use App\Http\Requests\Admin\Product\updateComprehensiveProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Resources\Admin\Product\EditProductResource;
use App\Http\Resources\Admin\Table\AdminProductResource;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\ComprehensiveProduct;
use App\Models\Etiket;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;

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
    
    public function productsComprehensiveNotAvailable()
    {
        $products = Product::query()->paginate();
        $categories = Category::query()->get();
        return view('admin.products.index_comprehensive_not_available', compact('products','categories'));
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
     * Show the form for creating a new gold product.
     */
    public function createGold()
    {
        $categories = Category::query()->get();
        $products = Product::query()->main()->get(); // For parent product selection
        return view('admin.products.create_gold', compact('categories', 'products'));
    }

    public function createNonGold()
    {
        $categories = Category::query()->get();
        $products = Product::query()->main()->get(); // For parent product selection
        return view('admin.products.create_non_gold', compact('categories', 'products'));
    }

    public function createComprehensive()
    {
        $categories = Category::query()->get();
        return view('admin.products.create_comprehensive', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        try {
            $validated = $request->validated();
            
            // Check if it's a non-gold product
            $isNotGoldProduct = $request->input('is_not_gold_product') == '1';
            
            // If it's a gold product and has etikets or orderable_etikets, use new logic
            $hasEtikets = $request->has('etikets') && is_array($request->etikets) && !empty($request->etikets);
            $hasOrderableEtikets = $request->has('orderable_etikets') && is_array($request->orderable_etikets) && !empty($request->orderable_etikets);
            if (!$isNotGoldProduct && ($hasEtikets || $hasOrderableEtikets)) {
                return $this->storeGoldProductWithEtikets($request, $validated);
            }
            
            // Original logic for non-gold products or products without etikets
            // Prepare product data
            $productData = [
                'name' => $validated['name'],
                'weight' => $isNotGoldProduct ? null : ($validated['weight'] ?? null),
                'darsad_kharid' => $isNotGoldProduct ? null : ($validated['darsad_kharid'] ?? null),
                'ojrat' => $isNotGoldProduct ? null : ($validated['ojrat'] ?? null),
                'discount_percentage' => $validated['discount_percentage'] ?? 0, // Set to 0 if null
                'description' => $validated['description'] ?? null,
                'parent_id' => $request->input('parent_id') ? (int)$request->input('parent_id') : null,
            ];
            
            // Handle price - multiply by 10 for storage
            if (isset($validated['price']) && $validated['price'] > 0) {
                $productData['price'] = $validated['price'] * 10;
            } else {
                if ($isNotGoldProduct) {
                    // Non-gold products must have a price
                    return response()->json([
                        'success' => false,
                        'message' => 'قیمت برای محصولات غیر طلایی الزامی است'
                    ], 422);
                } else {
                    // Calculate price from taban gohar if not provided (for gold products)
                    $productData['price'] = 0;
                }
            }
            
            // Handle attribute group
            if (!empty($validated['attribute_group'])) {
                $attributeGroup = AttributeGroup::firstOrCreate([
                    'name' => $validated['attribute_group']
                ]);
                $productData['attribute_group_id'] = $attributeGroup->id;
            }
            
            // Create the product
            $product = Product::create($productData);
            
            // If price was 0 and it's a gold product, calculate from taban gohar
            if ($productData['price'] == 0 && !$isNotGoldProduct) {
                $product->refresh();
                $tabanGoharPrice = $product->taban_gohar_price;
                if ($tabanGoharPrice > 0) {
                    $product->updateQuietly(['price' => $tabanGoharPrice * 10]);
                }
            }
            
            // Handle cover image
            if ($request->hasFile('cover_image')) {
                $product->clearMediaCollection('cover_image');
                $product->addMedia($request->file('cover_image'))
                    ->toMediaCollection('cover_image');
            }
            
            // Handle gallery images
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $image) {
                    if ($image->isValid()) {
                        $product->addMedia($image)
                            ->toMediaCollection('gallery');
                    }
                }
            }
            
            // Handle categories
            if ($request->has('category_ids') && !empty($request->category_ids)) {
                $product->categories()->sync($request->category_ids);
            }
            
            // Handle etikets - auto-generate with product details
            if ($request->has('etikets') && is_array($request->etikets)) {
                $etiketCodes = [];
                foreach ($request->etikets as $etiketIndex => $etiketData) {
                    // Get count, weight, and price from etiket data
                    $count = isset($etiketData['count']) && $etiketData['count'] > 0 ? (int)$etiketData['count'] : 1;
                    $weight = isset($etiketData['weight']) && $etiketData['weight'] > 0 ? (float)$etiketData['weight'] : ($product->weight ?? 0);
                    $price = isset($etiketData['price']) && $etiketData['price'] > 0 ? (int)($etiketData['price'] * 10) : $product->getRawOriginal('price'); // Multiply by 10 for storage
                    
                    // Generate base code using product ID and timestamp
                    $baseCode = $product->id . '-' . time() . '-' . ($etiketIndex + 1);
                    
                    // Create multiple etikets based on count
                    for ($i = 0; $i < $count; $i++) {
                        // Generate unique code for each etiket
                        $etiketCode = $count > 1 ? $baseCode . '-' . ($i + 1) : $baseCode;
                        
                        // Ensure code is unique (check in database)
                        $attempts = 0;
                        while (Etiket::where('code', $etiketCode)->exists() && $attempts < 10) {
                            $etiketCode = $baseCode . '-' . ($i + 1) . '-' . mt_rand(1000, 9999);
                            $attempts++;
                        }
                        
                        // Check for duplicate in current batch
                        if (in_array($etiketCode, $etiketCodes)) {
                            continue; // Skip duplicates in same batch
                        }
                        
                        // Create etiket with provided details
                        Etiket::create([
                            'code' => $etiketCode,
                            'name' => $product->name,
                            'weight' => $weight,
                            'price' => $price,
                            'product_id' => $product->id,
                            'ojrat' => $product->ojrat ?? null,
                            'darsad_kharid' => $product->darsad_kharid ?? null,
                            'is_mojood' => 1,
                        ]);
                        
                        $etiketCodes[] = $etiketCode; // Track added codes
                    }
                }
            }
            
            // Update discounted price (handled by observer, but ensure it's done)
            $product->refresh();
            $this->updateDiscountedPrice($product);
            
            return response()->json([
                'success' => true,
                'message' => 'محصول با موفقیت ایجاد شد',
                'product' => $product
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error creating product: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد محصول: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Store gold product with etikets - each etiket creates or assigns to a product
     */
    private function storeGoldProductWithEtikets($request, $validated)
    {
        $productName = $validated['name'];
        $enteredParentId = $request->input('parent_id') ? (int)$request->input('parent_id') : null;
        $firstProductId = null;
        $createdProducts = [];
        $etiketCodes = []; // For zr- codes
        $orderableEtiketCodes = []; // For s- codes
        
        // Common product data from form
        $commonProductData = [
            'darsad_kharid' => $validated['darsad_kharid'] ?? null,
            'ojrat' => $validated['ojrat'] ?? null,
            'discount_percentage' => $validated['discount_percentage'] ?? 0,
            'description' => $validated['description'] ?? null,
        ];
        
        // Handle attribute group
        $attributeGroupId = null;
        if (!empty($validated['attribute_group'])) {
            $attributeGroup = AttributeGroup::firstOrCreate([
                'name' => $validated['attribute_group']
            ]);
            $attributeGroupId = $attributeGroup->id;
        }
        
        // Process regular etikets
        $etiketsToProcess = [];
        if ($request->has('etikets') && is_array($request->etikets)) {
            foreach ($request->etikets as $etiketIndex => $etiketData) {
                $etiketsToProcess[] = [
                    'data' => $etiketData,
                    'orderable_after_out_of_stock' => false
                ];
            }
        }
        
        // Process orderable etikets
        if ($request->has('orderable_etikets') && is_array($request->orderable_etikets)) {
            foreach ($request->orderable_etikets as $etiketIndex => $etiketData) {
                $etiketsToProcess[] = [
                    'data' => $etiketData,
                    'orderable_after_out_of_stock' => true
                ];
            }
        }
        
        // Loop through each etiket card (both regular and orderable)
        foreach ($etiketsToProcess as $etiketEntry) {
            $etiketData = $etiketEntry['data'];
            $isOrderable = $etiketEntry['orderable_after_out_of_stock'];
            
            $etiketWeight = isset($etiketData['weight']) && $etiketData['weight'] > 0 ? (float)$etiketData['weight'] : null;
            $etiketPrice = isset($etiketData['price']) && $etiketData['price'] > 0 ? (int)($etiketData['price'] * 10) : null;
            $etiketCount = isset($etiketData['count']) && $etiketData['count'] > 0 ? (int)$etiketData['count'] : 1;
            
            if (!$etiketWeight || $etiketWeight <= 0) {
                continue; // Skip etikets without valid weight
            }
            
            // Check if product with same name and weight exists
            $existingProduct = Product::where('name', $productName)
                ->where('weight', $etiketWeight)
                ->whereNull('parent_id') // Only check main products
                ->first();
            
            if ($existingProduct) {
                // Use existing product
                $product = $existingProduct;
            } else {
                // Create new product
                $productData = array_merge($commonProductData, [
                    'name' => $productName,
                    'weight' => $etiketWeight,
                    'price' => $etiketPrice ?? 0,
                    'attribute_group_id' => $attributeGroupId,
                ]);
                
                // Determine parent_id
                if ($enteredParentId) {
                    $productData['parent_id'] = $enteredParentId;
                } elseif ($firstProductId) {
                    // Use first created product as parent for subsequent products
                    $productData['parent_id'] = $firstProductId;
                } else {
                    // First product has no parent
                    $productData['parent_id'] = null;
                }
                
                // Create the product
                $product = Product::create($productData);
                
                // If price was 0, calculate from taban gohar
                if ($productData['price'] == 0) {
                    $product->refresh();
                    $tabanGoharPrice = $product->taban_gohar_price;
                    if ($tabanGoharPrice > 0) {
                        $product->updateQuietly(['price' => $tabanGoharPrice * 10]);
                    }
                }
                
                // Store first product ID
                if (!$firstProductId) {
                    $firstProductId = $product->id;
                    
                    // Handle cover image for first product
                    if ($request->hasFile('cover_image')) {
                        $product->clearMediaCollection('cover_image');
                        $product->addMedia($request->file('cover_image'))
                            ->toMediaCollection('cover_image');
                    }
                    
                    // Handle gallery images for first product
                    if ($request->hasFile('gallery')) {
                        foreach ($request->file('gallery') as $image) {
                            if ($image->isValid()) {
                                $product->addMedia($image)
                                    ->toMediaCollection('gallery');
                            }
                        }
                    }
                    
                    // Handle categories for first product
                    if ($request->has('category_ids') && !empty($request->category_ids)) {
                        $product->categories()->sync($request->category_ids);
                    }
                } else {
                    // For subsequent products, sync categories without images
                    if ($request->has('category_ids') && !empty($request->category_ids)) {
                        $product->categories()->sync($request->category_ids);
                    }
                }
                
                $createdProducts[] = $product;
                $this->updateDiscountedPrice($product);
            }
            
            // Create etikets for this product based on count
            for ($i = 0; $i < $etiketCount; $i++) {
                // Generate unique code: zr-{number} for regular, s-{number} for orderable
                $prefix = $isOrderable ? 's' : 'zr';
                $existingCodesForPrefix = $isOrderable ? $orderableEtiketCodes : $etiketCodes;
                $etiketCode = $this->generateUniqueEtiketCode($existingCodesForPrefix, $prefix);
                
                // Create etiket
                Etiket::create([
                    'code' => $etiketCode,
                    'name' => $productName,
                    'weight' => $etiketWeight,
                    'price' => $etiketPrice ?? $product->getRawOriginal('price'),
                    'product_id' => $product->id,
                    'ojrat' => $product->ojrat ?? null,
                    'darsad_kharid' => $product->darsad_kharid ?? null,
                    'is_mojood' => 1,
                    'orderable_after_out_of_stock' => $isOrderable ? 1 : 0,
                ]);
                
                // Track code in appropriate array
                if ($isOrderable) {
                    $orderableEtiketCodes[] = $etiketCode;
                } else {
                    $etiketCodes[] = $etiketCode;
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => count($createdProducts) . ' محصول با موفقیت ایجاد شد',
            'products' => $createdProducts,
            'first_product_id' => $firstProductId
        ]);
    }
    
    /**
     * Generate unique etiket code in format: {prefix}-{number} starting from 7000
     * @param array $existingCodes Existing codes in current batch
     * @param string $prefix Code prefix ('zr' for regular, 's' for orderable)
     * @return string
     */
    private function generateUniqueEtiketCode(array $existingCodes = [], string $prefix = 'zr'): string
    {
        $startNumber = 7000;
        $highestNumber = $startNumber - 1;
        
        // Find the highest existing code number in database that matches {prefix}-{number} pattern
        $etikets = Etiket::where('code', 'like', $prefix . '-%')->get();
        foreach ($etikets as $etiket) {
            $pattern = '/^' . preg_quote($prefix, '/') . '-(\d+)$/';
            if (preg_match($pattern, $etiket->code, $matches)) {
                $codeNumber = (int)$matches[1];
                if ($codeNumber >= $highestNumber) {
                    $highestNumber = $codeNumber;
                }
            }
        }
        
        // Check for codes in current batch and find the highest
        foreach ($existingCodes as $code) {
            $pattern = '/^' . preg_quote($prefix, '/') . '-(\d+)$/';
            if (preg_match($pattern, $code, $matches)) {
                $codeNumber = (int)$matches[1];
                if ($codeNumber >= $highestNumber) {
                    $highestNumber = $codeNumber;
                }
            }
        }
        
        // Start from the highest number found + 1, or 7000 if no codes exist
        $nextNumber = max($startNumber, $highestNumber + 1);
        
        return $prefix . '-' . $nextNumber;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Eager load products relationship for comprehensive products
        $product = Product::query()->with('products')->findOrFail($id);
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
        
        
        // Only allow name change for comprehensive products
        if (!$product->is_comprehensive && isset($validated['name'])) {
            // Remove name from validated data if product is not comprehensive
            unset($validated['name']);
        }
        
        $product->update($validated);
        
        // Apply discount_percentage to children if checkbox is checked
        if ($request->has('apply_discount_to_children') && $request->input('apply_discount_to_children') == '1') {
            $discountPercentage = $request->input('discount_percentage') ?? 0;
            $children = $product->children()->get();
            foreach ($children as $child) {
                $child->update(['discount_percentage' => $discountPercentage]);
                // Update discounted price for child
                $this->updateDiscountedPrice($child);
            }
        }

        // Handle cover image deletion
        if ($request->has('delete_cover_image') && $request->delete_cover_image == '1') {
            $product->clearMediaCollection('cover_image');
        }
        
        // Handle cover image upload
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

        // Handle categories - attach without removing existing ones or creating duplicates
        if ($request->filled('category_ids')) {
            $product->categories()->sync($request->category_ids);
        }

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

        // Update discounted price
        $this->updateDiscountedPrice($product);

        return response()->json($product);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $product = Product::findOrFail($id);
            
            // If it's a comprehensive product, delete related ComprehensiveProduct records
            if ($product->is_comprehensive) {
                ComprehensiveProduct::where('comprehensive_product_id', $product->id)->delete();
            }
            
            // Delete related ComprehensiveProduct records where this product is a component
            ComprehensiveProduct::where('product_id', $product->id)->delete();
            
            // Delete media (images)
            $product->clearMediaCollection('cover_image');
            $product->clearMediaCollection('gallery');
            
            // Delete category relationships
            $product->categories()->detach();
            
            // Delete the product
            $product->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'محصول با موفقیت حذف شد'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error deleting product: ' . $e->getMessage(), [
                'product_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف محصول: ' . $e->getMessage()
            ], 500);
        }
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
        // Show only parent products where count > 0, available_count == 0, and parent_id is null
        $query = Product::query()
            ->whereNull('parent_id')
            ->where('count', '>', 0)
            ->where('available_count', '=', 0)
            ->select('*');

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
        // Show only available comprehensive products (where ALL constituent products have single_count >= 1)
        $query = Product::query()
            ->comprehensive()
            ->main()
            ->whereHas('products', function ($productsQuery) {
                // Check if constituent product has at least one available etiket (single_count >= 1)
                $productsQuery->whereHas('etikets', function ($etiketQuery) {
                    $etiketQuery->where('is_mojood', 1);
                });
            })
            // Ensure ALL constituent products have available etikets (not just one)
            ->whereDoesntHave('products', function ($productsQuery) {
                $productsQuery->whereDoesntHave('etikets', function ($etiketQuery) {
                    $etiketQuery->where('is_mojood', 1);
                });
            })
            ->select('*');

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
    
    public function products_comprehensive_not_available_table(Request $request)
    {
        // Show comprehensive products where at least one constituent product doesn't have available etikets
        $query = Product::query()
            ->comprehensive()
            ->main()
            ->whereHas('products', function ($productsQuery) {
                // Must have at least one product without available etikets
                $productsQuery->whereDoesntHave('etikets', function ($etiketQuery) {
                    $etiketQuery->where('is_mojood', 1);
                });
            })
            ->select('*');

        // Get total records before applying filters
        $totalRecords = $query->count();

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('etikets', function ($q2) use ($search) {
                        $q2->where('code', '=', "{$search}");
                    })
                    ->orWhereHas('children.etikets', function ($q2) use ($search) {
                        $q2->where('code', '=', "{$search}");
                    });
            });
        }

        // Handle pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        if ($length <= 0) {
            $length = 10;
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
                return AdminProductResource::make($item);
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
                // Add categories without removing existing ones, and prevent duplicates
                // syncWithoutDetaching adds new categories but keeps existing ones
                $product->categories()->syncWithoutDetaching($request->category_ids);
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

            // Update discounted price
            $this->updateDiscountedPrice($product);
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
        try {
            $validated = $request->validated();
            if (empty($validated['slug'])) {
                unset($validated['slug']);
            }
            $validated['is_comprehensive'] = 1;
            $validated['weight'] = 0;
            $validated['price'] = 0;

            // Ensure product_ids is an array
            if (!is_array($request->product_ids) || empty($request->product_ids)) {
                return back()->withErrors(['product_ids' => 'حداقل یک محصول باید انتخاب شود']);
            }

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

        // Handle categories - support both 'categories[]' and 'category_ids' field names
        $categoryIds = [];
        if ($request->has('category_ids')) {
            $categoryIds = $request->category_ids;
        } elseif ($request->has('categories')) {
            // Handle both array and single value
            $categoryIds = is_array($request->categories) ? $request->categories : [$request->categories];
        }
        
            if (!empty($categoryIds)) {
                $product->categories()->sync($categoryIds);
            }

            foreach ($request->product_ids as $productId) {
                ComprehensiveProduct::create([
                    'comprehensive_product_id' => $product->id,
                    'product_id' => $productId,
                ]);
            }
            
            // Update discounted price
            $this->updateDiscountedPrice($product);
            
            return back()->with('success', 'محصول جامع با موفقیت ایجاد شد');
        } catch (\Exception $e) {
            \Log::error('Error creating comprehensive product: ' . $e->getMessage());
            \Log::error('Request data: ' . json_encode($request->all()));
            return back()->withErrors(['error' => 'خطا در ایجاد محصول جامع: ' . $e->getMessage()])->withInput();
        }
    }
    public function updateComprehensiveProduct(updateComprehensiveProductRequest $request)
    {

    }

    /**
     * Add a product to comprehensive product
     */
    public function addProductToComprehensive(Request $request)
    {
        \Log::info('addProductToComprehensive called', $request->all());
        
        // Ensure product_id is an integer
        $productId = $request->input('product_id');
        if (is_string($productId) && strpos($productId, 'Product:') !== false) {
            $productId = str_replace('Product:', '', $productId);
        }
        $productId = (int) $productId;
        $request->merge(['product_id' => $productId]);
        
        // Ensure comprehensive_product_id is an integer
        $comprehensiveProductId = (int) $request->input('comprehensive_product_id');
        $request->merge(['comprehensive_product_id' => $comprehensiveProductId]);
        
        $request->validate([
            'comprehensive_product_id' => 'required|integer|exists:products,id',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $comprehensiveProduct = Product::findOrFail($comprehensiveProductId);
        if (!$comprehensiveProduct->is_comprehensive) {
            \Log::warning('Product is not comprehensive', ['id' => $comprehensiveProductId]);
            return response()->json([
                'success' => false,
                'message' => 'محصول انتخاب شده یک محصول جامع نیست'
            ], 422);
        }

        // Check if product is already added
        $exists = ComprehensiveProduct::where('comprehensive_product_id', $comprehensiveProductId)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            \Log::info('Product already exists in comprehensive', [
                'comprehensive_product_id' => $comprehensiveProductId,
                'product_id' => $productId
            ]);
            return response()->json([
                'success' => false,
                'message' => 'این محصول قبلا به محصول جامع اضافه شده است'
            ], 422);
        }

        try {
            ComprehensiveProduct::create([
                'comprehensive_product_id' => $comprehensiveProductId,
                'product_id' => $productId,
            ]);

            // Recalculate comprehensive product price and weight
            $comprehensiveProduct = Product::findOrFail($comprehensiveProductId);
            $this->recalculateComprehensiveProductTotals($comprehensiveProduct);

            \Log::info('Product added to comprehensive', [
                'comprehensive_product_id' => $comprehensiveProductId,
                'product_id' => $productId,
                'verification' => ComprehensiveProduct::where('comprehensive_product_id', $comprehensiveProductId)
                    ->where('product_id', $productId)
                    ->exists()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'محصول با موفقیت به محصول جامع اضافه شد'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error adding product to comprehensive: ' . $e->getMessage(), [
                'comprehensive_product_id' => $comprehensiveProductId,
                'product_id' => $productId,
                'original_request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'خطا در افزودن محصول: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a product from comprehensive product
     */
    public function removeProductFromComprehensive(Request $request)
    {
        $request->validate([
            'comprehensive_product_id' => 'required|exists:products,id',
            'product_id' => 'required|exists:products,id',
        ]);

        $deleted = ComprehensiveProduct::where('comprehensive_product_id', $request->comprehensive_product_id)
            ->where('product_id', $request->product_id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'محصول در محصول جامع یافت نشد'
            ], 404);
        }

        // Recalculate comprehensive product price and weight
        $comprehensiveProduct = Product::findOrFail($request->comprehensive_product_id);
        $this->recalculateComprehensiveProductTotals($comprehensiveProduct);

        return response()->json([
            'success' => true,
            'message' => 'محصول با موفقیت از محصول جامع حذف شد'
        ]);
    }

    /**
     * Recalculate comprehensive product totals (price and weight)
     */
    private function recalculateComprehensiveProductTotals(Product $comprehensiveProduct)
    {
        $totalPrice = 0;
        $totalWeight = 0;
        
        // Get all products in this comprehensive product
        $productIds = \App\Models\ComprehensiveProduct::where('comprehensive_product_id', $comprehensiveProduct->id)
            ->pluck('product_id');
        
        $products = Product::whereIn('id', $productIds)->get();
        
        foreach ($products as $pr) {
            $totalPrice += $pr->price * 10;
            $totalWeight += $pr->weight;
        }
        
        $comprehensiveProduct->update([
            'price' => $totalPrice,
            'weight' => $totalWeight
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        // Search products
        $products = Product::where('name', 'LIKE', "%{$query}%")
            ->limit(20)
            ->get(['id', 'name']);

        $productResults = $products->map(function ($product) {
            return [
                'id' => "Product:{$product->id}",
                'text' => $product->name,
            ];
        });

        // Search categories
        $categories = Category::where('title', 'LIKE', "%{$query}%")
            ->limit(20)
            ->get(['id', 'title']);

        $categoryResults = $categories->map(function ($category) {
            return [
                'id' => "Category:{$category->id}",
                'text' => $category->title,
            ];
        });

        // Merge products + categories
        $results = $productResults->merge($categoryResults);

        return response()->json($results);
    }



    public function ajaxSearch(Request $request)
    {
        $query = $request->input('q');
        $availableOnly = $request->has('available_only') && $request->available_only == '1';

        // Search products by name OR exact etiket code - only show products with count >= 1
        $productsQuery = Product::where(function($q) use ($query) {
                // Search by product name
                $q->where('name', 'LIKE', "%{$query}%")
                  // OR search by exact etiket code
                  ->orWhereHas('etikets', function($etQ) use ($query) {
                      $etQ->where('code', '=', $query) // Exact match for etiket code
                          ->where('is_mojood', 1); // Only available etikets
                  });
            })
            ->whereHas('etikets', function($q) {
                $q->where('is_mojood', 1); // Only products with available etikets
            });

        // Filter unavailable products if requested - use available() scope instead of single_count
        if ($availableOnly) {
            $productsQuery->available();
        }

        $products = $productsQuery->select('id', 'name', 'price', 'discounted_price', 'weight', 'parent_id')
            ->distinct() // Prevent duplicates
            ->limit(50) // Limit results for performance
            ->get()
            ->filter(function ($product) {
                // Filter by single_count >= 1
                return $product->single_count >= 1;
            });
        
        $products = $products->map(function ($product) {
            // Calculate single_count using the accessor after loading the product
            $singleCount = $product->single_count;
            $finalPrice = (int) $product->price;
            $originalPrice = (int) $product->originalPrice;
            $discountedPrice = $product->discounted_price ? (int) $product->discounted_price : null;

            return [
                'id' => "Product:{$product->id}",
                'text' => $product->name . (($product->weight) ? ' (' . $product->weight . 'g)' : '') . ' (موجودی: ' . $singleCount . ')',
                'price' => $finalPrice,
                'discounted_price' => $discountedPrice,
                'original_price' => $originalPrice,
                'single_count' => $singleCount,
                'weight' => $product->weight
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

    /**
     * AJAX search for parent products only (main products with parent_id = null)
     * Does not filter by count or single_count
     * Can search by product name or etiket code
     */
    public function ajaxSearchParents(Request $request)
    {
        $query = $request->input('q');
        $parentProductIds = collect();

        // First, search by product name - only main products (parent_id = null)
        $productsByName = Product::where('name', 'LIKE', "%{$query}%")
            ->whereNull('parent_id')
            ->select('id', 'name', 'price', 'discounted_price', 'weight', 'parent_id')
            ->distinct()
            ->limit(50)
            ->get();
        
        $parentProductIds = $parentProductIds->merge($productsByName->pluck('id'));

        // Also search by etiket code
        $etikets = \App\Models\Etiket::where('code', '=', $query)
            ->with('product:id,parent_id,name,price,discounted_price,weight')
            ->get();

        foreach ($etikets as $etiket) {
            if ($etiket->product) {
                $product = $etiket->product;
                // If product has a parent, get the parent; otherwise use the product itself
                if ($product->parent_id) {
                    $parent = Product::find($product->parent_id);
                    if ($parent && $parent->parent_id === null) {
                        // Only add if it's a main product (parent_id = null)
                        $parentProductIds->push($parent->id);
                    }
                } else {
                    // Product is already a parent
                    $parentProductIds->push($product->id);
                }
            }
        }

        // Get unique parent products
        $uniqueParentIds = $parentProductIds->unique()->take(50);
        
        $products = Product::whereIn('id', $uniqueParentIds)
            ->select('id', 'name', 'price', 'discounted_price', 'weight', 'parent_id')
            ->get()
            ->map(function ($product) {
                // Calculate counts for display (but don't filter by them)
                $singleCount = $product->single_count;
                $count = $product->count;
                $finalPrice = (int) $product->price;
                $originalPrice = (int) $product->originalPrice;
                $discountedPrice = $product->discounted_price ? (int) $product->discounted_price : null;

                return [
                    'id' => "Product:{$product->id}",
                    'text' => $product->name . (($product->weight) ? ' (' . $product->weight . 'g)' : '') . ' (موجودی: ' . $count . ')',
                    'price' => $finalPrice,
                    'discounted_price' => $discountedPrice,
                    'original_price' => $originalPrice,
                    'single_count' => $singleCount,
                    'count' => $count,
                    'weight' => $product->weight
                ];
            });

        return response()->json([
            'results' => $products,
            'pagination' => ['more' => false]
        ]);
    }

    /**
     * AJAX search for products with single_count > 0 (for comprehensive products)
     * Includes both parent and child products
     * Supports searching by product name or etiket code
     */
    public function ajaxSearchForComprehensive(Request $request)
    {
        $query = $request->input('q');
        $productIds = collect();

        // First, search by product name - products with single_count > 0
        // Exclude comprehensive products (they don't have direct etikets)
        // Include both parent and child products (no parent_id filter)
        $productsByName = Product::where('name', 'LIKE', "%{$query}%")
            ->where(function ($q) {
                $q->whereNull('is_comprehensive')
                    ->orWhere('is_comprehensive', 0);
            })
            ->whereHas('etikets', function ($q) {
                $q->where('is_mojood', 1);
            })
            ->select('id', 'name', 'price', 'discounted_price', 'weight', 'parent_id')
            ->distinct()
            ->limit(50)
            ->get()
            ->filter(function ($product) {
                // Double-check single_count > 0
                return $product->single_count > 0;
            });
        
        $productIds = $productIds->merge($productsByName->pluck('id'));

        // Also search by etiket code
        $etikets = \App\Models\Etiket::where('code', '=', $query)
            ->where('is_mojood', 1) // Only available etikets
            ->with('product:id,name,price,discounted_price,weight,parent_id,is_comprehensive')
            ->get();

        foreach ($etikets as $etiket) {
            if ($etiket->product) {
                $product = $etiket->product;
                // Exclude comprehensive products
                if ($product->is_comprehensive == 1) {
                    continue;
                }
                // Check if product has single_count > 0 (has available etikets)
                if ($product->single_count > 0) {
                    $productIds->push($product->id);
                }
            }
        }

        // Get unique products with single_count > 0
        $uniqueProductIds = $productIds->unique()->take(50);
        
        if ($uniqueProductIds->isEmpty()) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false]
            ]);
        }
        
        $products = Product::whereIn('id', $uniqueProductIds)
            ->whereHas('etikets', function ($q) {
                $q->where('is_mojood', 1);
            })
            ->select('id', 'name', 'price', 'discounted_price', 'weight', 'parent_id')
            ->get()
            ->filter(function ($product) {
                // Final check: single_count > 0
                return $product->single_count > 0;
            })
            ->map(function ($product) {
                // Calculate counts for display
                $singleCount = $product->single_count;
                $count = $product->count;
                $finalPrice = (int) $product->price;
                $originalPrice = (int) $product->originalPrice;
                $discountedPrice = $product->discounted_price ? (int) $product->discounted_price : null;

                // Build display text
                $text = $product->name;
                if ($product->weight) {
                    $text .= ' (' . $product->weight . 'g)';
                }
                $text .= ' (موجودی: ' . $singleCount . ')';
                
                // Add parent/child indicator if needed
                if ($product->parent_id) {
                    $text .= ' [فرزند]';
                } else {
                    $text .= ' [اصلی]';
                }

                return [
                    'id' => $product->id,
                    'text' => $text,
                    'price' => $finalPrice,
                    'discounted_price' => $discountedPrice,
                    'original_price' => $originalPrice,
                    'single_count' => $singleCount,
                    'count' => $count,
                    'weight' => $product->weight,
                    'parent_id' => $product->parent_id
                ];
            });

        return response()->json([
            'results' => $products,
            'pagination' => ['more' => false]
        ]);
    }

    /**
     * Search product by etiket code
     */
    public function searchByEtiketCode(Request $request)
    {
        $etiketCode = $request->input('etiket_code') ?? $request->input('code');
        
        if (empty($etiketCode)) {
            return response()->json([
                'success' => false,
                'exists' => false,
                'message' => 'کد اتیکت الزامی است'
            ], 400);
        }

        // Check if etiket code exists and get product
        $etiket = \App\Models\Etiket::where('code', $etiketCode)
            ->with('product')
            ->first();

        if ($etiket && $etiket->product) {
            $product = $etiket->product;
            
            // Check if product is available (single_count >= 1)
            if ($product->single_count < 1) {
                return response()->json([
                    'success' => false,
                    'exists' => true,
                    'message' => 'این کد اتیکت موجود است اما محصول در دسترس نیست (موجودی صفر)'
                ]);
            }
            
            // Get product price (stored multiplied by 10)
            $rawPrice = $product->getRawOriginal('price') ?? 0;
            $basePrice = $rawPrice / 10;
            $discountedPrice = $product->discounted_price ?? null;
            $originalPrice = $product->originalPrice ?? null;
            
            return response()->json([
                'success' => true,
                'exists' => true,
                'message' => 'این کد اتیکت در پایگاه داده موجود است',
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (int) $basePrice,
                    'discounted_price' => $discountedPrice ? (int) $discountedPrice : null,
                    'original_price' => $originalPrice ? (int) $originalPrice : null,
                    'weight' => $product->weight ?? null,
                    'etiket_code' => $etiketCode
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'exists' => false,
            'message' => 'کد اتیکت در پایگاه داده موجود نیست'
        ]);
    }

    /**
     * Remove cover image from product
     */
    public function removeCoverImage($product_id)
    {
        try {
            $product = Product::find($product_id);
            // Clear all media in 'cover_image' collection
            $product->clearMediaCollection('cover_image');
            
            return response()->json([
                'success' => true,
                'message' => 'تصویر کاور با موفقیت حذف شد'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف تصویر کاور'
            ], 500);
        }
    }

    /**
     * Export products to Excel
     */
    public function export(Request $request)
    {
        $filters = [
            'filter' => $request->get('filter'),
            'category_ids' => $request->get('category_ids'),
            'searchKey' => $request->get('searchKey'),
            'searchVal' => $request->get('searchVal'),
        ];

        return Excel::download(
            new ProductsExport($filters), 
            'products_' . date('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Recalculate discounts for all products with discount_percentage > 0
     */
    public function recalculateDiscounts(Request $request)
    {
        try {
            // Get all products with discount_percentage > 0
            $products = Product::where('discount_percentage', '>', 0)->get();
            
            $count = 0;
            foreach ($products as $product) {
                $this->updateDiscountedPrice($product);
                $count++;
            }

            return response()->json([
                'success' => true,
                'message' => "تخفیف {$count} محصول با موفقیت محاسبه شد",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            \Log::error('Error recalculating discounts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'خطا در محاسبه مجدد تخفیف ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate and update discounted price.
     */
    private function updateDiscountedPrice(Product $product)
    {
        // Get raw price value (stored multiplied by 10) and discount percentage
        $rawPrice = $product->getRawOriginal('price');
        $discountPercentage = $product->discount_percentage;

        if ($rawPrice != 0 && $discountPercentage != 0) {
            // Calculate discounted price
            // Raw price is stored multiplied by 10, so divide by 10 to get actual price
            // discounted_price is stored as-is (not multiplied by 10)
            $discountedPrice = ($rawPrice / 10) * (1 - $discountPercentage / 100);

            // Round to nearest 1000 (last three digits to 000)
            $discountedPrice = round($discountedPrice, -3);

            $product->discounted_price = $discountedPrice;
        } else {
            $product->discounted_price = null;
            $product->discount_percentage = 0;
        }

        $product->saveQuietly();
    }
}
