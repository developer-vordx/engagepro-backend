<?php

namespace App;

use App\DTO\Api\V1\AdminBackOffice\RequestLogsDTO\ErrorLogsDTO;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Models\ExceptionLog;
use App\Utils\BaseService;

class Helper extends BaseService
{

    /**
     * @param $response
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function response($response, int $statusCode): JsonResponse
    {
        $data = ['message' => ResponseAlias::$statusTexts[$statusCode]];
        if ($statusCode < ResponseAlias::HTTP_BAD_REQUEST) {
            $data['data'] = (array)$response;
        } else {
            $data['errors'] = (array)$response;
        }
        return response()->json($data, $statusCode);
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

        } catch (\Exception $e) {
            Log::error('Error during saving error logs: ' . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Error during saving error logs:',
                'errors' => (array)$e->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param bool $asForm
     * @param string $platform
     * @return array|null
     * @throws ConnectionException
     */
    public static function makeHttpRequest(string $method, string $url, array $data = [], array $headers = [], bool $asForm = false, string $platform = ''): ?array
    {
        $httpClient = Http::timeout(30);

        if ($asForm) {
            $httpClient = $httpClient->asForm();
        }

        if (!empty($headers)) {
            $httpClient = $httpClient->withHeaders($headers);
        }

        $response = match (strtoupper($method)) {
            'GET' => $httpClient->get($url, $data),
            'POST' => $httpClient->post($url, $data),
            'PUT' => $httpClient->put($url, $data),
            'DELETE' => $httpClient->delete($url, $data),
            default => null
        };
        if ($platform == 'tiktok') {
            if (isset($response->json()['error']['code']) && $response->json()['error']['code'] != 'ok'){
                return [
                    'header_code' => ResponseAlias::HTTP_EXPECTATION_FAILED,
                    'body' => $response->json()['error']['message'],
                ];
            }
            elseif (isset($response->json()['error_description'])){
                return [
                    'header_code' => ResponseAlias::HTTP_EXPECTATION_FAILED,
                    'body' => $response->json()['error_description'],
                ];
            }else{
                return [
                    'header_code' => ResponseAlias::HTTP_OK,
                    'body' => $response->json(),
                ];
            }
        }
        return [
            'header_code' => $response->status(),
            'body' => $response->json(),
        ];
    }
}
