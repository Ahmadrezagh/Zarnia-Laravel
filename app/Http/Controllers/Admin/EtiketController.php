<?php

namespace App\Http\Controllers\Admin;

use App\Exports\EtiketsExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Table\AdminEtiketResource;
use App\Models\Category;
use App\Models\Etiket;
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
     * Get etikets table data for DataTables
     */
    public function table(Request $request)
    {
        $query = Etiket::query()->with('product.categories')->select('etikets.*');
        
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
            $query->where('etikets.name', 'like', '%' . $request->input('name') . '%');
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
                $q->where('etikets.name', 'like', "%{$search}%")
                    ->orWhere('etikets.code', 'like', "%{$search}%");
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
                    // Sort by numeric part of code (extract numbers after removing prefixes like s- or zr-)
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
                    $query->orderBy('etikets.name', $sortDirection);
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

        // Get existing zr- codes to avoid duplicates
        $existingZrCodes = Etiket::where('code', 'like', 'zr-%')->pluck('code')->toArray();

        foreach ($request->etikets as $etiketData) {
            $count = (int)($etiketData['count'] ?? 1);
            $weight = (float)($etiketData['weight'] ?? 0);
            
            if ($count <= 0 || $weight <= 0) {
                $skipped += $count;
                continue;
            }
            
            // Generate unique zr- codes for each etiket
            for ($i = 0; $i < $count; $i++) {
                $etiketCode = $this->generateUniqueEtiketCode(
                    array_merge($etiketCodes, $existingZrCodes),
                    'zr'
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
     * Generate unique etiket code in format: {prefix}-{number}
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
        
        // Generate next code
        $nextNumber = max($highestNumber + 1, $startNumber);
        return $prefix . '-' . $nextNumber;
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
     * Bulk delete etikets
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

        $deleted = Etiket::whereIn('id', $request->etiket_ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "تعداد {$deleted} اتیکت با موفقیت حذف شد",
            'deleted' => $deleted
        ]);
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
