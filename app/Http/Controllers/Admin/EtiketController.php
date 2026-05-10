<?php

namespace App\Http\Controllers\Admin;

use App\Exports\EtiketsExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Table\AdminEtiketResource;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Etiket;
use App\Models\ComprehensiveEtiket;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class EtiketController extends Controller
{
    /**
     * Display a listing of available etikets.
     */
    public function indexAvailable()
    {
        $categories = Category::query()->get();
        return view('admin.etikets.index_available', compact('categories'));
    }

    /**
     * Display a listing of unavailable etikets.
     */
    public function indexNotAvailable()
    {
        $categories = Category::query()->get();
        return view('admin.etikets.index_not_available', compact('categories'));
    }

    /**
     * Show deleted etikets
     */
    public function indexDeleted()
    {
        $categories = Category::query()->get();
        return view('admin.etikets.index_deleted', compact('categories'));
    }

    /**
     * Get etikets table data for DataTables
     */
    public function table(Request $request)
    {
        $query = Etiket::query()->with('product.categories')->select('etikets.*');
        
        // Handle deleted filter
        if ($request->has('deleted') && ($request->input('deleted') == '1' || $request->input('deleted') == 1)) {
            $query->onlyTrashed();
        }
        
        // Filter by availability if specified
        if ($request->has('is_mojood')) {
            $isMojood = $request->input('is_mojood');
            if ($isMojood === '1' || $isMojood === 1) {
                $query->where('etikets.is_mojood', 1);
            } elseif ($isMojood === '0' || $isMojood === 0) {
                $query->where('etikets.is_mojood', 0);
            }
        }

        // Get total records before applying filters
        $totalRecords = $query->count();

        // Apply filters
        if ($request->has('name') && !empty($request->input('name'))) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('name') . '%');
            });
        }

        if ($request->has('code') && !empty($request->input('code'))) {
            $query->where('etikets.code', 'like', '%' . $request->input('code') . '%');
        }

        if ($request->has('weight') && !empty($request->input('weight'))) {
            $query->where('etikets.weight', $request->input('weight'));
        }

        if ($request->has('weight_from') && !empty($request->input('weight_from'))) {
            $query->where('etikets.weight', '>=', $request->input('weight_from'));
        }

        if ($request->has('weight_to') && !empty($request->input('weight_to'))) {
            $query->where('etikets.weight', '<=', $request->input('weight_to'));
        }

        if ($request->has('category_ids') && !empty($request->input('category_ids'))) {
            $categoryIds = is_array($request->input('category_ids')) 
                ? $request->input('category_ids') 
                : explode(',', $request->input('category_ids'));
            $query->whereHas('product.categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('etikets.code', 'like', "%{$search}%")
                    ->orWhereHas('product', function($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Get filtered records count after filters
        $filteredRecords = $query->count();

        // Handle pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        if ($length <= 0) {
            $length = 10;
        }

        // Apply sorting - check query parameters first (from select dropdown), then DataTable order
        $sortColumn = null;
        $sortDirection = 'desc';
        
        // Check if sort is provided via query parameters (from select dropdown)
        if ($request->has('sort_column') && !empty($request->input('sort_column'))) {
            $sortColumn = $request->input('sort_column');
            $sortDirection = $request->input('sort_direction', 'desc');
        } 
        // Otherwise check DataTable order parameter
        elseif ($request->has('order') && !empty($request->input('order'))) {
            $order = $request->input('order')[0];
            $columnIndex = $order['column'];
            $sortDirection = $order['dir'] === 'asc' ? 'asc' : 'desc';
            $sortColumn = $request->input("columns.{$columnIndex}.data");
        }
        
        if ($sortColumn) {
            switch ($sortColumn) {
                case 'code':
                    // Sort by numeric part of code (extract numbers after removing prefixes like s-)
                    // Use SUBSTRING_INDEX to get part after last dash, then cast to number
                    // Handle cases where code might not have a dash
                    $query->orderByRaw("CAST(
                        CASE 
                            WHEN etikets.code LIKE '%-%' 
                            THEN SUBSTRING_INDEX(etikets.code, '-', -1)
                            ELSE etikets.code
                        END AS UNSIGNED
                    ) {$sortDirection}");
                    break;
                case 'name':
                    // Join with products table to sort by product name
                    $query->join('products', 'etikets.product_id', '=', 'products.id')
                        ->orderBy('products.name', $sortDirection)
                        ->select('etikets.*');
                    break;
                case 'weight':
                    $query->orderBy('etikets.weight', $sortDirection);
                    break;
                case 'price':
                    $query->orderBy('etikets.price', $sortDirection);
                    break;
                case 'darsad_kharid':
                    $query->orderBy('etikets.darsad_kharid', $sortDirection);
                    break;
                case 'ojrat':
                    $query->orderBy('etikets.ojrat', $sortDirection);
                    break;
                default:
                    if (Schema::hasColumn('etikets', $sortColumn)) {
                        $query->orderBy('etikets.' . $sortColumn, $sortDirection);
                    }
                    break;
            }
        } else {
            $query->latest('etikets.id');
        }

        // Fetch paginated data
        $data = $query->skip($start)
            ->take($length)
            ->get()
            ->map(function ($item) {
                return AdminEtiketResource::make($item);
            });

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    public function getEtiketsOfProduct(Product $product)
    {
        return $product->EtiketsCodeAsArray;
    }

    public function search(Request $request)
    {
        $code = $request->input('q');

        $etiket = Etiket::where('code', $code)
            ->with('product')
            ->first();

        if (!$etiket || !$etiket->product) {
            return response()->json([
                'results' => [],
                'message' => 'No product found for this etiket code.'
            ]);
        }

        // Filter by single_count >= 1
        if ($etiket->product->single_count < 1) {
            return response()->json([
                'results' => [],
                'message' => 'Product is not available (single_count < 1).'
            ]);
        }

        return response()->json([
            'results' => [[
                'id' => $etiket->product->id,
                'text' => $etiket->code . ' - ' . $etiket->product->name,
                'product' => $etiket->product
            ]]
        ]);
    }

    /**
     * Show assign etiket page for a product
     */
    public function assignEtiket($product)
    {
        // Try to find by ID if numeric, otherwise by slug
        if (is_numeric($product)) {
            $productModel = Product::findOrFail($product);
        } else {
            $productModel = Product::where('slug', $product)->firstOrFail();
        }
        
        return view('admin.etikets.assign', ['product' => $productModel]);
    }

    public function storeForProduct(Request $request, $product)
    {
        // Find product by ID if numeric, otherwise by slug
        if (is_numeric($product)) {
            $productModel = Product::findOrFail($product);
        } else {
            $productModel = Product::where('slug', $product)->firstOrFail();
        }
        
        $request->validate([
            'etikets' => 'required|array|min:1',
            'etikets.*.count' => 'required|integer|min:1',
            'etikets.*.weight' => 'required|numeric|min:0',
        ]);

        $etiketCodes = [];
        $created = 0;
        $skipped = 0;

        // Get existing numeric codes to avoid duplicates
        $existingCodes = Etiket::whereRaw('code REGEXP \'^[0-9]+$\'')->pluck('code')->toArray();

        foreach ($request->etikets as $etiketData) {
            $count = (int)($etiketData['count'] ?? 1);
            $weight = (float)($etiketData['weight'] ?? 0);
            
            if ($count <= 0 || $weight <= 0) {
                $skipped += $count;
                continue;
            }
            
            // Generate unique numeric codes for each etiket
            for ($i = 0; $i < $count; $i++) {
                $etiketCode = $this->generateUniqueEtiketCode(
                    array_merge($etiketCodes, $existingCodes),
                    ''
                );
            
            // Check for duplicate in database
                $existingEtiket = Etiket::where('code', $etiketCode)->first();
            if ($existingEtiket) {
                $skipped++;
                continue;
            }
                
                // Calculate price based on weight, ojrat, and darsad_kharid
                $price = $this->calculateEtiketPrice($productModel, $weight);
            
            // Create etiket with product details
            Etiket::create([
                    'code' => $etiketCode,
                    'name' => $productModel->name,
                    'weight' => $weight,
                    'price' => $price,
                    'product_id' => $productModel->id,
                    'ojrat' => $productModel->ojrat ?? null,
                    'darsad_kharid' => $productModel->darsad_kharid ?? null,
                'is_mojood' => 1,
            ]);
            
                $etiketCodes[] = $etiketCode;
            $created++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "تعداد {$created} اتیکت با موفقیت ایجاد شد" . ($skipped > 0 ? " و {$skipped} اتیکت رد شد (تکراری یا خالی)" : ''),
            'created' => $created,
            'skipped' => $skipped
        ]);
    }

    /**
     * Show add etiket to product form (select product then add etikets).
     */
    public function addToProductForm()
    {
        return view('admin.etikets.add_to_product');
    }

    /**
     * Store etikets for a product from the add-to-product form (product_id, darsad_kharid, ojrat, etikets, orderable_etikets).
     */
    public function storeAddToProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'darsad_kharid' => 'nullable|numeric|min:0',
            'ojrat' => 'nullable|numeric|min:0',
            'etikets' => 'nullable|array',
            'etikets.*.code' => 'nullable|string|max:64',
            'etikets.*.weight' => 'required_with:etikets|numeric|min:0',
            'etikets.*.price' => 'nullable|numeric|min:0',
            'etikets.*.related_etikets' => 'nullable|array',
            'etikets.*.related_etikets.*' => 'integer|exists:etikets,id',
            'orderable_etikets' => 'nullable|array',
            'orderable_etikets.*.code' => 'nullable|string|max:64',
            'orderable_etikets.*.weight' => 'required_with:orderable_etikets|numeric|min:0',
            'orderable_etikets.*.price' => 'nullable|numeric|min:0',
            'orderable_etikets.*.related_etikets' => 'nullable|array',
            'orderable_etikets.*.related_etikets.*' => 'integer|exists:etikets,id',
        ]);

        $productModel = Product::findOrFail($request->product_id);
        $isNoneGold = ($productModel->type ?? 'gold') === 'none_gold';
        $isComprehensive = ($productModel->type ?? 'gold') === 'comprehensive_product';

        $etiketsList         = $request->input('etikets', []);
        $orderableEtiketsList = $request->input('orderable_etikets', []);

        if ($isNoneGold || $isComprehensive) {
            $hasRegular   = is_array($etiketsList) && count($etiketsList) > 0;
            $hasOrderable = is_array($orderableEtiketsList) && count($orderableEtiketsList) > 0;
        } else {
            $hasRegular = is_array($etiketsList) && count(array_filter($etiketsList, function ($e) {
                return (float)($e['weight'] ?? 0) > 0;
            })) > 0;
            $hasOrderable = is_array($orderableEtiketsList) && count(array_filter($orderableEtiketsList, function ($e) {
                return (float)($e['weight'] ?? 0) > 0;
            })) > 0;
        }

        if (!$hasRegular && !$hasOrderable) {
            return redirect()->back()->withInput()->withErrors(['product_id' => 'حداقل یک اتیکت (عادی یا قابل فروش پس از اتمام موجودی) با وزن معتبر وارد کنید.']);
        }

        // Update product ojrat if provided (darsad_kharid lives on etikets, not products)
        if ($request->has('ojrat') && $request->ojrat !== null && $request->ojrat !== '') {
            $productModel->update(['ojrat' => $request->ojrat]);
            $productModel->refresh();
        }

        $darsadKharid = ($request->darsad_kharid !== null && $request->darsad_kharid !== '')
            ? $request->darsad_kharid
            : null;

        $etiketCodes = [];
        $orderableEtiketCodes = [];
        $created = 0;
        $skipped = 0;
        $existingNumeric = Etiket::whereRaw('code REGEXP \'^[0-9]+$\'')->pluck('code')->toArray();
        $existingOrderable = Etiket::where('code', 'like', 's-%')->pluck('code')->toArray();

        // Regular etikets (one per row; code from form or generated)
        if ($hasRegular) {
            foreach ($etiketsList as $key => $etiketData) {
                $weight = ($isNoneGold || $isComprehensive) ? 0 : (float)($etiketData['weight'] ?? 0);
                if (!$isNoneGold && !$isComprehensive && $weight <= 0) {
                    $skipped++;
                    continue;
                }
                $codeInput = trim((string)($etiketData['code'] ?? ''));
                if ($codeInput !== '' && preg_match('/^\d+$/', $codeInput)) {
                    $etiketCode = $codeInput;
                } else {
                    $etiketCode = $this->generateUniqueEtiketCode(array_merge($etiketCodes, $existingNumeric), '');
                }
                if (Etiket::where('code', $etiketCode)->exists()) {
                    $skipped++;
                    continue;
                }
                if ($isNoneGold) {
                    $submittedPrice = $request->input("etikets.{$key}.price") ?? ($etiketData['price'] ?? 0);
                    $price = (float) $submittedPrice;
                } elseif ($isComprehensive) {
                    // For comprehensive products, etiket price is always stored as 0
                    $price = 0;
                } else {
                    $price = $this->calculateEtiketPrice($productModel, $weight);
                }
                $createdEtiket = Etiket::create([
                    'code' => $etiketCode,
                    'name' => $productModel->name,
                    'weight' => $weight,
                    'price' => $price,
                    'product_id' => $productModel->id,
                    'ojrat' => $productModel->ojrat ?? null,
                    'darsad_kharid' => $darsadKharid,
                    'is_mojood' => 1,
                    'orderable_after_out_of_stock' => 0,
                    'type' => $isComprehensive ? 'comprehensive' : 'real',
                ]);
                // Save related etikets for comprehensive products
                if ($isComprehensive && !empty($etiketData['related_etikets']) && is_array($etiketData['related_etikets'])) {
                    foreach ($etiketData['related_etikets'] as $relatedId) {
                        ComprehensiveEtiket::create([
                            'etiket_id' => $createdEtiket->id,
                            'related_etiket_id' => $relatedId,
                        ]);
                    }
                }
                $etiketCodes[] = $etiketCode;
                $existingNumeric[] = $etiketCode;
                $created++;
            }
        }

        // Orderable etikets (s-xxxx, one per row; code from form or generated)
        if ($hasOrderable) {
            foreach ($orderableEtiketsList as $key => $etiketData) {
                $weight = ($isNoneGold || $isComprehensive) ? 0 : (float)($etiketData['weight'] ?? 0);
                if (!$isNoneGold && !$isComprehensive && $weight <= 0) {
                    $skipped++;
                    continue;
                }
                $codeInput = trim((string)($etiketData['code'] ?? ''));
                if ($codeInput !== '' && preg_match('/^s-\d+$/', $codeInput)) {
                    $etiketCode = $codeInput;
                } else {
                    $etiketCode = $this->generateUniqueEtiketCode(array_merge($orderableEtiketCodes, $existingOrderable), 's');
                }
                if (Etiket::where('code', $etiketCode)->exists()) {
                    $skipped++;
                    continue;
                }
                if ($isNoneGold) {
                    $submittedPrice = $request->input("orderable_etikets.{$key}.price") ?? ($etiketData['price'] ?? 0);
                    $price = (float) $submittedPrice;
                } elseif ($isComprehensive) {
                    // For comprehensive products, etiket price is always stored as 0
                    $price = 0;
                } else {
                    $price = $this->calculateEtiketPrice($productModel, $weight);
                }
                $createdEtiket = Etiket::create([
                    'code' => $etiketCode,
                    'name' => $productModel->name,
                    'weight' => $weight,
                    'price' => $price,
                    'product_id' => $productModel->id,
                    'ojrat' => $productModel->ojrat ?? null,
                    'darsad_kharid' => $darsadKharid,
                    'is_mojood' => 1,
                    'orderable_after_out_of_stock' => 1,
                    'type' => $isComprehensive ? 'comprehensive' : 'real',
                ]);
                // Save related etikets for comprehensive products (orderable)
                if ($isComprehensive && !empty($etiketData['related_etikets']) && is_array($etiketData['related_etikets'])) {
                    foreach ($etiketData['related_etikets'] as $relatedId) {
                        ComprehensiveEtiket::create([
                            'etiket_id' => $createdEtiket->id,
                            'related_etiket_id' => $relatedId,
                        ]);
                    }
                }
                $orderableEtiketCodes[] = $etiketCode;
                $existingOrderable[] = $etiketCode;
                $created++;
            }
        }

        $message = "تعداد {$created} اتیکت با موفقیت ایجاد شد";
        if ($skipped > 0) {
            $message .= " و {$skipped} اتیکت رد شد (تکراری یا خالی)";
        }
        return redirect()->route('etikets.add_to_product')->with('success', $message);
    }

    /**
     * Show the افزودن ویژگی به اتیکت form.
     */
    public function addAttributeForm()
    {
        return view('admin.etikets.add_attribute');
    }

    /**
     * Save attribute values for a specific etiket.
     */
    public function storeAttributeToEtiket(Request $request)
    {
        $request->validate([
            'etiket_id'  => 'required|exists:etikets,id',
            'attributes' => 'nullable|array',
            'attributes.*.attribute_id' => 'required|exists:attributes,id',
            'attributes.*.value'        => 'nullable|string|max:255',
        ]);

        $etiket = Etiket::findOrFail($request->etiket_id);

        foreach ($request->get('attributes', []) as $attr) {
            $value = trim($attr['value'] ?? '');
            if ($value === '') {
                AttributeValue::where('etiket_id', $etiket->id)
                    ->where('attribute_id', $attr['attribute_id'])
                    ->delete();
            } else {
                AttributeValue::updateOrCreate(
                    ['etiket_id' => $etiket->id, 'attribute_id' => $attr['attribute_id']],
                    ['value' => $value]
                );
            }
        }

        return redirect()->route('etikets.add_attribute')
            ->with('success', 'ویژگی‌های اتیکت ' . $etiket->code . ' با موفقیت ذخیره شد.');
    }

    /**
     * AJAX: search etikets by code or name for select2.
     */
    public function ajaxSearch(Request $request)
    {
        $q = $request->input('q', '');

        $etikets = Etiket::with('product')
            ->where(function ($query) use ($q) {
                $query->where('code', 'like', "%{$q}%")
                      ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$q}%"));
            })
            ->whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->limit(30)
            ->get();

        $results = $etikets->map(fn($e) => [
            'id'   => $e->id,
            'text' => $e->code . ($e->product ? ' — ' . $e->product->name : ''),
        ]);

        return response()->json(['results' => $results]);
    }

    /**
     * AJAX: return attribute inputs for a given etiket (from its product's categories;
     * if the product has no categories but has a parent, use the parent's categories).
     */
    public function etiketAttributeData(int $id)
    {
        $etiket = Etiket::with([
            'product.categories.attributeGroups.attributes',
            'product.parent.categories.attributeGroups.attributes',
        ])->findOrFail($id);

        $product = $etiket->product;
        if (! $product) {
            return response()->json(['attributes' => [], 'attributeValues' => []]);
        }

        $categories = $product->categories;
        if ($categories->isEmpty() && $product->parent_id) {
            $product->loadMissing(['parent.categories.attributeGroups.attributes']);
            $categories = $product->parent?->categories ?? collect();
        }

        $attributeIds = [];
        foreach ($categories as $category) {
            foreach ($category->attributeGroups as $group) {
                foreach ($group->attributes as $attribute) {
                    $attributeIds[$attribute->id] = $attribute;
                }
            }
        }

        $existingValues = AttributeValue::where('etiket_id', $etiket->id)
            ->whereIn('attribute_id', array_keys($attributeIds))
            ->get()
            ->keyBy('attribute_id');

        $attributes = collect($attributeIds)->values()->map(fn($attr) => [
            'id'               => $attr->id,
            'name'             => $attr->name,
            'prefix_sentence'  => $attr->prefix_sentence,
            'postfix_sentence' => $attr->postfix_sentence,
            'value'            => $existingValues->get($attr->id)?->value ?? '',
        ]);

        return response()->json([
            'etiket'          => ['id' => $etiket->id, 'code' => $etiket->code],
            'product'         => ['id' => $product->id, 'name' => $product->name],
            'attributes'      => $attributes,
        ]);
    }

    /**
     * Return next etiket number(s) for add-to-product form (from DB max + 1, plus optional pending).
     * Query params: pending_regular=0, pending_orderable=0
     */
    public function nextEtiketNumbers(Request $request)
    {
        $pendingRegular = (int) $request->get('pending_regular', 0);
        $pendingOrderable = (int) $request->get('pending_orderable', 0);
        return response()->json([
            'regular_start' => $this->getNextEtiketNumber('', $pendingRegular),
            'orderable_start' => $this->getNextEtiketNumber('s', $pendingOrderable),
        ]);
    }

    /**
     * Get next starting number for etiket codes. Uses latest etiket id + 1 so numbers follow id (e.g. 15474 after id 15473).
     * @param string $prefix '' for regular numeric, 's' for orderable (s-XXXX)
     * @param int $pending Number of cards already added in form (offset)
     */
    private function getNextEtiketNumber(string $prefix, int $pending = 0): int
    {
        $startNumber = 7000;
        $maxId = (int) Etiket::max('id');
        $nextFromId = $maxId + 1 + $pending;
        return max($nextFromId, $startNumber);
    }

    /**
     * Generate unique etiket code in format: {prefix}-{number} or just {number}
     * @param array $existingCodes Existing codes in current batch
     * @param string $prefix Code prefix ('' for regular numeric, 's' for orderable)
     */
    private function generateUniqueEtiketCode(array $existingCodes = [], string $prefix = ''): string
    {
        $startNumber = 7000;
        $highestNumber = $startNumber - 1;
        
        // Find the highest existing code number in database
        if ($prefix) {
            // For prefixed codes (like s-7000)
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
        } else {
            // For non-prefixed codes (just numbers like 7000)
            $etikets = Etiket::whereRaw('code REGEXP \'^[0-9]+$\'')->get();
            foreach ($etikets as $etiket) {
                if (preg_match('/^(\d+)$/', $etiket->code, $matches)) {
                    $codeNumber = (int)$matches[1];
                    if ($codeNumber >= $highestNumber) {
                        $highestNumber = $codeNumber;
                    }
                }
            }
        }
        
        // Check for codes in current batch and find the highest
        foreach ($existingCodes as $code) {
            if ($prefix) {
                $pattern = '/^' . preg_quote($prefix, '/') . '-(\d+)$/';
                if (preg_match($pattern, $code, $matches)) {
                    $codeNumber = (int)$matches[1];
                    if ($codeNumber >= $highestNumber) {
                        $highestNumber = $codeNumber;
                    }
                }
            } else {
                if (preg_match('/^(\d+)$/', $code, $matches)) {
                    $codeNumber = (int)$matches[1];
                    if ($codeNumber >= $highestNumber) {
                        $highestNumber = $codeNumber;
                    }
                }
            }
        }
        
        // Use latest etiket id + 1 so new codes follow id (e.g. 15474 after id 15473); avoid using stray high code values
        $maxId = (int) Etiket::max('id');
        $nextFromId = $maxId + 1;
        $nextFromCodes = max($highestNumber + 1, $startNumber);
        // If code-based next is unreasonably high (e.g. 33333333335), use id-based next instead
        $nextNumber = $nextFromCodes <= $nextFromId ? $nextFromCodes : $nextFromId;
        return $prefix ? $prefix . '-' . $nextNumber : (string)$nextNumber;
    }

    /**
     * Calculate etiket price based on product and weight
     */
    private function calculateEtiketPrice(Product $product, float $weight): float
    {
        $goldPrice = (float) setting('gold_price') ?? 0;
        $ojrat = $product->ojrat ?? 0;
        
        if ($weight > 0 && $goldPrice > 0 && $ojrat > 0) {
            // Formula: price = weight * (goldPrice * 1.01) * (1 + (ojrat / 100))
            $adjustedGoldPrice = $goldPrice * 1.01;
            $calculatedPrice = $weight * $adjustedGoldPrice * (1 + ($ojrat / 100));
            
            // Round down to nearest thousand (last three digits become 0)
            return floor($calculatedPrice / 1000) * 1000;
        }
        
        return 0;
    }

    /**
     * Bulk update etikets
     */
    public function bulkUpdate(Request $request)
    {
        if (is_string($request->etiket_ids)) {
            $etiketIds = json_decode($request->etiket_ids, true);
            $request->merge(['etiket_ids' => $etiketIds]);
        }

        $request->validate([
            'etiket_ids' => 'required|array',
            'etiket_ids.*' => 'exists:etikets,id',
            'name' => 'nullable|string|max:255',
            'product_id' => 'nullable|integer|exists:products,id',
            'ojrat' => 'nullable|string',
            'darsad_kharid' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0',
        ]);

        $etikets = Etiket::whereIn('id', $request->etiket_ids)->get();
        $updated = 0;

        foreach ($etikets as $etiket) {
            $updateData = [];
            $hasUpdates = false;
            
            if ($request->filled('name')) {
                $updateData['name'] = $request->name;
                $hasUpdates = true;
            }
            
            // Update product_id if it's provided and has a valid value
            // This allows assigning product_id to etikets that don't have one (product_id = null)
            if ($request->has('product_id') && $request->product_id !== null && $request->product_id !== '') {
                $productId = (int) $request->input('product_id');
                // Only update if product_id is a valid positive integer
                if ($productId > 0) {
                    // Force update by explicitly setting the attribute
                    $etiket->product_id = $productId;
                    $hasUpdates = true;
                }
            }
            
            if ($request->filled('ojrat')) {
                $updateData['ojrat'] = $request->ojrat;
                $hasUpdates = true;
            }
            
            if ($request->filled('darsad_kharid')) {
                $updateData['darsad_kharid'] = $request->darsad_kharid;
                $hasUpdates = true;
            }
            
            if ($request->filled('weight')) {
                $updateData['weight'] = $request->weight;
                $hasUpdates = true;
            }
            
            // Force update - use save() to ensure product_id is updated even if it's the only change
            if ($hasUpdates) {
                if (!empty($updateData)) {
                    $etiket->fill($updateData);
                }
                // Save will update product_id if it was set above, and any other fields in updateData
                $etiket->save();
                $updated++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "تعداد {$updated} اتیکت با موفقیت به‌روزرسانی شد",
            'updated' => $updated
        ]);
    }

    /**
     * Bulk update etikets for selected products
     */
    public function bulkUpdateForProducts(Request $request)
    {
        if (is_string($request->product_ids)) {
            $productIds = json_decode($request->product_ids, true);
            $request->merge(['product_ids' => $productIds]);
        }

        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'ojrat' => 'nullable|string',
            'darsad_kharid' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0',
        ]);

        $products = Product::whereIn('id', $request->product_ids)->get();
        $updated = 0;
        $totalEtikets = 0;

        foreach ($products as $product) {
            $etikets = $product->etikets;
            $totalEtikets += $etikets->count();
            
            foreach ($etikets as $etiket) {
                $updateData = [];
                
                if ($request->filled('ojrat')) {
                    $updateData['ojrat'] = $request->ojrat;
                }
                
                if ($request->filled('darsad_kharid')) {
                    $updateData['darsad_kharid'] = $request->darsad_kharid;
                }
                
                if ($request->filled('weight')) {
                    $updateData['weight'] = $request->weight;
                }
                
                if (!empty($updateData)) {
                    $etiket->update($updateData);
                    $updated++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "تعداد {$updated} اتیکت از {$totalEtikets} اتیکت با موفقیت به‌روزرسانی شد",
            'updated' => $updated,
            'total' => $totalEtikets
        ]);
    }

    /**
     * Bulk delete etikets (soft delete)
     */
    public function bulkDelete(Request $request)
    {
        if (is_string($request->etiket_ids)) {
            $etiketIds = json_decode($request->etiket_ids, true);
            $request->merge(['etiket_ids' => $etiketIds]);
        }

        $request->validate([
            'etiket_ids' => 'required|array',
            'etiket_ids.*' => 'exists:etikets,id',
        ]);

        // Soft delete etikets
        $deleted = Etiket::whereIn('id', $request->etiket_ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "تعداد {$deleted} اتیکت با موفقیت حذف شد",
            'deleted' => $deleted
        ]);
    }

    /**
     * Bulk restore deleted etikets
     */
    public function bulkRestore(Request $request)
    {
        if (is_string($request->etiket_ids)) {
            $etiketIds = json_decode($request->etiket_ids, true);
            $request->merge(['etiket_ids' => $etiketIds]);
        }

        $request->validate([
            'etiket_ids' => 'required|array',
            'etiket_ids.*' => 'integer',
        ]);

        // Restore soft deleted etikets
        $restored = Etiket::onlyTrashed()
            ->whereIn('id', $request->etiket_ids)
            ->restore();

        return response()->json([
            'success' => true,
            'message' => "تعداد {$restored} اتیکت با موفقیت بازیابی شد",
            'restored' => $restored
        ]);
    }

    /**
     * Bulk force delete etikets (permanent delete)
     */
    public function bulkForceDelete(Request $request)
    {
        if (is_string($request->etiket_ids)) {
            $etiketIds = json_decode($request->etiket_ids, true);
            $request->merge(['etiket_ids' => $etiketIds]);
        }

        $request->validate([
            'etiket_ids' => 'required|array',
            'etiket_ids.*' => 'integer',
        ]);

        // Permanently delete etikets
        $deleted = Etiket::onlyTrashed()
            ->whereIn('id', $request->etiket_ids)
            ->forceDelete();

        return response()->json([
            'success' => true,
            'message' => "تعداد {$deleted} اتیکت به صورت دائمی حذف شد",
            'deleted' => $deleted
        ]);
    }

    /**
     * Restore a single deleted etiket
     */
    public function restore(string $id)
    {
        try {
            $etiket = Etiket::onlyTrashed()->findOrFail($id);
            $etiket->restore();
            
            return response()->json([
                'success' => true,
                'message' => 'اتیکت با موفقیت بازیابی شد'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در بازیابی اتیکت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Permanently delete a single etiket
     */
    public function forceDelete(string $id)
    {
        try {
            $etiket = Etiket::onlyTrashed()->findOrFail($id);
            $etiket->forceDelete();
            
            return response()->json([
                'success' => true,
                'message' => 'اتیکت به صورت دائمی حذف شد'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف دائمی اتیکت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export etikets to Excel
     */
    public function export(Request $request)
    {
        $filters = [
            'is_mojood' => $request->get('is_mojood'),
            'name' => $request->get('name'),
            'code' => $request->get('code'),
            'weight' => $request->get('weight'),
            'weight_from' => $request->get('weight_from'),
            'weight_to' => $request->get('weight_to'),
            'category_ids' => $request->get('category_ids'),
        ];

        // Get sorting parameters from request (same as DataTable)
        $sortColumn = $request->get('sort_column');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        $filters['sort_column'] = $sortColumn;
        $filters['sort_direction'] = $sortDirection;

        $fileName = 'etikets_' . date('Y-m-d_H-i-s') . '.xlsx';
        if (isset($filters['is_mojood']) && $filters['is_mojood'] == 1) {
            $fileName = 'etikets_available_' . date('Y-m-d_H-i-s') . '.xlsx';
        } elseif (isset($filters['is_mojood']) && $filters['is_mojood'] == 0) {
            $fileName = 'etikets_not_available_' . date('Y-m-d_H-i-s') . '.xlsx';
        }

        return Excel::download(
            new EtiketsExport($filters), 
            $fileName
        );
    }
}
