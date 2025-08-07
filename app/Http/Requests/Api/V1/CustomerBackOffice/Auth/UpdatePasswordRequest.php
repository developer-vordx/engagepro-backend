<?php

namespace App\Http\Requests\Api\V1\CustomerBackOffice\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Utils\BaseRequest;

class UpdatePasswordRequest extends BaseRequest
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
            'current_password' => 'required|max:150',
            'password' => 'required|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{12,}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Current password is required',
            'current_password.max' => 'Current password must be at least 150 characters',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password should match the confirmed password.',
            'password.regex' => 'Password must be at least 12 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ];
    }
}
