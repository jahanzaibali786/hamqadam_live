<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Gift;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Gift;
use App\Models\GiftTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin gift management: view/create/edit gifts, change prices, enable or
 * disable, reorder, and basic usage statistics. Members only ever see
 * is_active = true gifts — price changes reach both clients instantly because
 * they always read this table.
 */
class GiftAdminController extends ApiController
{
    /** GET /admin/gifts — all gifts including inactive. */
    public function index(): JsonResponse
    {
        return $this->success(
            Gift::orderBy('sort_order')->get(),
            'Gifts fetched successfully.'
        );
    }

    /** POST /admin/gifts */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['required', 'string', 'max:60', 'unique:gifts,slug'],
            'thumbnail' => ['required', 'string', 'max:255'],
            'animated_asset' => ['required', 'string', 'max:255'],
            'coins' => ['required', 'integer', 'min:1'],
            'category' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return $this->success(Gift::create($data), 'Gift created.', 201);
    }

    /** PATCH /admin/gifts/{gift} — price, name, assets, sort order, active flag. */
    public function update(Request $request, Gift $gift): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:60'],
            'thumbnail' => ['sometimes', 'string', 'max:255'],
            'animated_asset' => ['sometimes', 'string', 'max:255'],
            'coins' => ['sometimes', 'integer', 'min:1'],
            'category' => ['sometimes', 'string', 'max:40'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $gift->fill($data)->save();

        return $this->success($gift->fresh(), 'Gift updated.');
    }

    /** DELETE /admin/gifts/{gift} — soft-disable by default keeps history intact. */
    public function destroy(Request $request, Gift $gift): JsonResponse
    {
        // A gift with history must not disappear from members' gift lists —
        // deactivate instead of delete.
        if (GiftTransaction::where('gift_id', $gift->id)->exists() || true) {
            $gift->forceFill(['is_active' => false])->save();

            return $this->success(message: 'Gift deactivated (history preserved).');
        }
    }

    /** GET /admin/gifts/stats — usage analytics. */
    public function stats(): JsonResponse
    {
        return $this->success([
            'total_gifts_sent' => (int) GiftTransaction::where('status', 'sent')->count(),
            'total_coins_consumed' => (int) GiftTransaction::where('status', 'sent')->sum('coins'),
            'most_sent' => GiftTransaction::query()
                ->select('gift_id', DB::raw('COUNT(*) as sends'))
                ->where('status', 'sent')
                ->groupBy('gift_id')
                ->orderByDesc('sends')
                ->with('gift:id,name')
                ->first(),
            'usage_by_gift' => GiftTransaction::query()
                ->select('gift_id', DB::raw('COUNT(*) as sends'), DB::raw('SUM(coins) as coins'))
                ->where('status', 'sent')
                ->groupBy('gift_id')
                ->with('gift:id,name')
                ->get(),
            'usage_by_date' => GiftTransaction::query()
                ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as sends'))
                ->where('status', 'sent')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('day')
                ->orderBy('day')
                ->get(),
            'top_senders' => GiftTransaction::query()
                ->select('sender_id', DB::raw('COUNT(*) as sends'))
                ->where('status', 'sent')
                ->groupBy('sender_id')
                ->orderByDesc('sends')
                ->limit(10)
                ->with('sender:id,first_name,last_name')
                ->get(),
            'top_receivers' => GiftTransaction::query()
                ->select('receiver_id', DB::raw('COUNT(*) as gifts'))
                ->where('status', 'sent')
                ->groupBy('receiver_id')
                ->orderByDesc('gifts')
                ->limit(10)
                ->with('receiver:id,first_name,last_name')
                ->get(),
        ], 'Gift statistics fetched successfully.');
    }
}
