<?php

namespace App;

use App\DTO\Api\V1\AdminBackOffice\RequestLogsDTO\ErrorLogsDTO;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Models\ExceptionLog;
use App\Utils\BaseService;

class Helper extends BaseService
{

    /**
     * @param string $message
     * @param $data
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function response(string $message, $data, int $statusCode): JsonResponse
    {
        $response = ['message' => $message];
        if ($statusCode < 400) {
            $response['data'] = (array)$data;
        } else {
            $response['errors'] = (array)$data;
        }
        return response()->json($response, $statusCode);
    }


    /**
     * @param mixed $e
     * @return JsonResponse
     */
    public static function errors(mixed $e): JsonResponse
    {
        try {
            ExceptionLog::create((new ErrorLogsDTO($e))->toArray());

            $errorException = $e->getMessage() . 'on line ' . $e->getLine() . ' in ' . $e->getFile();
            return response()->json([
                'message' => 'Something went wrong',
                'errors' => (array)$errorException,
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);

        }catch (\Exception $e) {
             Log::error('Error during saving error logs: ' . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
