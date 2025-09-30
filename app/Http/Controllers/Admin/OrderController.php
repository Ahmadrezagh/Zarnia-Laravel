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
        $orders = Order::query()
            ->filterByTransactionId($request->transaction_id)
            ->latest()
            ->paginate();
        return view('admin.orders.index', compact('orders'));
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

        // Save the updated order
        $order->save();

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
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->delete();
        return response()->json();
    }


    public function table(Request $request)
    {
        $query = Order::query()
            ->filterByTransactionId($request->transaction_id)
            ->latest();
        $totalRecords = $query->count();

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where('id','LIKE',"%{$search}%")
                ->orWhereHas('address', function ($query) use ($search) {
                    $query->where('receiver_name', 'LIKE',"%{$search}%");
                })
                ->orWhereHas('orderItems',function ($query) use ($search) {
                    $query->where('name', 'LIKE',"%{$search}%");
                });
        }


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
        // Initialize slot content
        $filteredRecords = $data->count();

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
//        if($request->orderStatus == Order::$STATUSES[1]){
//            $order->submitInAccountingApp();
//        }
//        if($request->orderStatus == Order::$STATUSES[8]){
//            $order->cancelOrder();
//        }
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
            $order->update(['status' => Order::$STATUSES[8]]);
            return $result;
        }
        return [
            'error' => 'این قابلیت صرفا جهت سفارشات با درگاه اسنپ می باشد'
        ];
    }
}
