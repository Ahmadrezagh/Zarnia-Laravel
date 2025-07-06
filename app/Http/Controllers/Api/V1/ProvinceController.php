<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CityResource;
use App\Http\Resources\Api\V1\ProvinceResource;
use App\Models\IranProvince;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    public function index()
    {
        return ProvinceResource::collection(IranProvince::all());
    }

    public function show($province_id)
    {
        $province = IranProvince::query()->findOrFail($province_id);
        return CityResource::collection($province->cities()->get());
    }
}
