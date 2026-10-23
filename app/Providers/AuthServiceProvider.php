<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Super Admin')) {
                return true;
            }

            // Content management is intentionally available to both Admin and
            // Sub Admin roles, even when legacy permission rows are missing.
            $contentPermissions = [
                'show_blogs',
                'add_blog',
                'edit_blog',
                'delete_blog',
                'show_blog_categories',
                'add_blog_category',
                'edit_blog_category',
                'delete_blog_category',
                'show_happy_stories',
                'edit_happy_story',
                'view_details_happy_story',
                'edit_pages',
                'header',
                'footer',
                'appearances',
            ];

            if (in_array($ability, $contentPermissions, true)
                && $user->hasAnyRole(['Admin', 'admin', 'Sub Admin', 'subadmin'])) {
                return true;
            }

            return null;
        });
    }
}
