<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Concerns;

use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

trait ExecutesInTransaction
{
    /**
     * @throws Throwable
     */
    protected function transaction(Closure $callback, int $attempts = 1): mixed
    {
        return DB::transaction($callback, $attempts);
    }
}

