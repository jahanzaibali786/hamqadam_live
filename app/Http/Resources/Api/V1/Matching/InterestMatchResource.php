<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Matching;

use App\Http\Resources\Api\V1\Search\SearchProfileResource;
use Illuminate\Http\Request;

/**
 * A search card plus the reason it was recommended.
 *
 * Everything a Discover card already renders stays byte-for-byte the same
 * (`SearchProfileResource` is reused, not copied), so the app can show these
 * cards with the same widget and only has to read the extra three keys.
 */
class InterestMatchResource extends SearchProfileResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            // 0-100: how much of the viewer's own interests this member shares.
            'interest_score' => $this->resource->getAttribute('interest_score'),
            // Flat words for the "you both like…" line.
            'shared_interests' => $this->resource->getAttribute('shared_interests') ?? [],
            // Per-field detail: [{field, label, items}] for a richer tooltip.
            'interest_breakdown' => $this->resource->getAttribute('interest_breakdown') ?? [],
        ];
    }
}
