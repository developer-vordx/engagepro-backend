<?php

namespace App\DTO\Api\V1\AdminBackOffice\RequestLogsDTO;

use App\Utils\BaseDTO;
use Symfony\Component\HttpFoundation\Response;

class SaveRequestDTO extends BaseDTO
{
    public mixed $ip;
    public mixed $user_id;
    public mixed $device;
    public mixed $platform;
    public mixed $platform_version;
    public mixed $browser;
    public mixed $browser_version;
    public mixed $is_mobile;
    public mixed $is_desktop;
    public mixed $method;
    public mixed $url;
    public mixed $request_data;
    public mixed $response_data;
    public mixed $status_code;
    public mixed $execution_time_ms;
    public mixed $created_at;

    public function __construct($request, $agent, $requestData, $response, $duration)
    {
        $this->ip = $request->ip();
        $this->user_id = auth()?->user()?->id;
        $this->device = $agent->device();
        $this->platform = $agent->platform();
        $this->platform_version = $agent->version($agent->platform());
        $this->browser = $agent->browser();
        $this->browser_version = $agent->version($agent->browser());
        $this->is_mobile = $agent->isMobile();
        $this->is_desktop = $agent->isDesktop();
        $this->method = $request->method();
        $this->url = $request->fullUrl();
        $this->request_data = $requestData;
        $this->response_data = $this->getResponseData($response);
        $this->status_code = $response->getStatusCode();
        $this->execution_time_ms = $duration;
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
}
