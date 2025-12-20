<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoldSummaryController extends Controller
{
    // Valid order statuses for gold summary calculations
    private const VALID_STATUSES = ['paid', 'boxing', 'sent', 'post', 'completed'];

    /**
     * Display gold sales summary
     */
    public function index(Request $request)
    {
        // Build base query with date filters
        $query = Order::with(['orderItems.product', 'gateway'])
            ->whereIn('status', self::VALID_STATUSES);

        // Apply date filters if provided
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        // Get ALL filtered orders (not paginated) for summary calculations
        $allFilteredOrders = $query->get();

        // Get paginated orders for table display
        $orders = $query->latest()->paginate();

        // Calculate summary data from ALL filtered orders (matching the filters)
        $totalWeight = 0;
        $totalAmount = 0;
        $snappTotalAmount = 0;
        $snappTotalWeight = 0;
        $totalPurchasePercentageWeight = 0;
        $totalSalePercentageWeight = 0;
        $totalDiscount = 0;
        $totalDiscountPerGram = 0;

        // Calculate totals from ALL filtered orders
        foreach ($allFilteredOrders as $order) {
            $orderTotalDiscount = floatval($order->discount_price ?? 0);
            $firstMazaneh = null;
            
            foreach ($order->orderItems as $item) {
                if ($item->product) {
                    $itemWeight = floatval($item->product->weight ?? 0) * intval($item->count);
                    $itemAmount = floatval($item->price) * intval($item->count);
                    
                    $darsadKharid = floatval($item->product->darsad_kharid ?? 0);
                    $purchaseCommissionGrams = ($itemWeight * $darsadKharid) / 100;
                    
                    $ojrat = floatval($item->product->ojrat ?? 0);
                    
                    // Calculate discount: price - discounted_price
                    $originalPrice = floatval($item->product->getRawOriginal('price') ?? 0) / 10;
                    $discountedPrice = floatval($item->product->discounted_price ?? 0);
                    if ($discountedPrice > 0 && $originalPrice > 0) {
                        $productDiscountAmount = ($originalPrice - $discountedPrice) * intval($item->count);
                        $orderTotalDiscount += $productDiscountAmount;
                    }
                    
                    // Find first mazaneh that is not 0
                    if ($firstMazaneh === null && $item->product->mazaneh && floatval($item->product->mazaneh) != 0) {
                        $firstMazaneh = floatval($item->product->mazaneh);
                    }
                    
                    $totalWeight += $itemWeight;
                    $totalAmount += $itemAmount;
                    if($order->gateway && $order->gateway->key == 'snapp'){
                        $snappTotalAmount += $itemAmount;
                        $snappTotalWeight += $itemWeight;
                    }
                    $totalPurchasePercentageWeight += $purchaseCommissionGrams;
                    $totalSalePercentageWeight += ($itemWeight * $ojrat) / 100;
                }
            }
            
            // Calculate discount per gram for this order
            $orderDiscountPerGram = 0;
            if ($firstMazaneh !== null && $firstMazaneh > 0) {
                $orderDiscountPerGram = ($orderTotalDiscount / $firstMazaneh) * 10;
            }
            
            $totalDiscount += $orderTotalDiscount;
            $totalDiscountPerGram += $orderDiscountPerGram;
        }

        // Calculate page-specific data (for footer comparison)
        $pageWeight = 0;
        $pageAmount = 0;
        $pagePurchasePercentageWeight = 0;
        $pageSalePercentageWeight = 0;
        $pageTotalDiscount = 0;
        $pageTotalDiscountPerGram = 0;

        // Calculate totals from current page orders only
        foreach ($orders as $order) {
            $orderTotalDiscount = floatval($order->discount_price ?? 0);
            $firstMazaneh = null;
            
            foreach ($order->orderItems as $item) {
                if ($item->product) {
                    $itemWeight = floatval($item->product->weight ?? 0) * intval($item->count);
                    $itemAmount = floatval($item->price) * intval($item->count);
                    
                    $darsadKharid = floatval($item->product->darsad_kharid ?? 0);
                    $purchaseCommissionGrams = ($itemWeight * $darsadKharid) / 100;
                    
                    $ojrat = floatval($item->product->ojrat ?? 0);
                    
                    // Calculate discount: price - discounted_price
                    $originalPrice = floatval($item->product->getRawOriginal('price') ?? 0) / 10;
                    $discountedPrice = floatval($item->product->discounted_price ?? 0);
                    if ($discountedPrice > 0 && $originalPrice > 0) {
                        $productDiscountAmount = ($originalPrice - $discountedPrice) * intval($item->count);
                        $orderTotalDiscount += $productDiscountAmount;
                    }
                    
                    // Find first mazaneh that is not 0
                    if ($firstMazaneh === null && $item->product->mazaneh && floatval($item->product->mazaneh) != 0) {
                        $firstMazaneh = floatval($item->product->mazaneh);
                    }
                    
                    $pageWeight += $itemWeight;
                    $pageAmount += $itemAmount;
                    $pagePurchasePercentageWeight += $purchaseCommissionGrams;
                    $pageSalePercentageWeight += ($itemWeight * $ojrat) / 100;
                }
            }
            
            // Calculate discount per gram for this order
            $orderDiscountPerGram = 0;
            if ($firstMazaneh !== null && $firstMazaneh > 0) {
                $orderDiscountPerGram = ($orderTotalDiscount / $firstMazaneh) * 10;
            }
            
            $pageTotalDiscount += $orderTotalDiscount;
            $pageTotalDiscountPerGram += $orderDiscountPerGram;
        }

        // Calculate overall averages from ALL filtered orders
        $avgPurchasePercentage = $totalWeight > 0 ? ($totalPurchasePercentageWeight / $totalWeight) * 100 : 0;
        $avgSalePercentage = $totalWeight > 0 ? ($totalSalePercentageWeight / $totalWeight) * 100 : 0;
        $percentageDifference = $avgSalePercentage - $avgPurchasePercentage;

        $summary = [
            // Overall totals from ALL filtered orders (matching date/status filters)
            'total_weight' => number_format($totalWeight, 3) . ' گرم',
            'total_amount' => number_format($totalAmount) . ' تومان',
            'snapp_total_amount' => number_format($snappTotalAmount) . ' تومان',
            'snapp_total_weight' => number_format($snappTotalWeight, 3) . ' گرم',
            'avg_purchase_percentage' => number_format($avgPurchasePercentage, 2) . '%',
            'avg_sale_percentage' => number_format($avgSalePercentage, 2) . '%',
            'percentage_difference' => number_format($percentageDifference, 2) . '%',
            'total_purchase_commission' => number_format($totalPurchasePercentageWeight, 3) . ' گرم',
            'total_sale_commission' => number_format($totalSalePercentageWeight, 3) . ' گرم',
            'total_discount' => number_format($totalDiscount) . ' تومان',
            'total_discount_per_gram' => number_format($totalDiscountPerGram, 3),
            // Page-specific data (for footer comparison)
            'page_weight' => number_format($pageWeight, 3),
            'page_amount' => number_format($pageAmount),
            'page_purchase_commission' => number_format($pagePurchasePercentageWeight, 3),
            'page_sale_commission' => number_format($pageSalePercentageWeight, 3),
            'page_total_discount' => number_format($pageTotalDiscount) . ' تومان',
            'page_total_discount_per_gram' => number_format($pageTotalDiscountPerGram, 3),
        ];

        return view('admin.gold_summary.index', compact('orders', 'summary'));
    }

    /**
     * Get gold summary data for DataTables
     */
    public function table(Request $request)
    {
        // Build base query with status filter
        $query = Order::with(['orderItems.product'])
            ->whereIn('status', self::VALID_STATUSES)
            ->orderBy('created_at', 'asc');

        // Apply date filters if provided
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        // Get total count before pagination
        $totalRecords = $query->count();
        
        \Log::info('Gold Summary - Total successful orders:', ['count' => $totalRecords]);

        // Get ALL orders for summary calculations (not paginated)
        $allOrders = $query->get();

        // Handle pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);

        // Build paginated query with same filters
        $paginatedQuery = Order::with(['orderItems.product'])
            ->whereIn('status', self::VALID_STATUSES)
            ->orderBy('created_at', 'asc');
            
        // Apply same date filters to paginated query
        if ($request->filled('from_date')) {
            $paginatedQuery->where('created_at', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $paginatedQuery->where('created_at', '<=', $request->to_date);
        }

        $orders = $paginatedQuery->skip($start)->take($length)->get();

        // Calculate summary data for paginated results
        $data = [];
        $cumulativeAmount = 0;
        
        // Calculate totals from ALL orders for summary cards
        $totalWeight = 0;
        $totalAmount = 0;
        $totalPurchasePercentageWeight = 0; // Sum of (weight * purchase_percentage)
        $totalSalePercentageWeight = 0;     // Sum of (weight * sale_percentage)
        $totalDiscount = 0;
        $totalDiscountPerGram = 0;

        // First, calculate totals from ALL orders for summary cards
        foreach ($allOrders as $order) {
            $orderTotalDiscount = floatval($order->discount_price ?? 0);
            $firstMazaneh = null;
            
            foreach ($order->orderItems as $item) {
                if ($item->product) {
                    $itemWeight = floatval($item->product->weight ?? 0) * intval($item->count);
                    $itemAmount = floatval($item->price) * intval($item->count);
                    
                    // Calculate purchase commission in grams
                    $darsadKharid = floatval($item->product->darsad_kharid ?? 0);
                    $purchaseCommissionGrams = ($itemWeight * $darsadKharid) / 100;
                    
                    // Calculate sale commission percentage (ojrat)
                    $ojrat = floatval($item->product->ojrat ?? 0);
                    
                    // Calculate discount: price - discounted_price
                    $originalPrice = floatval($item->product->getRawOriginal('price') ?? 0) / 10;
                    $discountedPrice = floatval($item->product->discounted_price ?? 0);
                    if ($discountedPrice > 0 && $originalPrice > 0) {
                        $productDiscountAmount = ($originalPrice - $discountedPrice) * intval($item->count);
                        $orderTotalDiscount += $productDiscountAmount;
                    }
                    
                    // Find first mazaneh that is not 0
                    if ($firstMazaneh === null && $item->product->mazaneh && floatval($item->product->mazaneh) != 0) {
                        $firstMazaneh = floatval($item->product->mazaneh);
                    }
                    
                    $totalWeight += $itemWeight;
                    $totalAmount += $itemAmount;
                    $totalPurchasePercentageWeight += $purchaseCommissionGrams;
                    $totalSalePercentageWeight += ($itemWeight * $ojrat) / 100;
                }
            }
            
            // Calculate discount per gram for this order
            $orderDiscountPerGram = 0;
            if ($firstMazaneh !== null && $firstMazaneh > 0) {
                $orderDiscountPerGram = ($orderTotalDiscount / $firstMazaneh) * 10;
            }
            
            $totalDiscount += $orderTotalDiscount;
            $totalDiscountPerGram += $orderDiscountPerGram;
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

            // Calculate discount per gram of gold
            $totalDiscount = floatval($order->discount_price ?? 0);
            $firstMazaneh = null;
            
            // Calculate discount: price - discounted_price for all order items' products
            foreach ($order->orderItems as $item) {
                if ($item->product) {
                    $originalPrice = floatval($item->product->getRawOriginal('price') ?? 0) / 10;
                    $discountedPrice = floatval($item->product->discounted_price ?? 0);
                    if ($discountedPrice > 0 && $originalPrice > 0) {
                        $productDiscountAmount = ($originalPrice - $discountedPrice) * intval($item->count);
                        $totalDiscount += $productDiscountAmount;
                    }
                    
                    // Find first mazaneh that is not 0
                    if ($firstMazaneh === null && $item->product->mazaneh && floatval($item->product->mazaneh) != 0) {
                        $firstMazaneh = floatval($item->product->mazaneh);
                    }
                }
            }
            
            $discountPerGram = 0;
            if ($firstMazaneh !== null && $firstMazaneh > 0) {
                $discountPerGram = ($totalDiscount / $firstMazaneh) * 10;
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
                'total_discount' => number_format($totalDiscount) . ' تومان',
                'mazaneh' => $firstMazaneh !== null ? number_format($firstMazaneh / 10, 0) : '-',
                'discount_per_gram' => $firstMazaneh !== null && $firstMazaneh > 0 ? number_format($discountPerGram, 3) : '-',
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
            'total_discount' => number_format($totalDiscount) . ' تومان',
            'total_discount_per_gram' => number_format($totalDiscountPerGram, 3),
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
        if (!in_array($order->status, self::VALID_STATUSES)) {
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
        $totalDiscount = 0;
        $totalDiscountPerGram = 0;

        // Calculate total order discount for proportional allocation
        $orderDiscountPrice = floatval($order->discount_price ?? 0);
        $totalOrderItemsCount = $order->orderItems->sum('count');
        
        foreach ($order->orderItems as $item) {
            if ($item->product) {
                $itemWeight = floatval($item->product->weight ?? 0) * intval($item->count);
                $itemAmount = floatval($item->price) * intval($item->count);
                
                $darsadKharid = floatval($item->product->darsad_kharid ?? 0);
                $purchaseCommissionGrams = ($itemWeight * $darsadKharid) / 100;
                
                $ojrat = floatval($item->product->ojrat ?? 0);
                $saleCommissionGrams = ($itemWeight * $ojrat) / 100;

                // Calculate discount per gram for this item
                $itemDiscount = 0;
                if ($totalOrderItemsCount > 0) {
                    // Proportional share of order discount
                    $proportionalOrderDiscount = ($orderDiscountPrice * intval($item->count)) / $totalOrderItemsCount;
                    // Calculate product discount: price - discounted_price
                    $originalPrice = floatval($item->product->getRawOriginal('price') ?? 0) / 10;
                    $discountedPrice = floatval($item->product->discounted_price ?? 0);
                    $productDiscountAmount = 0;
                    if ($discountedPrice > 0 && $originalPrice > 0) {
                        $productDiscountAmount = ($originalPrice - $discountedPrice) * intval($item->count);
                    }
                    $itemDiscount = $proportionalOrderDiscount + $productDiscountAmount;
                }
                
                $mazaneh = $item->product->mazaneh ? floatval($item->product->mazaneh) : null;
                $discountPerGram = 0;
                if ($mazaneh !== null && $mazaneh > 0) {
                    $discountPerGram = ($itemDiscount / $mazaneh) * 10;
                }

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
                    'total_discount' => $itemDiscount,
                    'discount_per_gram' => $discountPerGram,
                    'mazaneh' => $mazaneh,
                ];

                $totalWeight += $itemWeight;
                $totalAmount += $itemAmount;
                $totalPurchaseCommission += $purchaseCommissionGrams;
                $totalSaleCommission += $saleCommissionGrams;
                $totalDiscount += $itemDiscount;
                $totalDiscountPerGram += $discountPerGram;
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
            'total_discount' => $totalDiscount,
            'total_discount_per_gram' => $totalDiscountPerGram,
        ];

        return view('admin.gold_summary.show', compact('order', 'items', 'summary'));
    }
}

