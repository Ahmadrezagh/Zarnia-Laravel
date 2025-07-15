<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BlogItemResource;
use App\Http\Resources\Api\V1\BlogListResource;
use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        return BlogListResource::collection(Blog::query()->paginate(10));
    }

    public function show(Blog $blog)
    {
        return BlogItemResource::make($blog);
    }
}
