<?php

declare(strict_types=1);

namespace App\Support\Dto;

use App\Contracts\Dto\DataTransferObject;

abstract readonly class ArrayData implements DataTransferObject
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

