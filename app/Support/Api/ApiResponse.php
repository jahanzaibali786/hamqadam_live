<?php

declare(strict_types=1);

namespace App\Support\Api;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => self::normalizeData($data),
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function error(
        string $message,
        int $status = 400,
        ?string $code = null,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
            'error' => [
                'code' => $code ?? self::defaultErrorCode($status),
                'errors' => $errors,
            ],
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function paginated(Paginator|CursorPaginator $paginator, ?string $message = null): JsonResponse
    {
        return self::success(
            data: $paginator->items(),
            message: $message,
            meta: [
                'pagination' => self::paginationMeta($paginator),
            ]
        );
    }

    private static function normalizeData(mixed $data): mixed
    {
        if ($data instanceof JsonResource) {
            return $data->resolve();
        }

        if ($data instanceof Collection) {
            return $data->values();
        }

        return $data;
    }

    private static function paginationMeta(Paginator|CursorPaginator $paginator): array
    {
        $meta = [
            'per_page' => $paginator->perPage(),
            'has_more_pages' => $paginator->hasMorePages(),
        ];

        if ($paginator instanceof CursorPaginator) {
            return $meta + [
                'next_cursor' => optional($paginator->nextCursor())->encode(),
                'previous_cursor' => optional($paginator->previousCursor())->encode(),
            ];
        }

        return $meta + Arr::only($paginator->toArray(), [
            'current_page',
            'last_page',
            'from',
            'to',
            'total',
        ]);
    }

    private static function defaultErrorCode(int $status): string
    {
        return match ($status) {
            400 => 'bad_request',
            401 => 'unauthenticated',
            403 => 'forbidden',
            404 => 'not_found',
            409 => 'conflict',
            422 => 'validation_failed',
            429 => 'too_many_requests',
            default => $status >= 500 ? 'server_error' : 'request_failed',
        };
    }
}

