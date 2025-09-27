<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Discount\VerifyDiscountRequest;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function verify(VerifyDiscountRequest $request)
    {
        $user = auth()->user();
        $shopping_cart = $user->totalShoppingCart();

        $result = Discount::verify($request->discount_code, $shopping_cart, $user->id);

        // تعیین وضعیت HTTP بر اساس نتیجه
        $status = $result['valid'] ? 200 : 422; // یا 404 برای "یافت نشد"

        return response()->json($result, $status);
   }
}
