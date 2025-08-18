<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\AdminUpdateOrderRequest;
use App\Models\Attribute;
use App\Models\Order;
use App\Models\Page;
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
    public function update(AdminUpdateOrderRequest $request, Order $order)
    {
        $order->update($request->validated());
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
            ->get();
        // Initialize slot content
        $filteredRecords = $data->count();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $query
        ]);
    }
}
