<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\AdminUpdateOrderRequest;
use App\Http\Requests\Admin\Order\AdminUpdateOrderStatusRequest;
use App\Http\Resources\Admin\Order\OrderItemResource;
use App\Http\Resources\Admin\Table\AdminProductResource;
use App\Models\Attribute;
use App\Models\Order;
use App\Models\Page;
use App\Services\PaymentGateways\SnappPayGateway;
use App\Services\SMS\Kavehnegar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get status counts for all statuses
        $statusCounts = [];
        foreach (Order::$STATUSES as $status) {
            $statusCounts[$status] = Order::where('status', $status)->count();
        }
        
        $orders = Order::query()
            ->filterByTransactionId($request->transaction_id)
            ->filterByStatus($request->status)
            ->search($request->search)
            ->orderByStatusPriority()
            ->paginate();
        return view('admin.orders.index', compact('orders', 'statusCounts'));
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
        // Debug logging
        \Log::info('Order creation request received', [
            'user_id' => $request->user_id,
            'address_id' => $request->address_id,
            'gateway_id' => $request->gateway_id,
            'shipping_id' => $request->shipping_id,
            'products' => $request->products,
            'all_data' => $request->all()
        ]);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'address_id' => 'nullable|exists:addresses,id',
            'gateway_id' => 'nullable|exists:gateways,id',
            'shipping_id' => 'nullable|exists:shippings,id',
            'products' => 'required|string',
            'status' => 'required|in:' . implode(',', Order::$STATUSES),
            'discount_code' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        try {
            // Replace null with empty string for optional fields
            $discountCode = $request->discount_code ?? '';
            $note = $request->note ?? '';
            
            // Parse products JSON
            $products = json_decode($request->products, true);
            
            // Validate that products is valid JSON and is an array
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($products)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['products' => ['فرمت محصولات نامعتبر است']]
                ], 422);
            }
            
            if (empty($products)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['products' => ['حداقل یک محصول را انتخاب کنید']]
                ], 422);
            }

            // Calculate totals
            $totalAmount = 0;
            foreach ($products as $product) {
                $totalAmount += $product['price'] * $product['quantity'];
            }

            // Get shipping price (0 for in-store orders)
            $shippingPrice = 0;
            if ($request->shipping_id) {
                $shipping = \App\Models\Shipping::find($request->shipping_id);
                $shippingPrice = $shipping->price ?? 0;
            }

            // Handle discount if provided
            $discountPrice = 0;
            $discountPercentage = 0;
            if (!empty($discountCode)) {
                $discount = \App\Models\Discount::where('code', $discountCode)->first();
                if ($discount) {
                    if ($discount->amount) {
                        $discountPrice = $discount->amount;
                    } elseif ($discount->percentage) {
                        $discountPrice = ($discount->percentage / 100) * $totalAmount;
                        $discountPercentage = $discount->percentage;
                    }
                }
            }

            // Calculate final amount
            $finalAmount = $totalAmount + $shippingPrice - $discountPrice;
            $finalAmount = max(0, $finalAmount); // Ensure non-negative

            // Generate unique transaction ID
            $transactionId = Order::generateUniqueTransactionId();

            // Create order
            $order = Order::create([
                'user_id' => $request->user_id,
                'address_id' => $request->address_id,
                'shipping_id' => $request->shipping_id,
                'gateway_id' => $request->gateway_id,
                'status' => $request->status,
                'discount_code' => $discountCode,
                'discount_price' => $discountPrice,
                'discount_percentage' => $discountPercentage,
                'total_amount' => $totalAmount,
                'final_amount' => $finalAmount,
                'shipping_price' => $shippingPrice,
                'transaction_id' => $transactionId,
                'note' => $note,
                'paid_at' => in_array($request->status, ['paid', 'boxing', 'sent', 'post', 'completed']) ? now() : null,
            ]);

            // Create order items
            foreach ($products as $productData) {
                $product = \App\Models\Product::find($productData['product_id']);
                
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productData['product_id'],
                    'name' => $product->name,
                    'count' => $productData['quantity'],
                    'price' => $productData['price'],
                    'etiket' => $product->etikets()->first()->code ?? null,
                ]);
            }

            \Log::info('Order created in admin', [
                'order_id' => $order->id,
                'status' => $order->status,
                'is_paid' => ($order->status === 'paid'),
                'final_amount' => $order->final_amount
            ]);

            // Check and generate gift if order is paid (created by admin with paid status)
            if ($order->status === 'paid') {
                \Log::info('Order status is paid, proceeding with gift and SMS', [
                    'order_id' => $order->id
                ]);
                
                $order->checkAndGenerateGift();
                
                // Refresh order to ensure user relationship is loaded
                $order->refresh();
                $order->load('user');
                
                // Notify admins about new paid order
                $adminNumbers = [
                    '09127127053',
                    '09193106488'
                ];
                
                $sms = new Kavehnegar();
                $userName = $order->user->name ?? 'کاربر';
                $orderAmount = number_format($order->final_amount) . ' تومان';
                
                \Log::info('Sending admin SMS notification', [
                    'order_id' => $order->id,
                    'user_name' => $userName,
                    'order_amount' => $orderAmount,
                    'admin_numbers' => $adminNumbers
                ]);
                
                foreach ($adminNumbers as $phone) {
                    try {
                        $result = $sms->send_with_two_token($phone, $userName, $orderAmount, 'notifyAdminNewOrder');
                        \Log::info('Admin SMS sent successfully', [
                            'order_id' => $order->id,
                            'phone' => $phone,
                            'sms_result' => $result
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Failed to send admin SMS notification', [
                            'order_id' => $order->id,
                            'phone' => $phone,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
            } else {
                \Log::info('Order status is not paid, skipping SMS', [
                    'order_id' => $order->id,
                    'status' => $order->status
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'سفارش با موفقیت ایجاد شد',
                'order' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد سفارش: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function update(AdminUpdateOrderRequest $request, $order)
    {
        $order = Order::query()->findOrFail($order);
        // از hidden input
        $payload = json_decode($request->updated_items, true);

        if ($payload && isset($payload['order_id'], $payload['items'])) {
            $order = Order::findOrFail($payload['order_id']);

            foreach ($payload['items'] as $itemId => $newCount) {
                $orderItem = $order->orderItems()->find($itemId);

                if (!$orderItem) {
                    continue; // آیتم معتبر نبود
                }

                if ($newCount == 0) {
                    // یعنی حذف
                    $orderItem->delete();
                } else {
                    // آپدیت تعداد
                    $orderItem->count = $newCount;
                    $orderItem->save();
                }
            }
        }
        // Recalculate total_amount: sum of (price * count) for all order items
        $totalAmount = $order->orderItems()->sum(DB::raw('price * count'));
        $order->total_amount = $totalAmount;

        // Recalculate final_amount: (total_amount + shipping_price) - discount_price
        $shippingPrice = $order->shipping_price ?? 0; // Adjust based on your model
        $discountPrice = $order->discount_price ?? 0; // Adjust based on your model
        $order->final_amount = ($totalAmount + $shippingPrice) - $discountPrice;

        // Check if final_amount is less than or equal to 0
        if ($order->final_amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'بدلیل صفر شدن مجموع فاکتور سفارش قابل ویرایش نیست درصورت تمایل سفارش را لغو یا مسترد کنید'
            ], 422);
        }

        // Save the updated order
        $order->save();

        $order = Order::query()->find($order->id);
        // Call external transaction update
        $transactionResult = $order->updateSnappTransaction();

        // Return response
        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order->load('orderItems'), // Optionally include updated items
            'transaction' => $transactionResult, // Include transaction result if needed
        ]);
    }

    /**
     * Display trashed orders (زباله دان).
     */
    public function trash(Request $request)
    {
        // Get status counts for all statuses in trash
        $statusCounts = [];
        foreach (Order::$STATUSES as $status) {
            $statusCounts[$status] = Order::onlyTrashed()->where('status', $status)->count();
        }
        
        $orders = Order::onlyTrashed()
            ->filterByTransactionId($request->transaction_id)
            ->filterByStatus($request->status)
            ->search($request->search)
            ->orderByStatusPriority()
            ->paginate();
            
        return view('admin.orders.trash', compact('orders', 'statusCounts'));
    }

    /**
     * Restore a trashed order.
     */
    public function restore($id)
    {
        $order = Order::onlyTrashed()->findOrFail($id);
        $order->restore();
        
        return response()->json([
            'success' => true,
            'message' => 'سفارش با موفقیت بازیابی شد'
        ]);
    }

    /**
     * Permanently delete an order.
     */
    public function forceDelete($id)
    {
        $order = Order::onlyTrashed()->findOrFail($id);
        $order->forceDelete();
        
        return response()->json([
            'success' => true,
            'message' => 'سفارش به طور کامل حذف شد'
        ]);
    }

    /**
     * Bulk update status for multiple orders.
     */
    public function bulkStatus(Request $request)
    {
        // Decode order_ids if it's a JSON string
        $orderIds = $request->order_ids;
        if (is_string($orderIds)) {
            $orderIds = json_decode($orderIds, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($orderIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فرمت داده ارسالی صحیح نیست'
                ], 422);
            }
        }

        // Ensure order_ids is an array
        if (!is_array($orderIds) || empty($orderIds)) {
            return response()->json([
                'success' => false,
                'message' => 'لطفا حداقل یک سفارش را انتخاب کنید'
            ], 422);
        }

        // Convert all order IDs to integers and re-index array
        $orderIds = array_map('intval', $orderIds);
        $orderIds = array_filter($orderIds, function($id) {
            return $id > 0; // Remove any invalid IDs (0 or negative)
        });
        $orderIds = array_values($orderIds); // Re-index array
        
        if (empty($orderIds)) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه‌های سفارش معتبر نیستند'
            ], 422);
        }

        // Validate status
        $status = $request->status;
        if (!$status || !in_array($status, Order::$STATUSES)) {
            return response()->json([
                'success' => false,
                'message' => 'وضعیت انتخاب شده معتبر نیست'
            ], 422);
        }

        // Validate order IDs exist (only non-trashed orders)
        $validOrderIds = Order::whereIn('id', $orderIds)->pluck('id')->toArray();
        if (empty($validOrderIds)) {
            return response()->json([
                'success' => false,
                'message' => 'سفارش‌های انتخاب شده یافت نشدند'
            ], 422);
        }

        // Update only valid orders
        $updated = Order::whereIn('id', $validOrderIds)->update([
            'status' => $status,
            'paid_at' => in_array($status, ['paid', 'boxing', 'sent', 'post', 'completed']) ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "وضعیت {$updated} سفارش با موفقیت تغییر کرد",
            'updated_count' => $updated
        ]);
    }

    /**
     * Bulk delete (soft delete) multiple orders.
     */
    public function bulkDelete(Request $request)
    {
        // Decode order_ids if it's a JSON string
        $orderIds = $request->order_ids;
        if (is_string($orderIds)) {
            $orderIds = json_decode($orderIds, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'فرمت داده ارسالی صحیح نیست'
                ], 422);
            }
        }

        $request->merge(['order_ids' => $orderIds]);

        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id',
        ]);

        $deleted = Order::whereIn('id', $orderIds)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deleted} سفارش به زباله دان منتقل شد",
            'deleted_count' => $deleted
        ]);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy( $order_id)
    {
         $order = Order::query()->findOrFail($order_id);
        $order->delete();
        return response()->json([
            'success' => true,
            'message' => 'سفارش به زباله دان منتقل شد'
        ]);
    }


    public function table(Request $request)
    {
        // Get total records count (before any filters)
        $totalRecords = Order::count();

        // Build base query with filters
        $query = Order::query()
            ->filterByTransactionId($request->transaction_id)
            ->filterByStatus($request->status);

        // Apply search filter - handle both DataTables search and direct search parameter
        $searchValue = null;
        if ($request->has('search')) {
            // Check if search is an array (DataTables format)
            if (is_array($request->input('search'))) {
                $searchValue = $request->input('search.value');
            } else {
                // Direct search parameter
                $searchValue = $request->input('search');
            }
        }
        
        if (!empty($searchValue)) {
            $query->search($searchValue);
        }
        
        // Apply custom ordering by status priority
        $query->orderByStatusPriority();

        // Get filtered count (after applying filters but before pagination)
        $filteredRecords = $query->count();

        // Handle pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10); // Default to 10 if length is missing or invalid
        if ($length <= 0) {
            $length = 10; // Ensure length is positive to avoid SQL error
        }

        // Fetch paginated data
        $data = $query
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($item) {
                return OrderItemResource::make($item); // Ensure all necessary fields are included
            });

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    public function trashTable(Request $request)
    {
        // Get total records count (before any filters) - only trashed
        $totalRecords = Order::onlyTrashed()->count();

        // Build base query with filters - only trashed orders
        $query = Order::onlyTrashed()
            ->filterByTransactionId($request->transaction_id)
            ->filterByStatus($request->status);

        // Apply search filter - handle both DataTables search and direct search parameter
        $searchValue = null;
        if ($request->has('search')) {
            // Check if search is an array (DataTables format)
            if (is_array($request->input('search'))) {
                $searchValue = $request->input('search.value');
            } else {
                // Direct search parameter
                $searchValue = $request->input('search');
            }
        }
        
        if (!empty($searchValue)) {
            $query->search($searchValue);
        }
        
        // Apply custom ordering by status priority
        $query->orderByStatusPriority();

        // Get filtered count (after applying filters but before pagination)
        $filteredRecords = $query->count();

        // Handle pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10); // Default to 10 if length is missing or invalid
        if ($length <= 0) {
            $length = 10; // Ensure length is positive to avoid SQL error
        }

        // Fetch paginated data
        $data = $query
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($item) {
                return OrderItemResource::make($item); // Ensure all necessary fields are included
            });

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    public function updateOrderStatus(AdminUpdateOrderStatusRequest $request)
    {
        $order = Order::find($request->input('orderId'));
        $order->update([
            'status' => $request->orderStatus
        ]);
        if($request->orderStatus == Order::$STATUSES[1]){
//            $order->submitInAccountingApp();
        }
        if($request->orderStatus == Order::$STATUSES[3] || $request->orderStatus == Order::$STATUSES[4]){
//            return $order->cancelOrder();
        }
        return response()->json();
    }

    public function print(Order $order)
    {
        return view('pdf.order', compact('order'));
    }


    function normalizeIranPhone(?string $phone): ?string
    {
        if (!$phone) return null;

        $phone = trim($phone);

        // If already in +98 format, keep it
        if (preg_match('/^\+98\d{10}$/', $phone)) {
            return $phone;
        }

        // If starts with 09xxxxxxxxx → convert to +98
        if (preg_match('/^09\d{9}$/', $phone)) {
            return '+98' . substr($phone, 1);
        }

        // If starts with 9xxxxxxxxx → convert to +98
        if (preg_match('/^9\d{9}$/', $phone)) {
            return '+98' . $phone;
        }

        // Otherwise return null (invalid format)
        return null;
    }


    public function cancel($order_id): array
    {
        $order = Order::query()->findOrFail($order_id);
        if($order->gateway->key == 'snapp'){
            $snapp = new SnappPayGateway();
            $result = $snapp->cancel($order->payment_token);
            $order->update(['status' => Order::$STATUSES[4]]);
            return $result;
        }
        return [
            'error' => 'این قابلیت صرفا جهت سفارشات با درگاه اسنپ می باشد'
        ];
    }

    /**
     * Get users list for order creation
     */
    public function getUsersList(Request $request)
    {
        $search = $request->input('search', '');
        
        $users = \App\Models\User::query()
            ->when($search, function($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
            })
            ->select('id', 'name', 'phone', 'email')
            ->limit(20)
            ->get();
        
        return response()->json([
            'results' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'text' => $user->name . ' (' . $user->phone . ')',
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                ];
            })
        ]);
    }

    /**
     * Get user addresses for order creation
     */
    public function getUserAddresses($userId)
    {
        $user = \App\Models\User::with('addresses')->findOrFail($userId);
        
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
            ],
            'addresses' => $user->addresses->map(function($address) {
                return [
                    'id' => $address->id,
                    'receiver_name' => $address->receiver_name,
                    'receiver_phone' => $address->receiver_phone ?? $address->phone,
                    'address' => $address->address,
                    'postal_code' => $address->postal_code ?? '',
                    'province' => $address->province ?? '',
                    'city' => $address->city ?? '',
                ];
            })
        ]);
    }
}
