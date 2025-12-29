<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Table\AdminEtiketResource;
use App\Models\Category;
use App\Models\Etiket;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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

        // Apply sorting if provided
        if ($request->has('order') && !empty($request->input('order'))) {
            $order = $request->input('order')[0];
            $columnIndex = $order['column'];
            $direction = $order['dir'] === 'asc' ? 'asc' : 'desc';
            $column = $request->input("columns.{$columnIndex}.data");
            if ($column && Schema::hasColumn('etikets', $column)) {
                $query->orderBy('etikets.' . $column, $direction);
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

    public function storeForProduct(Request $request, Product $product)
    {
        $request->validate([
            'etikets' => 'required|array|min:1',
            'etikets.*.code' => 'required|string|max:255',
        ]);

        $etiketCodes = [];
        $created = 0;
        $skipped = 0;

        foreach ($request->etikets as $etiketData) {
            $code = trim($etiketData['code'] ?? '');
            
            if (empty($code)) {
                $skipped++;
                continue;
            }
            
            // Check for duplicate in current batch
            if (in_array($code, $etiketCodes)) {
                $skipped++;
                continue;
            }
            
            // Check for duplicate in database
            $existingEtiket = Etiket::where('code', $code)->first();
            if ($existingEtiket) {
                $skipped++;
                continue;
            }
            
            // Create etiket with product details
            Etiket::create([
                'code' => $code,
                'name' => $product->name,
                'weight' => $product->weight ?? 0,
                'price' => $product->getRawOriginal('price'),
                'product_id' => $product->id,
                'ojrat' => $product->ojrat ?? null,
                'darsad_kharid' => $product->darsad_kharid ?? null,
                'is_mojood' => 1,
            ]);
            
            $etiketCodes[] = $code;
            $created++;
        }
        
        return response()->json([
            'success' => true,
            'message' => "تعداد {$created} اتیکت با موفقیت ایجاد شد" . ($skipped > 0 ? " و {$skipped} اتیکت رد شد (تکراری یا خالی)" : ''),
            'created' => $created,
            'skipped' => $skipped
        ]);
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
            'ojrat' => 'nullable|string',
            'darsad_kharid' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0',
        ]);

        $etikets = Etiket::whereIn('id', $request->etiket_ids)->get();
        $updated = 0;

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
}
