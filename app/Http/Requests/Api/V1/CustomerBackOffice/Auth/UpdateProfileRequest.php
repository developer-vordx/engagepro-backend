<?php

namespace App\Http\Requests\Api\V1\CustomerBackOffice\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Utils\BaseRequest;

class UpdateProfileRequest extends BaseRequest
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
            'name' => 'required|max:100',
            'phone' => 'required|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'name.max' => 'Name must be less than 100 characters',
            'phone.required' => 'Phone is required',
            'phone.max' => 'Phone must be less than 20 characters',
        ];
    }
}
