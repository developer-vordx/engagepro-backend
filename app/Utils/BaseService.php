<?php


namespace App\Utils;

use App\Models\ExceptionLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;

class BaseService extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'failure',
                'message' => 'Bad request',
                'errors' => $validator->errors(),
            ], 400)
        );
    }

    public function logException(\Exception $exception)
    {
        try {

            $user = auth()->user();

            $input = Request::except(array_keys(Request::allFiles()));
            $input = Arr::except($input, ['password', 'password_confirmation']);

            ExceptionLog::create([
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
                'url' => Request::fullUrl(),
                'input' => json_encode($input),
                'user_id' => $user ? $user->id : null,
            ]);
        } catch (\Throwable $e) {

            \Log::error('Error while logging exception to the database: ' . $e->getMessage());
        }
    }
}
