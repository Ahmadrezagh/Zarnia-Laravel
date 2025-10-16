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
        // Get successful orders with their items
        $orders = Order::with(['orderItems.product'])
            ->whereIn('status', ['paid', 'boxing', 'sent', 'post', 'completed'])
            ->orderBy('created_at', 'asc')
            ->paginate(25);

        // Calculate summary data
        $allOrders = Order::with(['orderItems.product'])
            ->whereIn('status', ['paid', 'boxing', 'sent', 'post', 'completed'])
            ->get();

        $totalWeight = 0;
        $totalAmount = 0;
        $totalPurchasePercentageWeight = 0;
        $totalSalePercentageWeight = 0;

        // Calculate totals from ALL orders
        foreach ($allOrders as $order) {
            foreach ($order->orderItems as $item) {
                if ($item->product) {
                    $itemWeight = floatval($item->product->weight ?? 0) * intval($item->count);
                    $itemAmount = floatval($item->price) * intval($item->count);
                    
                    $darsadKharid = floatval($item->product->darsad_kharid ?? 0);
                    $purchaseCommissionGrams = ($itemWeight * $darsadKharid) / 100;
                    
                    $ojrat = floatval($item->product->ojrat ?? 0);
                    
                    $totalWeight += $itemWeight;
                    $totalAmount += $itemAmount;
                    $totalPurchasePercentageWeight += $purchaseCommissionGrams;
                    $totalSalePercentageWeight += ($itemWeight * $ojrat) / 100;
                }
            }
        }

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
            $data[] = [
                'id' => $order->id,
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
}

