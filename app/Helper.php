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


    /**
     * Create PKCE challenge and verifier
     * @param string $sessionKey
     * @return array
     */
    public static function generatePKCE(string $sessionKey): array
    {
        $codeVerifier = bin2hex(random_bytes(64));
        session([$sessionKey => $codeVerifier]);

        $codeChallenge = rtrim(
            strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'),
            '='
        );

        return [
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256'
        ];
    }

    /**
     * Validate file for social media upload
     * @param string $filePath
     * @param array $allowedMimes
     * @param int $maxSizeBytes
     * @return array
     */
    public static function validateMediaFile(
        string $filePath,
        array $allowedMimes,
        int $maxSizeBytes
    ): array {
        if (!file_exists($filePath)) {
            return [
                'valid' => false,
                'error' => "File does not exist: {$filePath}"
            ];
        }

        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath);

        if (!in_array($mimeType, $allowedMimes)) {
            return [
                'valid' => false,
                'error' => "File type {$mimeType} not supported. Allowed: " . implode(', ', $allowedMimes)
            ];
        }

        if ($fileSize > $maxSizeBytes) {
            return [
                'valid' => false,
                'error' => "File size " . round($fileSize / (1024 * 1024), 2) . "MB exceeds limit of " . round($maxSizeBytes / (1024 * 1024), 2) . "MB"
            ];
        }

        return [
            'valid' => true,
            'size' => $fileSize,
            'mime_type' => $mimeType,
        ];
    }

    /**
     * Build Basic Auth header
     * @param string $username
     * @param string $password
     * @return string
     */
    public static function buildBasicAuthHeader(string $username, string $password): string
    {
        return 'Basic ' . base64_encode($username . ':' . $password);
    }

    /**
     * Truncate text to max length with ellipsis
     * @param string|null $text
     * @param int $maxLength
     * @return string|null
     */
    public static function truncateText(?string $text, int $maxLength): ?string
    {
        if (!$text || strlen($text) <= $maxLength) {
            return $text;
        }

        return substr($text, 0, $maxLength - 3) . '...';
    }

    /**
     * Parse scopes from various formats (string, array, JSON)
     * @param mixed $scopes
     * @return array
     */
    public static function parseScopes(mixed $scopes): array
    {
        if (is_array($scopes)) {
            return $scopes;
        }

        if (is_string($scopes)) {
            // Try JSON decode first
            $decoded = json_decode($scopes, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            // Try comma-separated
            if (str_contains($scopes, ',')) {
                return array_map('trim', explode(',', $scopes));
            }

            // Try space-separated
            if (str_contains($scopes, ' ')) {
                return array_map('trim', explode(' ', $scopes));
            }

            // Single scope
            return [$scopes];
        }

        return [];
    }
}

