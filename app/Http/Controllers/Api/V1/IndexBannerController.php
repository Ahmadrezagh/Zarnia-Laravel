<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\IndexBanner\IndexBannerResource;
use App\Models\IndexBanner;
use Illuminate\Http\Request;

class IndexBannerController extends Controller
{
    public function index()
    {
        return IndexBannerResource::collection(IndexBanner::all());
    }
}
