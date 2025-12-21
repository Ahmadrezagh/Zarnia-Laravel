<?php

namespace App\Http\Requests\Admin\ProductSlider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductSliderRequest extends FormRequest
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
            'title' => 'required|string',
            'query' => 'required|string',
            'show_more' => 'nullable|numeric',
            'before_category_slider' => 'nullable|integer|min:0',
            'after_category_slider' => 'nullable|integer|min:0',
        ];
    }
}
