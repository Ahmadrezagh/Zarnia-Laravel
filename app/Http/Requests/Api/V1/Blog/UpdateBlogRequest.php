<?php

namespace App\Http\Requests\Api\V1\Blog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogRequest extends FormRequest
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
        $blog = $this->route('blog');

        return [
            'title' => 'required|string',
            'slug' => ['required', 'string', 'max:255', Rule::unique('blogs', 'slug')->ignore($blog?->id)],
            'description' => 'required|string',
            'cover_image' => 'nullable',
        ];
    }
}
