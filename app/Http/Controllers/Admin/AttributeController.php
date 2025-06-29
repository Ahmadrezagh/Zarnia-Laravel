<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttributeGroup;
use App\Models\AttributeValue;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function loadAttributeGroup(Request $request)
    {
        $group = AttributeGroup::where('name', $request->name)->first();

        if ($group) {
            $attributeValues = AttributeValue::where('product_id', $request->product_id)
                ->whereIn('attribute_id', $group->attributes->pluck('id'))
                ->get();

            return response()->json([
                'attributeGroup' => $group,
                'attributes' => $group->attributes,
                'attributeValues' => $attributeValues
            ]);
        }

        return response()->json(['attributeGroup' => null]);
    }
}
