<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }
        
        return new UserResource($user);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }
        
        $user->update($request->validated());
        return UserResource::make(User::find($user->id));
    }
}
