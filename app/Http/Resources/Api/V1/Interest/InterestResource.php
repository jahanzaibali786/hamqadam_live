<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Interest;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewerId = $request->user()?->id;
        // `interested_by` is the sender, `user_id` the recipient.
        $isSender = (int) $this->interested_by === (int) $viewerId;
        $counterpart = $isSender ? $this->user : $this->sender;

        return [
            'id' => $this->id,
            'direction' => $isSender ? 'sent' : 'received',
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'initial_note' => $this->initial_note,
            'can_respond' => ! $isSender && $this->status?->value === 0,
            'can_withdraw' => $isSender && $this->status?->value === 0,
            'member' => $counterpart ? $this->memberBlock($counterpart, $viewerId) : null,
            'responded_at' => optional($this->responded_at)->toISOString(),
            'withdrawn_at' => optional($this->withdrawn_at)->toISOString(),
            'expires_at' => optional($this->expires_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }

    /**
     * The card the app draws on the Matches screen.
     *
     * The reference design shows photo, name + age, city, education,
     * profession and monthly income — the same lines a Discover card reads —
     * so the member block carries them alongside the identity/verification
     * fields the older app version parses. Everything is resolved server-side
     * (lookup names, not ids) so the card needs no extra calls and no lookup
     * table of its own.
     */
    private function memberBlock(User $counterpart, ?int $viewerId): array
    {
        $member = $counterpart->member;

        // Highest declared education: the row flagged as highest, else the
        // most recent one. Only the readable label travels to the app.
        $education = $counterpart->education
            ->sortByDesc(fn ($e) => [(int) $e->is_highest_degree, $e->created_at])
            ->first();
        $educationLine = $education?->educationLevel?->name
            ?? $education?->degree_legacy
            ?? null;

        // Profession: the career row's profession, falling back to the
        // profession picked on the member record itself.
        $career = $counterpart->career->first();
        $professionLine = $career?->profession?->name
            ?? $member?->profession?->name
            ?? $member?->job_title
            ?? null;

        // Income: the package-visible salary band, rendered the way the
        // reference card reads it ("PKR 120k / mo").
        $incomeLine = null;
        if ($member?->annual_salary_range_id) {
            $rangeMax = \DB::table('annual_salary_ranges')
                ->where('id', $member->annual_salary_range_id)
                ->value('max_salary');
            if ($rangeMax !== null) {
                $incomeLine = 'PKR '.$this->compactNumber((float) $rangeMax).' / mo';
            }
        } elseif ($member?->annual_income) {
            $incomeLine = 'PKR '.$this->compactNumber((float) $member->annual_income).' / yr';
        }

        // Viewer-relative compatibility score, exactly what a search card gets.
        $match = $counterpart->profile_match_for_viewer()
            ->where('user_id', $viewerId)
            ->value('match_percentage');

        return [
            // ---- identity (unchanged shape) --------------------------------
            'id' => $counterpart->id,
            'code' => $counterpart->code,
            'name' => trim(($counterpart->first_name ?? '').' '.($counterpart->last_name ?? '')),
            'photo' => $counterpart->photo ? uploaded_asset($counterpart->photo) : null,
            'gender' => $member?->gender,
            'verification_status' => $member?->verification_status,
            'ai_verification_status' => $member?->ai_verification_status,

            // ---- card lines (new) ------------------------------------------
            'age' => $member?->birthday ? Carbon::parse($member->birthday)->age : null,
            'city' => $counterpart->addresses->first()?->city?->name,
            'education' => $educationLine,
            'profession' => $professionLine,
            'income' => $incomeLine,
            'compatibility_percentage' => $match !== null ? (int) $match : null,
        ];
    }

    /** "120k", "1.2M" — the compact money style the reference card shows. */
    private function compactNumber(float $value): string
    {
        if ($value >= 1_000_000) {
            $m = $value / 1_000_000;

            return ($m === floor($m) ? (string) (int) $m : rtrim(rtrim((string) round($m, 1), '0'), '.')).'M';
        }

        if ($value >= 1_000) {
            $k = $value / 1_000;

            return ($k === floor($k) ? (string) (int) $k : rtrim(rtrim((string) round($k, 1), '0'), '.')).'k';
        }

        return (string) (int) $value;
    }
}
