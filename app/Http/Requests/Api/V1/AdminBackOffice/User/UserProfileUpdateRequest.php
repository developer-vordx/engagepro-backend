<?php

namespace App\Http\Requests\Api\V1\AdminBackOffice\User;

use App\Utils\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UserProfileUpdateRequest extends BaseRequest
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
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
//            'email' => ['required', 'email'],
//            'phone' => ['required', 'string'],
//            'gender' => ['required', 'string'],
//            'birthday' => ['required', 'date'],

        ];
    }
}
