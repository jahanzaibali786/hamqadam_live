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
            // Focus / invisibility switches. They were accepted by
            // PATCH /profile/privacy but never echoed back, so the app could
            // write them and not read them: a member who turned Invisible Mode
            // on saw the switch flip itself back off on the next load and had
            // no way to tell whether it had taken effect. The server already
            // enforces both (search/discover/recommendations exclude
            // invisible_mode; proposals stop for do_not_disturb) — this only
            // hands the stored value back.
            'do_not_disturb' => (bool) $this->do_not_disturb,
            'invisible_mode' => (bool) $this->invisible_mode,
        ];
    }
}

