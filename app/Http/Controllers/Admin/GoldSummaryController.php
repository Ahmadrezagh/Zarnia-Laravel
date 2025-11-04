<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoldSummaryController extends Controller
{
    /**
     * Display gold sales summary
     */
    public function index(Request $request)
    {
        // Build base query with date filters
        $query = Order::with(['orderItems.product'])
            ->whereIn('status', ['paid', 'boxing', 'sent', 'post', 'completed']);

        // Apply date filters if provided
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        // Get paginated orders
        $orders = $query->latest()->paginate();

        // Calculate summary data for current page only
        $pageWeight = 0;
        $pageAmount = 0;
        $pagePurchasePercentageWeight = 0;
        $pageSalePercentageWeight = 0;

        // Calculate totals from current page orders
        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                if ($item->product) {
                    $itemWeight = floatval($item->product->weight ?? 0) * intval($item->count);
                    $itemAmount = floatval($item->price) * intval($item->count);
                    
                    $darsadKharid = floatval($item->product->darsad_kharid ?? 0);
                    $purchaseCommissionGrams = ($itemWeight * $darsadKharid) / 100;
                    
                    $ojrat = floatval($item->product->ojrat ?? 0);
                    
                    $pageWeight += $itemWeight;
                    $pageAmount += $itemAmount;
                    $pagePurchasePercentageWeight += $purchaseCommissionGrams;
                    $pageSalePercentageWeight += ($itemWeight * $ojrat) / 100;
                }
            }
        }

        // Calculate overall summary using database aggregation for efficiency
        $overallStatsQuery = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('orders.status', ['paid', 'boxing', 'sent', 'post', 'completed']);

        // Apply date filters to overall stats query
        if ($request->filled('from_date')) {
            $overallStatsQuery->where('orders.created_at', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $overallStatsQuery->where('orders.created_at', '<=', $request->to_date);
        }

        $overallStats = $overallStatsQuery
            ->selectRaw('
                SUM(products.weight * order_items.count) as total_weight,
                SUM(order_items.price * order_items.count) as total_amount,
                SUM((products.weight * order_items.count * products.darsad_kharid) / 100) as total_purchase_commission,
                SUM((products.weight * order_items.count * products.ojrat) / 100) as total_sale_commission
            ')
            ->first();

        $totalWeight = floatval($overallStats->total_weight ?? 0);
        $totalAmount = floatval($overallStats->total_amount ?? 0);
        $totalPurchasePercentageWeight = floatval($overallStats->total_purchase_commission ?? 0);
        $totalSalePercentageWeight = floatval($overallStats->total_sale_commission ?? 0);

        // Calculate overall averages
        $avgPurchasePercentage = $totalWeight > 0 ? ($totalPurchasePercentageWeight / $totalWeight) * 100 : 0;
        $avgSalePercentage = $totalWeight > 0 ? ($totalSalePercentageWeight / $totalWeight) * 100 : 0;
        $percentageDifference = $avgSalePercentage - $avgPurchasePercentage;

        $summary = [
            'total_weight' => number_format($totalWeight, 3),
            'total_amount' => number_format($totalAmount),
            'avg_purchase_percentage' => number_format($avgPurchasePercentage, 2),
            'avg_sale_percentage' => number_format($avgSalePercentage, 2),
            'percentage_difference' => number_format($percentageDifference, 2),
            'total_purchase_commission' => number_format($totalPurchasePercentageWeight, 3),
            'total_sale_commission' => number_format($totalSalePercentageWeight, 3),
            // Page-specific data
            'page_weight' => number_format($pageWeight, 3),
            'page_amount' => number_format($pageAmount),
            'page_purchase_commission' => number_format($pagePurchasePercentageWeight, 3),
            'page_sale_commission' => number_format($pageSalePercentageWeight, 3),
        ];

        return view('admin.gold_summary.index', compact('orders', 'summary'));
    }

    /**
     * Get gold summary data for DataTables
     */
    public function table(Request $request)
    {
        // Get successful orders with their items
        $query = Order::with(['orderItems.product'])
            ->whereIn('status', ['paid', 'boxing', 'sent', 'post', 'completed'])
            ->orderBy('created_at', 'asc');

        // Get total count before pagination
        $totalRecords = $query->count();
        
        \Log::info('Gold Summary - Total successful orders:', ['count' => $totalRecords]);

        // Get ALL orders for summary calculations (not paginated)
        $allOrders = $query->get();

        // Handle pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);

        $orders = Order::with(['orderItems.product'])
            ->whereIn('status', ['paid', 'boxing', 'sent', 'post', 'completed'])
            ->orderBy('created_at', 'asc')
            ->skip($start)
            ->take($length)
            ->get();

        // Calculate summary data for paginated results
        $data = [];
        $cumulativeAmount = 0;
        
        // Calculate totals from ALL orders for summary cards
        $totalWeight = 0;
        $totalAmount = 0;
        $totalPurchasePercentageWeight = 0; // Sum of (weight * purchase_percentage)
        $totalSalePercentageWeight = 0;     // Sum of (weight * sale_percentage)

        // First, calculate totals from ALL orders for summary cards
        foreach ($allOrders as $order) {
            foreach ($order->orderItems as $item) {
                if ($item->product) {
                    $itemWeight = floatval($item->product->weight ?? 0) * intval($item->count);
                    $itemAmount = floatval($item->price) * intval($item->count);
                    
                    // Calculate purchase commission in grams
                    $darsadKharid = floatval($item->product->darsad_kharid ?? 0);
                    $purchaseCommissionGrams = ($itemWeight * $darsadKharid) / 100;
                    
                    // Calculate sale commission percentage (ojrat)
                    $ojrat = floatval($item->product->ojrat ?? 0);
                    
                    $totalWeight += $itemWeight;
                    $totalAmount += $itemAmount;
                    $totalPurchasePercentageWeight += $purchaseCommissionGrams;
                    $totalSalePercentageWeight += ($itemWeight * $ojrat) / 100;
                }
            }
        }

        // Now process paginated orders for table display
        \Log::info('Gold Summary - Processing paginated orders:', ['count' => count($orders)]);
        
        foreach ($orders as $order) {
            $orderWeight = 0;
            $orderAmount = 0;
            $orderPurchaseWeightSum = 0;
            $orderSaleWeightSum = 0;

            foreach ($order->orderItems as $item) {
                \Log::info('Gold Summary - Processing item:', [
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'has_product' => !is_null($item->product),
                    'product_weight' => $item->product ? $item->product->weight : null
                ]);
                
                if ($item->product) {
                    $itemWeight = floatval($item->product->weight ?? 0) * intval($item->count);
                    $itemAmount = floatval($item->price) * intval($item->count);
                    
                    // Calculate purchase commission in grams
                    $darsadKharid = floatval($item->product->darsad_kharid ?? 0);
                    $purchaseCommissionGrams = ($itemWeight * $darsadKharid) / 100;
                    
                    // Calculate sale commission percentage (ojrat)
                    $ojrat = floatval($item->product->ojrat ?? 0);
                    
                    $orderWeight += $itemWeight;
                    $orderAmount += $itemAmount;
                    $orderPurchaseWeightSum += $purchaseCommissionGrams;
                    $orderSaleWeightSum += ($itemWeight * $ojrat) / 100;
                }
            }

            $cumulativeAmount += $orderAmount;
            
            \Log::info('Gold Summary - Order processed:', [
                'order_id' => $order->id,
                'weight' => $orderWeight,
                'amount' => $orderAmount,
                'items_count' => $order->orderItems->count()
            ]);

            // Always add row even if amounts are 0
            $detailUrl = route('gold_summary.show', $order->id);
            $data[] = [
                'id' => '<a href="' . $detailUrl . '" target="_blank" style="cursor: pointer; color: #007bff;">' . $order->id . '</a>',
                'transaction_id' => $order->transaction_id ?? '',
                'date' => $order->createdAtJalali ?? '',
                'weight' => number_format($orderWeight, 3) . ' گرم',
                'amount' => number_format($orderAmount) . ' تومان',
                'cumulative_amount' => number_format($cumulativeAmount) . ' تومان',
                'purchase_commission_grams' => number_format($orderPurchaseWeightSum, 3) . ' گرم',
                'sale_commission_grams' => number_format($orderSaleWeightSum, 3) . ' گرم',
            ];
        }
        
        \Log::info('Gold Summary - Final data count:', ['count' => count($data)]);

        // Calculate overall averages from ALL orders
        $avgPurchasePercentage = $totalWeight > 0 ? ($totalPurchasePercentageWeight / $totalWeight) * 100 : 0;
        $avgSalePercentage = $totalWeight > 0 ? ($totalSalePercentageWeight / $totalWeight) * 100 : 0;
        $percentageDifference = $avgSalePercentage - $avgPurchasePercentage;

        // Summary statistics (from ALL orders, not just paginated)
        $summary = [
            'total_weight' => number_format($totalWeight, 3) . ' گرم',
            'total_amount' => number_format($totalAmount) . ' تومان',
            'avg_purchase_percentage' => number_format($avgPurchasePercentage, 2) . '%',
            'avg_sale_percentage' => number_format($avgSalePercentage, 2) . '%',
            'percentage_difference' => number_format($percentageDifference, 2) . '%',
            'total_purchase_commission' => number_format($totalPurchasePercentageWeight, 3) . ' گرم',
            'total_sale_commission' => number_format($totalSalePercentageWeight, 3) . ' گرم',
        ];

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
            'summary' => $summary
        ]);
    }

    /**
     * Show order calculation details
     */
    public function show(Order $order)
    {
        // Ensure order has valid status
        if (!in_array($order->status, ['paid', 'boxing', 'sent', 'post', 'completed'])) {
            abort(404, 'Order not found or status not valid for gold summary');
        }

        // Load order with items and products
        $order->load(['orderItems.product', 'user', 'address']);

        // Calculate details for each order item
        $items = [];
        $totalWeight = 0;
        $totalAmount = 0;
        $totalPurchaseCommission = 0;
        $totalSaleCommission = 0;

        foreach ($order->orderItems as $item) {
            if ($item->product) {
                $itemWeight = floatval($item->product->weight ?? 0) * intval($item->count);
                $itemAmount = floatval($item->price) * intval($item->count);
                
                $darsadKharid = floatval($item->product->darsad_kharid ?? 0);
                $purchaseCommissionGrams = ($itemWeight * $darsadKharid) / 100;
                
                $ojrat = floatval($item->product->ojrat ?? 0);
                $saleCommissionGrams = ($itemWeight * $ojrat) / 100;

                $items[] = [
                    'product_name' => $item->name,
                    'product_id' => $item->product_id,
                    'count' => $item->count,
                    'unit_weight' => floatval($item->product->weight ?? 0),
                    'total_weight' => $itemWeight,
                    'unit_price' => floatval($item->price),
                    'total_amount' => $itemAmount,
                    'purchase_percentage' => $darsadKharid,
                    'purchase_commission_grams' => $purchaseCommissionGrams,
                    'sale_percentage' => $ojrat,
                    'sale_commission_grams' => $saleCommissionGrams,
                ];

                $totalWeight += $itemWeight;
                $totalAmount += $itemAmount;
                $totalPurchaseCommission += $purchaseCommissionGrams;
                $totalSaleCommission += $saleCommissionGrams;
            }
        }

        // Calculate averages
        $avgPurchasePercentage = $totalWeight > 0 ? ($totalPurchaseCommission / $totalWeight) * 100 : 0;
        $avgSalePercentage = $totalWeight > 0 ? ($totalSaleCommission / $totalWeight) * 100 : 0;

        $summary = [
            'total_weight' => $totalWeight,
            'total_amount' => $totalAmount,
            'total_purchase_commission' => $totalPurchaseCommission,
            'total_sale_commission' => $totalSaleCommission,
            'avg_purchase_percentage' => $avgPurchasePercentage,
            'avg_sale_percentage' => $avgSalePercentage,
        ];

        return view('admin.gold_summary.show', compact('order', 'items', 'summary'));
    }
}

