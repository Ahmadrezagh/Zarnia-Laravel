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

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::query()->latest()->paginate();
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
        $orderItemIds = $request->input('order_item_ids', []);

        if (!empty($orderItemIds)) {
            // Delete all order items of this order where id is not in the given list
            $order->orderItems()
                ->whereNotIn('id', $orderItemIds)
                ->delete();
        }
        return $order->updateSnappTransaction();
        return response()->json();
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
        $query = Order::query()->latest();
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
        if($request->orderStatus == Order::$STATUSES[1]){
            $order->submitInAccountingApp();
        }
        if($request->orderStatus == Order::$STATUSES[8]){
            $order->cancelOrder();
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
            $order->update(['status' => Order::$STATUSES[8]]);
            return $result;
        }
        return [
            'error' => 'این قابلیت صرفا جهت سفارشات با درگاه اسنپ می باشد'
        ];
    }
}
