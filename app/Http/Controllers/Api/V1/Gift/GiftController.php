<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Gift;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Gift\SendGiftRequest;
use App\Http\Resources\Api\V1\Gift\GiftResource;
use App\Http\Resources\Api\V1\Gift\GiftTransactionResource;
use App\Models\Gift;
use App\Services\Api\V1\Gift\GiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiftController extends ApiController
{
    public function __construct(private readonly GiftService $gifts)
    {
    }

    /** GET /gifts */
    public function index(): JsonResponse
    {
        return GiftResource::collection($this->gifts->list())
            ->additional(['success' => true, 'message' => 'Gifts fetched successfully.'])
            ->response();
    }

    /** GET /gifts/{gift} */
    public function show(Gift $gift): JsonResponse
    {
        return $this->success(new GiftResource($gift), 'Gift fetched successfully.');
    }

    /** POST /gifts/send */
    public function send(SendGiftRequest $request): JsonResponse
    {
        return $this->success(
            $this->gifts->send(
                $request->user(),
                (int) $request->integer('receiver_id'),
                (int) $request->integer('gift_id'),
                $request->validated('message'),
            ),
            'Gift sent successfully.'
        );
    }

    /** GET /gifts/received */
    public function received(Request $request): JsonResponse
    {
        return GiftTransactionResource::collection($this->gifts->received($request->user(), $request->integer('per_page', 30)))
            ->additional(['success' => true])
            ->response();
    }

    /** GET /gifts/sent */
    public function sent(Request $request): JsonResponse
    {
        return GiftTransactionResource::collection($this->gifts->sent($request->user(), $request->integer('per_page', 30)))
            ->additional(['success' => true])
            ->response();
    }

    /** GET /gifts/transactions/{transaction} */
    public function transaction(Request $request, int $transaction): JsonResponse
    {
        return $this->success(
            new GiftTransactionResource($this->gifts->transactionDetail($request->user(), $transaction)),
            'Gift transaction fetched successfully.'
        );
    }
}
