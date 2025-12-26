<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Etiket;
use App\Models\Product;
use Illuminate\Http\Request;

class EtiketController extends Controller
{
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
}
