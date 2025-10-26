<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Address\AddressResource;
use App\Http\Resources\Api\V1\Gifts\Giftresource;
use Illuminate\Http\Request;

class GiftController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $gifts = $user->gifts;
        return Giftresource::collection($gifts);
    }
}
