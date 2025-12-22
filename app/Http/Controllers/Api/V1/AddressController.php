<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Address\StoreAddressRequest;
use App\Http\Requests\Api\V1\Address\UpdateAddressRequest;
use App\Http\Resources\Api\V1\Address\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $addresses = $user->addresses;
        return AddressResource::collection($addresses);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAddressRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();
        
        // If receiver_name is not provided in request (not set at all), set it to user's name
        // But allow explicit null values to remain null
        if (!isset($validated['receiver_name']) && $user->name) {
            $validated['receiver_name'] = $user->name;
        }
        
        // Ensure receiver_name and receiver_phone can be null
        $validated['receiver_name'] = $validated['receiver_name'] ?? null;
        $validated['receiver_phone'] = $validated['receiver_phone'] ?? null;
        
        $address = $user->addresses()->create($validated);
        return new AddressResource($address);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAddressRequest $request, Address $address)
    {
        $validated = $request->validated();
        
        // Ensure receiver_name and receiver_phone can be null
        $validated['receiver_name'] = $validated['receiver_name'] ?? null;
        $validated['receiver_phone'] = $validated['receiver_phone'] ?? null;
        
        $address->update($validated);
        return new AddressResource($address);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Address $address)
    {
        $address->delete();
        return response()->noContent();
    }
}
