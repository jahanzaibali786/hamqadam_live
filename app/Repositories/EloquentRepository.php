<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\Repository;
use Illuminate\Database\Eloquent\Model;

abstract class EloquentRepository implements Repository
{
    public function __construct(protected readonly Model $model)
    {
    }
}

