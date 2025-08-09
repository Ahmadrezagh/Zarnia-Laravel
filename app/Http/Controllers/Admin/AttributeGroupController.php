<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttributeGroupRequest;
use App\Http\Requests\UpdateAttributeGroupRequest;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use Illuminate\Http\Request;

class AttributeGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $attributes = Attribute::query()->latest()->get();
        $attribute_groups = AttributeGroup::query()->latest()->paginate();
        return view('admin.attribute_groups.index', compact('attribute_groups','attributes'));
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
    public function store(StoreAttributeGroupRequest $request)
    {
        $attribute_group = AttributeGroup::query()->create($request->validated());
        $attribute_group->attributes()->sync($request->attribute_ids);
        return response()->json();
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
    public function update(UpdateAttributeGroupRequest $request, AttributeGroup $attribute_group)
    {
        $attribute_group->update($request->validated());
        $attribute_group->attributes()->sync($request->attribute_ids);
        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttributeGroup $attribute_group)
    {
        $attribute_group->delete();
        return response()->json();
    }
}
