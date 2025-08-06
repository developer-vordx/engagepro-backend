<?php

namespace App\Http\Requests\Api\V1\CustomerBackOffice\Auth;

use App\Rules\Api\V1\CustomerBackOffice\Auth\LoginRule;
use App\Utils\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class LoginRequest extends BaseRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required','email','exists:customers,email' , new LoginRule()],
            'password' => 'required|string|max:255'
        ];
    }
}
