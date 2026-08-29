<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\CallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CallController extends ApiController
{
    public function __construct(private readonly CallService $calls)
    {
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chat_thread_id' => ['required', 'integer', 'exists:chat_threads,id'],
            'call_type' => ['required', 'string', 'in:audio,video'],
        ]);

        try {
            return response()->json([
                'success' => true,
                'message' => translate('Calling...'),
                'data' => $this->calls->start($request->user(), (int) $data['chat_thread_id'], $data['call_type']),
            ]);
        } catch (Throwable $throwable) {
            $status = method_exists($throwable, 'statusCode') ? $throwable->statusCode() : 500;
            $message = $throwable->getMessage() ?: translate('Calling service unavailable.');
            Log::error('API call start failed.', ['message' => $throwable->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => [
                    'code' => method_exists($throwable, 'errorCode') ? $throwable->errorCode() : null,
                    'errors' => method_exists($throwable, 'errors') ? $throwable->errors() : [],
                ],
            ], $status);
        }
    }

    public function accept(Request $request, int $call): JsonResponse
    {
        return $this->handle(fn () => $this->calls->accept($request->user(), $call), translate('Call accepted.'));
    }

    public function reject(Request $request, int $call): JsonResponse
    {
        return $this->handle(fn () => $this->calls->reject($request->user(), $call), translate('Call declined.'));
    }

    public function cancel(Request $request, int $call): JsonResponse
    {
        return $this->handle(fn () => $this->calls->cancel($request->user(), $call), translate('Call cancelled.'));
    }

    public function connect(Request $request, int $call): JsonResponse
    {
        return $this->handle(fn () => $this->calls->connect($request->user(), $call), translate('Call connected.'));
    }

    public function end(Request $request, int $call): JsonResponse
    {
        return $this->handle(fn () => $this->calls->end($request->user(), $call, $request->input('status')), translate('Call ended.'));
    }

    public function missed(Request $request, int $call): JsonResponse
    {
        return $this->handle(fn () => $this->calls->end($request->user(), $call, 'missed'), translate('Missed call.'));
    }

    public function history(Request $request, int $thread): JsonResponse
    {
        return $this->success($this->calls->history($request->user(), $thread, (int) $request->input('per_page', 20)), 'Call history loaded.');
    }

    public function show(Request $request, int $call): JsonResponse
    {
        return $this->success($this->calls->get($request->user(), $call), 'Call details loaded.');
    }

    private function handle(callable $callback, string $message): JsonResponse
    {
        try {
            return $this->success($callback(), $message);
        } catch (Throwable $throwable) {
            $status = method_exists($throwable, 'statusCode') ? $throwable->statusCode() : 500;
            Log::error('API call action failed.', ['message' => $throwable->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage() ?: translate('Calling service unavailable.'),
                'error' => [
                    'code' => method_exists($throwable, 'errorCode') ? $throwable->errorCode() : null,
                    'errors' => method_exists($throwable, 'errors') ? $throwable->errors() : [],
                ],
            ], $status);
        }
    }
}
