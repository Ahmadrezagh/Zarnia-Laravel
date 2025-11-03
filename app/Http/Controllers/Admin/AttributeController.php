<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttributeRequest;
use App\Http\Requests\UpdateAttributeRequest;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class AttributeController extends Controller
{
    public function index()
    {
        $attributes = Attribute::query()->latest()->paginate();
        return view('admin.attributes.index', compact('attributes'));
    }

    public function store(StoreAttributeRequest $request)
    {
        if(Attribute::query()->where('name','=',$request->name)->exists()){
            toastr()->warning('این ویژگی قبلا ثبت شده است');
            return back();
        }
        Attribute::create($request->validated());
        return response()->json();
    }

    public function update(UpdateAttributeRequest $request, Attribute $attribute)
    {
        $attribute->update($request->validated());
        return response()->json();
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();
        return response()->json();
    }


    public function table()
    {
        $attributes = Attribute::query()->latest()->paginate();

        // Initialize slot content
        $slotContent = '';

        // Loop through users and render the Blade string for each
        foreach ($attributes as $attribute) {
            $slotContent .= Blade::render(
                <<<'BLADE'
             <x-modal.destroy id="modal-destroy-{{$attribute->id}}" title="حذف مدیر" action="{{route('attributes.destroy', $attribute->id)}}" title="{{$attribute->name}}" />
                <!-- Modal -->
                <x-modal.update id="modal-edit-{{$attribute->id}}" title="ویرایش مدیر" action="{{route('attributes.update', $attribute->id)}}" >
                    <input type="hidden" name="id" value="{{$attribute->id}}">
                    <x-form.input title="نام" :value="$attribute->name" name="name" />
                    <x-form.input title="جمله پیشوند" :value="$attribute->prefix_sentence"   name="prefix_sentence" />
                    <x-form.input title="جمله پسوند" :value="$attribute->postfix_sentence"   name="postfix_sentence" />
                </x-modal.update>
                <!-- /Modal -->
            BLADE,
                ['attribute' => $attribute]
            );
        }

        return view('components.table', [
            'id' => 'attributes-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'name', 'type' => 'text'],
                ['label' => 'جمله پیشوندی', 'key' => 'prefix_sentence', 'type' => 'text'],
                ['label' => 'جمله پسوند', 'key' => 'postfix_sentence', 'type' => 'text'],
            ],
            'url' => route('table.attributes'),
            'items' => $attributes,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }


    public function loadAttributeGroup(Request $request)
    {
        if($request->category_ids){
            $categories = Category::query()->whereIn('id',$request->category_ids)->with('attributeGroups')->get();
            if ($categories) {
                $attribute_ids = [];
                $attribute_group_ids = [];
                $attribute_groups = [];
                
                foreach ($categories as $category) {
                    foreach ($category->attributeGroups as $attribute_group) {
                        // Collect unique attribute group IDs
                        if (!in_array($attribute_group->id, $attribute_group_ids)) {
                            $attribute_group_ids[] = $attribute_group->id;
                            $attribute_groups[] = [
                                'id' => $attribute_group->id,
                                'name' => $attribute_group->name
                            ];
                        }
                        
                        foreach ($attribute_group->attributes as $attribute) {
                            array_push($attribute_ids, $attribute->id);
                        }
                    }
                }
                
                $attributeValues = AttributeValue::where('product_id', $request->product_id)
                    ->whereIn('attribute_id', $attribute_ids)
                    ->get();

                $response = [
                    'attributeGroup' => '',
                    'attributes' => Attribute::query()->whereIn('id',$attribute_ids)->get(),
                    'attributeValues' => $attributeValues
                ];
                
                // Include attribute groups if requested
                if ($request->get_attribute_groups || $request->has('get_attribute_groups')) {
                    $response['attributeGroups'] = $attribute_groups;
                }
                
                return response()->json($response);
            }
        }

        return response()->json(['attributeGroup' => null]);
    }
}
