<?php

namespace App\Http\Middleware;

use App\DTO\Api\V1\AdminBackOffice\RequestLogsDTO\SaveRequestDTO;
use App\Models\RequestLog;
use Closure;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

class RequestLogMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true); // Use micro time for HTTP-request aligned measurement

        $response = $next($request); // Let the request fully process first

        $durationMs = round((microtime(true) - $startTime) * 1000, 2); // Convert to milliseconds

        // Log AFTER timing (doesn't affect measured duration)
        $agent = new Agent();
        $requestData = $this->filterRequestData($request);
        $log = RequestLog::create((new SaveRequestDTO($request, $agent, $requestData, $response, $durationMs))->toArray());

        $request['request_id'] = $log->id;
        return $response;
    }

    /**
     * @param Request $request
     * @return array
     */
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
}
