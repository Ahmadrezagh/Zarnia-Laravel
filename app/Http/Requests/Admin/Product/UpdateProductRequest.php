<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'name' => 'nullable|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'description' => 'nullable|string',
            'discounted_price' => 'nullable|numeric',
            'categories' => 'nullable',
            'cover_image' => 'nullable',
            'gallery' => 'nullable',
            'attribute_group' => 'nullable',
            'attributes' => 'nullable',
            'discount_percentage' => 'nullable',
        ];
    }
}
