<?php


namespace App\Utils;

use App\Models\ExceptionLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;

class BaseService
{

    public function logException(\Throwable $exception)
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
                'user_id' => $user?->id,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error while logging exception to the database: ' . $e->getMessage());
        }
    }
}
