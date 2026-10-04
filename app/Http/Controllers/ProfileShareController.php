<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
class ProfileShareController extends Controller
{
    public function show(Request $request, User $user)
    {
        abort_unless($user->user_type === 'member' && !$user->blocked && !$user->deactivated, 404);
        abort_if((bool)($user->member?->hide_profile), 404);
        if (!$request->user()) return redirect()->route('user.login')->with('url.intended', route('profile.share',$user));
        if ($request->user()->id === $user->id) return redirect()->route('dashboard');
        return redirect()->route('member_profile', ['id'=>$user->id]);
    }
}
