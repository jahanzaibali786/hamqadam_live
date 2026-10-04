<?php

namespace App\Http\Resources;

use App\Http\Resources\SupportTicket\SupportTicketReply;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'expected_response_at' => $this->expected_response_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'rating' => $this->rating,
            'rating_comment' => $this->rating_comment,
            'can_rate' => (string) $this->status === '1' && $this->rating === null,
            'subject' => $this->subject,
            'attachments' => uploaded_asset($this->attachments),
            'description' => str_replace('&amp;', '&', str_replace('&nbsp;', ' ', strip_tags($this->description))),
            'support_category_name' => optional($this->supportCategory)->name,
            'created_at' => $this->created_at,
            'reply' => SupportTicketReply::collection($this->supportTicketReplies),
        ];
    }
}
