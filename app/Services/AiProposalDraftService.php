<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AiProposalDraftService
{
    /**
     * AI returns a draft only. Sending must still pass through the explicit
     * human approval gate in MemberConnectionWorkflow::saveProposal().
     */
    public function generate(User $sender, User $recipient, string $language, string $intention): string
    {
        if (!app(MemberConnectionWorkflow::class)->canCommunicate($sender->id,$recipient->id)) {
            throw ValidationException::withMessages(['proposal'=>'Interest must be accepted first.']);
        }
        $key=(string)config('services.openai.key',env('OPENAI_API_KEY',''));
        if ($key==='') {
            throw ValidationException::withMessages(['ai'=>'AI generation is unavailable. Please use one of the static proposal templates.']);
        }
        $language=in_array($language,['en','ur'],true)?$language:'en';
        $details=array_filter([
            'Sender first name'=>$sender->first_name,
            'Recipient first name'=>$recipient->first_name,
            'Sender job'=>$sender->member?->job_title,
            'Recipient job'=>$recipient->member?->job_title,
            'Sender future goals'=>$sender->member?->future_goals,
            'Sender stated intention'=>mb_substr(trim($intention),0,700),
        ],fn($value)=>is_scalar($value)&&trim((string)$value)!=='');
        $system='Write a warm, respectful and sincere Hamqadam marriage-intention proposal as a draft for the sender to review. Respond in '.($language==='ur'?'Urdu':'English').'. Use ONLY provided details. Do not invent family history, faith, income, schooling, job roles, mutual feelings, commitments, consent, or timelines. Never suggest that the receiver has agreed to anything. Mention family introduction only if the sender explicitly requests it. Return only the draft proposal, no quotation marks or metadata.';
        try {
            $response=Http::withToken($key)->acceptJson()->timeout(20)->post('https://api.openai.com/v1/chat/completions',[
                'model'=>(string)config('services.openai.proposal_model',env('HAMQADAM_PROPOSAL_AI_MODEL','gpt-4o-mini')),
                'messages'=>[['role'=>'system','content'=>$system],['role'=>'user','content'=>json_encode($details,JSON_UNESCAPED_UNICODE)]],
                'temperature'=>0.65,
                'max_tokens'=>650,
            ]);
            if (!$response->successful()) throw new \RuntimeException('AI provider rejected the draft request.');
            $draft=trim((string)$response->json('choices.0.message.content',''));
            if (mb_strlen($draft)<20 || mb_strlen($draft)>5000) throw new \RuntimeException('AI provider returned an invalid proposal draft.');
            return $draft;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Proposal draft generation unavailable',['sender_id'=>$sender->id,'error'=>$e->getMessage()]);
            throw ValidationException::withMessages(['ai'=>'AI generation is temporarily unavailable. Please use a static proposal template.']);
        }
    }
}
