<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftStructure;
use Illuminate\Http\Request;

class GiftStructureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $giftStructures = GiftStructure::query()->latest()->paginate();
        return view('admin.gift_structures.index', compact('giftStructures'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_price' => 'required|integer|min:0',
            'to_price' => 'required|integer|min:0|gt:from_price',
            'amount' => 'nullable|integer|min:0',
            'percentage' => 'nullable|integer|min:0|max:100',
            'limit_in_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $giftStructure = GiftStructure::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'ساختار هدیه با موفقیت ایجاد شد',
            'data' => $giftStructure
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GiftStructure $giftStructure)
    {
        $validated = $request->validate([
            'from_price' => 'required|integer|min:0',
            'to_price' => 'required|integer|min:0|gt:from_price',
            'amount' => 'nullable|integer|min:0',
            'percentage' => 'nullable|integer|min:0|max:100',
            'limit_in_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $giftStructure->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'ساختار هدیه با موفقیت بروزرسانی شد'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GiftStructure $giftStructure)
    {
        $giftStructure->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'ساختار هدیه با موفقیت حذف شد'
        ]);
    }

    /**
     * DataTable endpoint
     */
    public function table()
    {
        $giftStructures = GiftStructure::query()->latest()->get();

        return response()->json([
            'data' => $giftStructures->map(function ($gift) {
                return [
                    'id' => $gift->id,
                    'from_price' => number_format($gift->from_price) . ' تومان',
                    'to_price' => number_format($gift->to_price) . ' تومان',
                    'discount_info' => $gift->discount_info, // Use model accessor
                    'limit_in_days' => $gift->limit_in_days . ' روز',
                    'is_active' => $gift->is_active ? 
                        '<span class="badge badge-success">فعال</span>' : 
                        '<span class="badge badge-danger">غیرفعال</span>',
                    'created_at' => $gift->created_at->format('Y-m-d H:i'),
                ];
            })
        ]);
    }
}

