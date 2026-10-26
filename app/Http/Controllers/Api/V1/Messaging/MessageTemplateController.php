<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Messaging;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\MessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Send Interest / Send Proposal message library (active, non-empty rows only). */
class MessageTemplateController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $type = (string) $request->query('type', '');
        $language = (string) $request->query('language', 'en');

        $rows = MessageTemplate::query()
            ->where('status', 'active')
            ->when(in_array($type, ['interest', 'proposal'], true), fn ($q) => $q->where('type', $type))
            ->where('language', $language !== '' ? $language : 'en')
            ->orderBy('display_order')->orderBy('id')
            ->get()
            ->filter(fn ($t) => filled(trim((string) $t->opening)) || filled(trim((string) $t->body)) || filled(trim((string) $t->closing)))
            ->values()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'title' => $t->title,
                'opening' => (string) $t->opening,
                'body' => (string) $t->body,
                'closing' => (string) $t->closing,
                'language' => $t->language,
                'status' => $t->status,
                'display_order' => (int) $t->display_order,
            ])->all();

        return $this->success($rows);
    }
}
