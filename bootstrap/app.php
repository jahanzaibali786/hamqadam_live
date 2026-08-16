<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Exceptions\ApiException;
use App\Support\Api\ApiResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->use([
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        // $middleware->validateCsrfTokens(except: [
        //     'aaamarpay/success',
        //     'aaamarpay/fail',
        // ]);

        $middleware->appendToGroup('web', [
             //\App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            //\App\Http\Middleware\LastUserActivity::class,
            \App\Http\Middleware\HttpsProtocol::class,
            \App\Http\Middleware\CheckForMaintenanceMode::class
        ]);
    
        $middleware->prependToGroup('api', [
             \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,   
            // 'throttle:600,1',
            // 'bindings',
        ]);


        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'member' => \App\Http\Middleware\IsMember::class,
            'api_member' => \App\Http\Middleware\IsApiMember::class,
            'api_email_verified' => \App\Http\Middleware\EmailVerifiedApi::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \App\Http\Middleware\EnsureCmsEmailIsVerified::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'app_language' =>  \App\Http\Middleware\AppLanguage::class,
            'activated' => \App\Http\Middleware\CheckActiveUser::class,
            'check.package' => \App\Http\Middleware\CheckMemberPackage::class,
        ]);

        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\Authenticate::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, $request) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'The given data was invalid.',
                status: 422,
                code: 'validation_failed',
                errors: $exception->errors()
            );
        });

        $exceptions->render(function (AuthenticationException $exception, $request) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return ApiResponse::error('Unauthenticated.', 401, 'unauthenticated');
        });

        $exceptions->render(function (AuthorizationException $exception, $request) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return ApiResponse::error('This action is unauthorized.', 403, 'forbidden');
        });

        $exceptions->render(function (NotFoundHttpException $exception, $request) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return ApiResponse::error('Resource not found.', 404, 'not_found');
        });

        $exceptions->render(function (ApiException $exception, $request) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return ApiResponse::error(
                message: $exception->getMessage(),
                status: $exception->statusCode(),
                code: $exception->errorCode(),
                errors: $exception->errors()
            );
        });
    })->create();
