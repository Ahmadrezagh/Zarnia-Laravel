<?php

namespace App\Http\Requests\Admin\IndexBannder;

use Illuminate\Foundation\Http\FormRequest;

class StoreIndexBannerRequest extends FormRequest
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
            'link' => 'required|string',
            'cover_image' => 'required',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'cover_image.required' => 'تصویر کاور الزامی است.',
            'cover_image.image' => 'فایل ارسالی باید یک تصویر باشد.',
            'cover_image.max' => 'حجم تصویر نباید بیشتر از 2 مگابایت باشد.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('cover_image')) {
                $file = $this->file('cover_image');
                \Log::info('StoreIndexBannerRequest - After validation', [
                    'is_valid' => $file->isValid(),
                    'error_code' => $file->getError(),
                    'error_message' => $file->getErrorMessage(),
                    'validation_errors' => $validator->errors()->all(),
                ]);
            }
        });
    }
}
