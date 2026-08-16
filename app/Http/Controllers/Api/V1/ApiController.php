<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        return ApiResponse::success($data, $message, $status, $meta);
    }

    protected function error(
        string $message,
        int $status = 400,
        ?string $code = null,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        return ApiResponse::error($message, $status, $code, $errors, $meta);
    }
}

