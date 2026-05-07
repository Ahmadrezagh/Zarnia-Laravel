<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\QA\QAResource;
use App\Models\QA;
use Illuminate\Http\Request;

class QAController extends Controller
{
    public function index()
    {
        return QAResource::collection(QA::query()->latest()->get());
    }
}
