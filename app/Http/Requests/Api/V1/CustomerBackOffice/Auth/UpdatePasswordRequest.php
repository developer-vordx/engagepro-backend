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
            'new_password' => 'required|confirmed|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/',
            'new_password_confirmation' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Current password is required',
            'current_password.max' => 'Current password must be at least 150 characters',
            'new_password.required' => 'New password is required',
            'new_password.confirmed' => 'Password confirmation does not match',
            'new_password.min' => 'Password must be at least 8 characters',
            'new_password.regex' => 'Password must include at least one uppercase letter, one lowercase letter, one number, and one special character',
            'new_password_confirmation.required' => 'Password confirmation is required',
        ];
    }
}
