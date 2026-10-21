<?php
namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    private function ownNotifications()
    {
        return Notification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', Auth::id());
    }

    public function index()
    {
        $notifications = $this->ownNotifications()->latest()->paginate(10);
        return view('admin.notifications', compact('notifications'));
    }

    public function frontend_notify_listing()
    {
        $notifications = $this->ownNotifications()->latest()->paginate(10);
        return view('frontend.member.notifications', compact('notifications'));
    }

    public function notification_view($id)
    {
        // A notification UUID alone never grants permission to open its link.
        $notification = $this->ownNotifications()->findOrFail($id);
        $data = is_array($notification->data) ? (object) $notification->data : (object) json_decode((string) $notification->data, true);
        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        $fallback = Auth::user()?->user_type === 'admin'
            ? route('admin.notifications')
            : (Route::has('client.scope.notifications') ? route('client.scope.notifications') : route('dashboard'));

        $destination = trim((string) ($data->deep_link ?? $data->route ?? ''));
        if ($destination === '') return redirect($fallback);

        if (($data->type ?? '') === 'member_registration' && Route::has($destination)) {
            $member = User::query()->find((int) ($data->notify_by ?? 0));
            return $member ? redirect()->route($destination, [$member->membership]) : redirect($fallback);
        }

        // Only local URL paths and known internal route names are trusted.
        // Avoid an open redirect via a persisted notification's deep_link.
        if (Str::startsWith($destination, '/') && ! Str::startsWith($destination, '//')) {
            return redirect($destination);
        }
        if (Str::startsWith($destination, ['https://','http://'])) {
            $urlParts = parse_url($destination);
            $sameHost = strtolower((string)($urlParts['host'] ?? '')) === strtolower((string)request()->getHost());
            if ($sameHost) {
                $path = (string)($urlParts['path'] ?? '/');
                $query = isset($urlParts['query']) ? '?'.$urlParts['query'] : '';
                $fragment = isset($urlParts['fragment']) ? '#'.$urlParts['fragment'] : '';
                return redirect($path.$query.$fragment);
            }
            return redirect($fallback);
        }
        if (Route::has($destination)) return redirect()->route($destination);
        return redirect($fallback);
    }

    public function mark_all_as_read()
    {
        $this->ownNotifications()->whereNull('read_at')->update(['read_at' => now()]);
        flash('All notifications are marked as read.')->success();
        return back();
    }
}
