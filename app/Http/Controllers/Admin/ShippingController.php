<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipping;
use App\Models\ShippingTime;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shippings = Shipping::query()->with('times')->latest()->paginate();
        return view('admin.shippings.index', compact('shippings'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'passive_price' => 'nullable|integer|min:0',
            'key' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
        ]);

        $validated['passive_price'] = $validated['passive_price'] ?? 0;

        $shipping = Shipping::create($validated);

        // Handle image upload
        if ($request->hasFile('image')) {
            $shipping->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return response()->json([
            'success' => true,
            'message' => 'روش ارسال با موفقیت ایجاد شد',
            'data' => $shipping
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shipping $shipping)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'passive_price' => 'nullable|integer|min:0',
            'key' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
        ]);

        $validated['passive_price'] = $validated['passive_price'] ?? 0;

        $shipping->update($validated);

        // Handle image upload
        if ($request->hasFile('image')) {
            $shipping->clearMediaCollection('image');
            $shipping->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return response()->json([
            'success' => true,
            'message' => 'روش ارسال با موفقیت بروزرسانی شد'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shipping $shipping)
    {
        $shipping->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'روش ارسال با موفقیت حذف شد'
        ]);
    }

    /**
     * DataTable endpoint
     */
    public function table()
    {
        $shippings = Shipping::query()->with('times')->latest()->get();

        return response()->json([
            'data' => $shippings->map(function ($shipping) {
                return [
                    'id' => $shipping->id,
                    'title' => $shipping->title,
                    'key' => $shipping->key ?? '-',
                    'price' => number_format($shipping->price) . ' تومان',
                    'passive_price' => number_format($shipping->passive_price) . ' تومان',
                    'times_count' => $shipping->times->count() . ' زمان',
                    'image' => '<img src="' . $shipping->image . '" width="50" height="50" class="rounded">',
                    'created_at' => $shipping->created_at->format('Y-m-d'),
                ];
            })
        ]);
    }

    /**
     * Show shipping times for a specific shipping
     */
    public function times(Shipping $shipping)
    {
        $shippingTimes = $shipping->times()->latest()->paginate();
        return view('admin.shipping_times.index', compact('shipping', 'shippingTimes'));
    }

    /**
     * Store shipping time
     */
    public function storeTime(Request $request, Shipping $shipping)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $shippingTime = $shipping->times()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'زمان ارسال با موفقیت ایجاد شد',
            'data' => $shippingTime
        ]);
    }

    /**
     * Update shipping time
     */
    public function updateTime(Request $request, Shipping $shipping, ShippingTime $time)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $time->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'زمان ارسال با موفقیت بروزرسانی شد'
        ]);
    }

    /**
     * Delete shipping time
     */
    public function destroyTime(Shipping $shipping, ShippingTime $time)
    {
        $time->delete();

        return response()->json([
            'success' => true,
            'message' => 'زمان ارسال با موفقیت حذف شد'
        ]);
    }

    /**
     * DataTable endpoint for shipping times
     */
    public function timesTable(Shipping $shipping)
    {
        $times = $shipping->times()->latest()->get();

        return response()->json([
            'data' => $times->map(function ($time) {
                return [
                    'id' => $time->id,
                    'title' => $time->title,
                    'created_at' => $time->created_at->format('Y-m-d H:i'),
                ];
            })
        ]);
    }
}

