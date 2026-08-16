<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;

interface UserRepository extends Repository
{
    public function findForEmailLogin(string $email): ?User;

    public function findForPhoneLogin(string $phone): ?User;
}

