<?php

namespace App\Http\Requests\Api\V1\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
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
            'receiver_name' => 'string|required',
            'receiver_phone' => 'string|required',
            'address' => 'string|required',
            'postal_code' => 'numeric|required',
            'province_id' => 'numeric|required|exists:iran_provinces,id',
            'city_id' => 'numeric|required|exists:iran_cities,id',
        ];
    }
}
