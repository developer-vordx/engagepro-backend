<?php

namespace App\Http\Requests\Api\V1\AdminBackOffice\Auth;

use App\Utils\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ResetPasswordRequest extends BaseRequest
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
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
            'email' => ['required', 'email', 'exists:users,email'], // Only one "exists" here
        ];
    }

    /**
     * Add custom validation after basic rules.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $email = $this->input('email');

            // Custom check: must also exist in password_reset_token_users table
            $existsInResetTable = \DB::table('password_reset_token_users')->where('email', $email)->exists();

            if (!$existsInResetTable) {
                $validator->errors()->add('email', 'The selected email is not valid for password reset.');
            }
        });
    }
}
