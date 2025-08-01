<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class storeComprehensiveProductRequest extends FormRequest
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
        return [
            '_token' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'categories' => 'nullable',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
            'cover_image' => 'nullable', // Adjust if it's a file
            'gallery' => 'nullable|array|min:1',
        ];
    }
}
