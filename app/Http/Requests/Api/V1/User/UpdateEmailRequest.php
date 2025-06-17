<?php

namespace App\Http\Requests\Api\V1\User;

use App\Utils\BaseRequest;

class UpdateEmailRequest extends BaseRequest
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
            'otp' => 'required|string|min:6',
            'email' => 'required|email|different:current_email',
        ];
    }
}
