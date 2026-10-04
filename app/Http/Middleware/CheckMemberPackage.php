<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckMemberPackage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            // Guardians act on behalf of invited members and are exempt from buying separate packages
            $isGuardian = \App\Models\FamilyGuardianLink::where('guardian_user_id', $user->id)
                ->where('status', 'approved')
                ->whereNull('revoked_at')
                ->exists();

            if ($isGuardian) {
                return $next($request);
            }

            if ($user->member && is_null($user->member->current_package_id)) {
                if (!$request->routeIs(['home', 'packages', 'package_payment_methods', 'free_package_purchase', 'package_payment.invoice', 'package_purchase_history', 'package.payment', 'guardian_panel.*', 'guardian_mode.*'])) {
                    flash(translate('Please purchase a package first.'))->warning();
                    return redirect()->route('packages');
                }
            }
        }

        return $next($request);
    }
}
