<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\AuthOtpCodeRepository;
use App\Contracts\Repositories\UserRepository;
use App\Repositories\AuthOtpCodeEloquentRepository;
use App\Repositories\UserEloquentRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, UserEloquentRepository::class);
        $this->app->bind(AuthOtpCodeRepository::class, AuthOtpCodeEloquentRepository::class);
    }
}

