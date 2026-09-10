<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ManualReviewReadOnly
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'isUnderManualReview') || ! $user->isUnderManualReview()) {
            return $next($request);
        }

        $routeName = (string) optional($request->route())->getName();
        $legacyMutation = preg_match('/(destroy|delete|purchase|recharge|store|accept|reject|remove|add|withdraw|cancel|clear|block|unblock|report|send|update|create|edit|activate|view_contact)/i', $routeName) === 1;
        $profileSettingsRoute = $request->routeIs([
            'member.introduction.update', 'member.basic_info_update', 'member.language_info_update',
            'address.store', 'address.update',
            'education.store', 'education.create', 'education.edit', 'education.update',
            'education.update_highest_degree', 'education.update_education_present_status',
            'career.store', 'career.create', 'career.edit', 'career.update', 'career.update_career_present_status',
            'physical-attribute.store', 'physical-attribute.update',
            'hobbies.store', 'hobbies.update', 'attitudes.store', 'attitudes.update',
            'recidencies.store', 'recidencies.update', 'lifestyles.store', 'lifestyles.update',
            'astrologies.store', 'astrologies.update', 'families.store', 'families.update',
            'spiritual_backgrounds.store', 'spiritual_backgrounds.update',
            'partner_expectations.store', 'partner_expectations.update',
            'additional_member_info.update',
            'states.get_state_by_country', 'cities.get_cities_by_state',
            'castes.get_caste_by_religion', 'sub_castes.get_sub_castes_by_religion',
            'gallery-image.store', 'gallery-image.update',
            'api.v1.profile.update', 'api.v1.profile.privacy.update', 'api.v1.profile.visibility.update',
            'api.v1.partner_preferences.update',
        ]);

        if ($profileSettingsRoute
            || ($request->isMethodSafe() && ! $legacyMutation)
            || $request->routeIs('api.v1.auth.manual_review.contact', 'api.v1.auth.logout', 'api.v1.auth.logout_all') || str_ends_with($routeName, '.logout')) {
            return $next($request);
        }

        $state = $user->manualReviewState();
        $message = $state['expired']
            ? 'Your manual review time has expired. Please contact administration for an update.'
            : 'Your account is under manual review. This action will be available after verification.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => ['code' => 'manual_review_read_only', 'errors' => []],
                'review' => $state,
            ], 423);
        }

        return redirect()->back()->with('manual_review_blocked', $state);
    }
}







