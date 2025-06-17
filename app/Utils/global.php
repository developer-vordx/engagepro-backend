<?php

use Illuminate\Support\Str;

if (!function_exists('success')) {
    function success(string $message, $data = [], int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}

if (!function_exists('errors')) {
    function errors(string $message, $data = null, int $status = 400): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        return Str::slug($text,'-');
    }
}


if (!function_exists('preparePagination')) {
    function preparePagination($pagination): array
    {
        return [
            'items' => $pagination->items(),
            'per_page' => $pagination->perPage(),
            'total_records' => $pagination->total(),
            'current_page' => $pagination->currentPage(),
            'previous_page' => $pagination->previousPageUrl(),
            'next_page' => $pagination->nextPageUrl(),
            'last_page' => $pagination->lastPage(),
        ];
    }
}

