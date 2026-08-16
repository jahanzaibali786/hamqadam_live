<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfilePrivacyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'show_photo' => $this->show_photo,
            'show_gallery' => $this->show_gallery,
            'show_contact' => $this->show_contact,
            'show_email' => $this->show_email,
            'show_phone' => $this->show_phone,
            'show_location' => $this->show_location,
            'allow_profile_view_notifications' => $this->allow_profile_view_notifications,
        ];
    }
}

