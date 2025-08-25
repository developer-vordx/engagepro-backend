<?php

namespace App\Http\Requests\Api\V1\CustomerBackOffice\Social;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Utils\BaseRequest;

class HandleCallback extends BaseRequest
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
            'code' => 'required|string',
            'state' => 'nullable|string',
        ];
    }
}
