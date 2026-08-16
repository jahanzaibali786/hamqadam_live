<?php

declare(strict_types=1);

namespace App\Contracts\Dto;

interface DataTransferObject
{
    public function toArray(): array;
}

