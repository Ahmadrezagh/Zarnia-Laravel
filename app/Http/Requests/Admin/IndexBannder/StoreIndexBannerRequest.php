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
        \Log::info('StoreIndexBannerRequest - Validation rules requested', [
            'request_method' => $this->method(),
            'content_type' => $this->header('Content-Type'),
            'has_cover_image' => $this->hasFile('cover_image'),
            'all_input_keys' => array_keys($this->all()),
        ]);

        if ($this->hasFile('cover_image')) {
            $file = $this->file('cover_image');
            \Log::info('StoreIndexBannerRequest - File before validation', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_mime_type' => $file->getMimeType(),
                'is_valid' => $file->isValid(),
                'error_code' => $file->getError(),
                'error_message' => $file->getErrorMessage(),
            ]);
        } else {
            // Check if file exists but is invalid
            $allFiles = $this->allFiles();
            $fileInfo = [];
            
            if (isset($allFiles['cover_image'])) {
                $file = $allFiles['cover_image'];
                $fileInfo = [
                    'file_exists' => true,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_mime_type' => $file->getMimeType(),
                    'is_valid' => $file->isValid(),
                    'error_code' => $file->getError(),
                    'error_message' => $file->getErrorMessage(),
                    'temp_path' => $file->getRealPath(),
                    'temp_path_exists' => file_exists($file->getRealPath()),
                ];
            }
            
            \Log::warning('StoreIndexBannerRequest - No cover_image file found', [
                'hasFile_check' => false,
                'all_files_keys' => array_keys($allFiles),
                'cover_image_info' => $fileInfo,
                'input' => $this->except(['_token']),
            ]);
        }

        return [
            'title' => 'required|string',
            'link' => 'required|string',
            'cover_image' => 'required|image|max:2048',
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
