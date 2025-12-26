<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
        $isNotGoldProduct = $this->input('is_not_gold_product') == '1';
        
        $rules = [
            'name' => 'required|string|max:255',
            'price' => $isNotGoldProduct ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'parent_id' => 'nullable|integer|exists:products,id',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'integer|exists:categories,id',
            'attribute_group' => 'nullable|string|max:255',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'gallery' => 'nullable|array',
            'gallery.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'etikets' => 'nullable|array',
            'etikets.*.code' => 'nullable|string|max:255',
            'is_not_gold_product' => 'nullable|boolean',
        ];
        
        // Add gold-related fields validation only if it's a gold product
        if (!$isNotGoldProduct) {
            $rules['weight'] = 'required|numeric|min:0';
            $rules['darsad_kharid'] = 'nullable|numeric|min:0|max:100';
            $rules['ojrat'] = 'nullable|numeric|min:0|max:100';
        }
        
        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'نام محصول الزامی است.',
            'weight.required' => 'وزن محصول الزامی است.',
            'weight.numeric' => 'وزن باید عدد باشد.',
            'weight.min' => 'وزن نمی‌تواند منفی باشد.',
            'price.required' => 'قیمت محصول الزامی است.',
            'price.numeric' => 'قیمت باید عدد باشد.',
            'price.min' => 'قیمت نمی‌تواند منفی باشد.',
            'category_ids.required' => 'انتخاب دسته بندی الزامی است.',
            'category_ids.array' => 'دسته بندی باید به صورت آرایه ارسال شود.',
            'category_ids.min' => 'حداقل یک دسته بندی باید انتخاب شود.',
            'cover_image.image' => 'فایل تصویر کاور باید یک تصویر معتبر باشد.',
            'cover_image.max' => 'حجم تصویر کاور نباید بیشتر از 2 مگابایت باشد.',
            'gallery.*.image' => 'فایل‌های گالری باید تصویر معتبر باشند.',
            'gallery.*.max' => 'حجم هر تصویر گالری نباید بیشتر از 2 مگابایت باشد.',
        ];
    }
}

