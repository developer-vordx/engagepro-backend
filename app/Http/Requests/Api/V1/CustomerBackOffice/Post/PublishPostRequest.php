<?php

namespace App\Http\Requests\Api\V1\CustomerBackOffice\Post;

use Illuminate\Foundation\Http\FormRequest;

class PublishPostRequest extends FormRequest
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
            'platforms' => 'nullable|array',
            'platforms.*' => 'string|in:tiktok,x,instagram,facebook,youtube,linkedin'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'platforms.*.in' => 'Invalid platform selected for publishing'
        ];
    }
}
