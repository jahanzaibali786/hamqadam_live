<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Services\CallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CallController extends Controller
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
            $result = $this->calls->start($request->user(), (int) $data['chat_thread_id'], $data['call_type']);
            return response()->json([
                'success' => true,
                'message' => translate('Calling...'),
                'data' => $result,
            ]);
        } catch (ApiException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'error' => [
                    'code' => $exception->errorCode(),
                    'errors' => $exception->errors(),
                ],
            ], $exception->statusCode());
        } catch (Throwable $throwable) {
            Log::error('Call start failed.', ['message' => $throwable->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => translate('Calling service unavailable.'),
            ], 500);
        }
    }

    public function accept(Request $request, int $call): JsonResponse
    {
        try {
            $result = $this->calls->accept($request->user(), $call);
            return response()->json([
                'success' => true,
                'message' => translate('Call accepted.'),
                'data' => $result,
            ]);
        } catch (ApiException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'error' => [
                    'code' => $exception->errorCode(),
                    'errors' => $exception->errors(),
                ],
            ], $exception->statusCode());
        } catch (Throwable $throwable) {
            Log::error('Call accept failed.', ['message' => $throwable->getMessage()]);
            return response()->json(['success' => false, 'message' => translate('Calling service unavailable.')], 500);
        }
    }

    public function reject(Request $request, int $call): JsonResponse
    {
        try {
            $result = $this->calls->reject($request->user(), $call);
            return response()->json([
                'success' => true,
                'message' => translate('Call declined.'),
                'data' => $result,
            ]);
        } catch (ApiException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'error' => [
                    'code' => $exception->errorCode(),
                    'errors' => $exception->errors(),
                ],
            ], $exception->statusCode());
        } catch (Throwable $throwable) {
            Log::error('Call reject failed.', ['message' => $throwable->getMessage()]);
            return response()->json(['success' => false, 'message' => translate('Calling service unavailable.')], 500);
        }
    }

    public function cancel(Request $request, int $call): JsonResponse
    {
        try {
            $result = $this->calls->cancel($request->user(), $call);
            return response()->json([
                'success' => true,
                'message' => translate('Call cancelled.'),
                'data' => $result,
            ]);
        } catch (ApiException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'error' => [
                    'code' => $exception->errorCode(),
                    'errors' => $exception->errors(),
                ],
            ], $exception->statusCode());
        } catch (Throwable $throwable) {
            Log::error('Call cancel failed.', ['message' => $throwable->getMessage()]);
            return response()->json(['success' => false, 'message' => translate('Calling service unavailable.')], 500);
        }
    }

    public function connect(Request $request, int $call): JsonResponse
    {
        try {
            $result = $this->calls->connect($request->user(), $call);
            return response()->json([
                'success' => true,
                'message' => translate('Call connected.'),
                'data' => $result,
            ]);
        } catch (ApiException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'error' => [
                    'code' => $exception->errorCode(),
                    'errors' => $exception->errors(),
                ],
            ], $exception->statusCode());
        } catch (Throwable $throwable) {
            Log::error('Call connect failed.', ['message' => $throwable->getMessage()]);
            return response()->json(['success' => false, 'message' => translate('Calling service unavailable.')], 500);
        }
    }

    public function end(Request $request, int $call): JsonResponse
    {
        try {
            $result = $this->calls->end($request->user(), $call, $request->input('status'));
            return response()->json([
                'success' => true,
                'message' => translate('Call ended.'),
                'data' => $result,
            ]);
        } catch (ApiException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'error' => [
                    'code' => $exception->errorCode(),
                    'errors' => $exception->errors(),
                ],
            ], $exception->statusCode());
        } catch (Throwable $throwable) {
            Log::error('Call end failed.', ['message' => $throwable->getMessage()]);
            return response()->json(['success' => false, 'message' => translate('Calling service unavailable.')], 500);
        }
    }

    public function missed(Request $request, int $call): JsonResponse
    {
        try {
            $result = $this->calls->end($request->user(), $call, 'missed');
            return response()->json([
                'success' => true,
                'message' => translate('Missed call.'),
                'data' => $result,
            ]);
        } catch (ApiException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'error' => [
                    'code' => $exception->errorCode(),
                    'errors' => $exception->errors(),
                ],
            ], $exception->statusCode());
        } catch (Throwable $throwable) {
            Log::error('Call missed failed.', ['message' => $throwable->getMessage()]);
            return response()->json(['success' => false, 'message' => translate('Calling service unavailable.')], 500);
        }
    }
}
