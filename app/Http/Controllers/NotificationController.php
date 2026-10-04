<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use Auth;
use Str;
use Illuminate\Support\Facades\Route;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $notifications = Notification::latest()->where('notifiable_id',Auth()->user()->id)->paginate(10);
        return view('admin.notifications',compact('notifications'));
    }

    public function frontend_notify_listing()
    {
        $notifications = Notification::latest()->where('notifiable_id',Auth()->user()->id)->paginate(10);
        return view('frontend.member.notifications',compact('notifications'));
    }

    public function notification_view($id)
    {
        $notification = Notification::findOrFail($id);
        $notification_data = is_array($notification->data) ? (object)$notification->data : (is_string($notification->data) ? json_decode($notification->data) : (object)$notification->data);

        // Notification seen
        if($notification->read_at == null)
        {
            $notification->read_at = date('Y-m-d');
            $notification->save();
        }

        $destination = $notification_data->deep_link ?? $notification_data->route ?? '/dashboard';

        if($notification_data->type == 'member_registration' && !Str::contains($destination,'http'))
        {
            $membership = User::where('id',$notification_data->notify_by)->first()->membership;
            return redirect()->route($destination, $membership);
        }
        else {
            if(Str::startsWith($destination, ['http://', 'https://', '/'])){
                return redirect($destination);
            }
            if (Route::has($destination)) {
                return redirect()->route($destination);
            }

            return redirect('/dashboard');
        }

    }

    public function mark_all_as_read(){
        $notifications = Notification::where('notifiable_id',Auth::user()->id)->where('read_at',null)->get();
        foreach($notifications as $notification){
            $notification->read_at = date('Y-m-d');
            $notification->save();
        }
        flash('All notifications are marked as read.')->success();
        return back();
    }
}
