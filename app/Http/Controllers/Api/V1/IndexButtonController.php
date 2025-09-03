<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\IndexButton\IndexButtonResource;
use App\Models\IndexButton;
use Illuminate\Http\Request;

class IndexButtonController extends Controller
{
    public function index()
    {
        return IndexButtonResource::collection(IndexButton::all());
    }
}
