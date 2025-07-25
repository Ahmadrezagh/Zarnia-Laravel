<?php

namespace App\Http\Requests\Admin\User;

use App\Rules\IranPhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'name' => ['required'],
            'password' => ['nullable','confirmed'],
            'email' => ['nullable', Rule::unique('users', 'email')->ignore($this->id)],
            'phone' => ['nullable', Rule::unique('users', 'phone')->ignore($this->id),new IranPhoneNumberRule ],
            'profile_image' => ['nullable', 'file'],

        ];
    }
}
