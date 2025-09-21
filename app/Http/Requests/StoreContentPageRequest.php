<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentPageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' || $this->user()?->role === 'super-admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'page_key' => 'required|string|max:100|unique:content_pages,page_key',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'page_key.required' => 'The page key is required.',
            'page_key.unique' => 'A content page with this key already exists.',
            'title.required' => 'The title is required.',
            'content.required' => 'The content is required.',
        ];
    }
}
