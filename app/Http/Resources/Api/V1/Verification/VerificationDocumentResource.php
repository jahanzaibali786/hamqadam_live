<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Verification;

use App\Enums\VerificationDocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->type instanceof VerificationDocumentType ? $this->type->value : $this->type;

        return [
            'id' => $this->id,
            'type' => $type,
            'upload_id' => $this->upload_id,
            'url' => $this->upload_id ? uploaded_asset((int) $this->upload_id) : $this->file_path,
            'metadata' => $this->metadata,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
