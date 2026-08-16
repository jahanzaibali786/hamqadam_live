<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Search;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'filters' => $this->filters ?: [],
            'result_count' => (int) $this->result_count,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
