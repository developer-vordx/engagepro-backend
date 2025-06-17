<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiException extends Exception
{
    protected int $status;
    protected array $errors;

    public function __construct(string $message = 'Something went wrong', int $status = 400, array $errors = [])
    {
        parent::__construct($message);
        $this->status = $status;
        $this->errors = $errors;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public static function fromException(Exception $e): self
    {
        $status = 500;
        $message = $e->getMessage();
        $errors = [];

        switch (true) {
            case $e instanceof QueryException:
                $status = 500;
                $message = 'Database error occurred.';
                break;

            case $e instanceof ValidationException:
                $status = 422;
                $message = 'The given data was invalid.';
                $errors = $e->validator->errors()->toArray();
                break;

            case $e instanceof AuthenticationException:
                $status = 401;
                $message = 'Unauthenticated.';
                break;

            case $e instanceof AuthorizationException:
                $status = 403;
                $message = 'This action is unauthorized.';
                break;

            case $e instanceof ModelNotFoundException:
                $status = 404;
                $message = 'Resource not found.';
                break;

            case $e instanceof NotFoundHttpException:
                $status = 404;
                $message = 'Endpoint not found.';
                break;

            case $e instanceof HttpException:
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: 'HTTP error occurred.';
                break;

            default:
                if (method_exists($e, 'getStatusCode')) {
                    $status = $e->getStatusCode();
                    $message = $e->getMessage() ?: 'An error occurred.';
                } else {
                    $status = 500;
                    $message = 'Internal server error.';
                }
                break;
        }

        return new self($message, $status, $errors);
    }
}
