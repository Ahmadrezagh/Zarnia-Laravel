<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiscountCodeRequest extends FormRequest
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
            'code' => ['nullable',Rule::unique('discounts', 'code')],
            'percentage' => ['nullable','numeric','between:0,100'],
            'amount' => ['nullable','numeric'],
            'min_price' => ['nullable','numeric'],
            'max_price' => ['nullable','numeric'],
            'quantity' => ['nullable','numeric','min:1'],
            'quantity_per_user' => ['nullable','numeric','min:1'],
            'start_at' => ['required','date'],
            'expires_at' => ['required','date'],
        ];
    }
}
