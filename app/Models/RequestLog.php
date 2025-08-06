<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    protected $fillable = [
        'ip', 'device', 'platform', 'platform_version',
        'browser', 'browser_version', 'is_mobile', 'is_desktop',
        'method', 'url', 'request_data', 'response_data',
        'status_code', 'execution_time_ms'
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];
}
