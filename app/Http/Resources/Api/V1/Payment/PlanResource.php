<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tier' => $this->plan_tier,
            'price' => (float) $this->price,
            'validity_days' => (int) $this->validity,
            'is_recurring' => (bool) $this->is_recurring,
            'features' => [
                'coins' => (int) $this->express_interest,
                'messaging_interests' => (int) $this->express_interest,
                'photo_gallery' => (int) $this->photo_gallery,
                'contacts' => (int) $this->contact,
                'profile_viewers' => (int) $this->profile_viewers_view,
                'profile_image_views' => (int) $this->profile_image_view,
                'gallery_image_views' => (int) $this->gallery_image_view,
                'auto_profile_match' => (bool) $this->auto_profile_match,
                'auto_horoscope_profile_match' => (bool) $this->auto_horoscope_profile_match,
            ],
            'feature_flags' => $this->feature_flags ?? [],
        ];
    }
}
