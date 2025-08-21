<?php

namespace App\DTO\Api\V1\AdminBackOffice\RequestLogsDTO;

use App\Utils\BaseDTO;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;

class ErrorLogsDTO extends BaseDTO
{
    public mixed $message;
    public mixed $trace;
    public mixed $line;
    public mixed $file;
    public mixed $code;
    public mixed $url;
    public mixed $input;
    public mixed $user_id;

    public function __construct($exception)
    {
        $input = Request::except(array_keys(Request::allFiles()));
        $input = Arr::except($input, ['password', 'password_confirmation']);
        $this->message = $exception->getMessage();
        $this->trace = $exception->getTraceAsString();
        $this->file = $exception->getFile();
        $this->line = $exception->getLine();
        $this->code = $exception->getCode();
        $this->url = Request::fullUrl();
        $this->input = json_encode($input);
        $this->user_id = auth()->user()?->id;
    }
}
