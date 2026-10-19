<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationDeliveryLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NotificationAuditController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'channel' => ['nullable','in:push,email,in_app'],
            'status' => ['nullable','in:queued,sent,delivered,read,failed'],
            'user_id' => ['nullable','integer','min:1'],
        ]);
        $logs = NotificationDeliveryLog::query()->with('user')
            ->when($data['channel'] ?? null, fn ($q,$v) => $q->where('channel',$v))
            ->when($data['status'] ?? null, fn ($q,$v) => $q->where('status',$v))
            ->when($data['user_id'] ?? null, fn ($q,$v) => $q->where('user_id',$v))
            ->latest('id')->paginate(30)->withQueryString();
        $summary = NotificationDeliveryLog::query()
            ->selectRaw('channel,status,COUNT(*) as total')
            ->groupBy('channel','status')->get();
        return view('admin.notification_audit.index', compact('logs','summary'));
    }
}
