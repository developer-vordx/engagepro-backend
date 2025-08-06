<?php


namespace App\Utils;

use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Helper;

class BaseRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Helper::response(
            'One or more fields have an error',
            $validator->errors()->all(),
            ResponseAlias::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
