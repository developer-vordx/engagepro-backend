<?php

namespace App\Http\Middleware\Log;

use App\Models\RequestLog;
use Closure;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

class RequestLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $agent = new Agent();

        // Remove file uploads from request log
        $requestData = $this->filterRequestData($request);
        $user_id = auth()->id() ?? null;

        /** @var Response $response */
        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $logData = [
            'ip' => $request->ip(),
            'user_id' => $user_id,
            'device' => $agent->device(),
            'platform' => $agent->platform(),
            'platform_version' => $agent->version($agent->platform()),
            'browser' => $agent->browser(),
            'browser_version' => $agent->version($agent->browser()),
            'is_mobile' => $agent->isMobile(),
            'is_desktop' => $agent->isDesktop(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'request_data' => $requestData,
            'response_data' => $this->getResponseData($response),
            'status_code' => $response->getStatusCode(),
            'execution_time_ms' => $duration,
        ];

        $this->logRequest($logData);

        return $response;
    }

    protected function filterRequestData(Request $request): array
    {
        $data = $request->except(['password', 'password_confirmation']);

        foreach ($request->allFiles() as $key => $file) {
            if (isset($data[$key])) {
                $data[$key] = '[file omitted]';
            }
        }

        return $data;
    }

    protected function getResponseData(Response $response): array
    {
        if (method_exists($response, 'getContent')) {
            $content = $response->getContent();

            // Don't log binary responses (e.g., file downloads, images)
            if (!str_starts_with($response->headers->get('Content-Type'), 'application/json')) {
                return ['message' => 'Binary or non-JSON response omitted'];
            }

            $decoded = json_decode($content, true);
            return is_array($decoded) ? $decoded : ['raw' => $content];
        }

        return ['message' => 'Non-standard response'];
    }

    protected function logRequest(array $data): void
    {
        try {
            RequestLog::create($data);
        } catch (\Throwable $e) {
            \Log::warning('Request logging failed', ['error' => $e->getMessage()]);
        }
    }
}
