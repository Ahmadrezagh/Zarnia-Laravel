<?php

namespace App\Http\Requests\Api\V1\Orders;

use Illuminate\Foundation\Http\FormRequest;

class createOrderRequest extends FormRequest
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

            'address_id' => ['required', 'exists:addresses,id'],
            'shipping_id' => ['required', 'exists:shippings,id'],
            'shipping_time_id' => ['nullable', 'exists:shipping_times,id'],
            'gateway_id' => ['nullable', 'exists:gateways,id'],

            'discount_code' => ['nullable', 'string', 'max:255'],

            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
