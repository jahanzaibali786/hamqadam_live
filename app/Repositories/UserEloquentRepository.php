<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\UserRepository;
use App\Models\User;

class UserEloquentRepository implements UserRepository
{
    public function findForEmailLogin(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->where('user_type', 'member')
            ->first();
    }

    public function findForPhoneLogin(string $phone): ?User
    {
        return User::query()
            ->where('phone', $phone)
            ->where('user_type', 'member')
            ->first();
    }
}

