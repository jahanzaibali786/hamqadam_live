<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\ExpressInterest;
use App\Models\HelpChatThread;
use App\Models\PaymentRefundRequest;
use App\Models\ReportedUser;
use App\Models\SupportTicket;
use App\Models\ViewProfilePicture;
use App\Models\WalletWithdrawRequest;
use App\Services\ClientScope\RequestBellNotifier;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

/**
 * Records request lifecycle events after committed writes, including writes
 * coming from the mobile API. Does not call a notification provider.
 */
final class RequestEventObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Model $model): void
    {
        $event = $this->describe($model);
        if (! $event) return;
        app(RequestBellNotifier::class)->created(
            $event['type'], (int)$model->getKey(), (int)($event['actor'] ?: 0) ?: null,
            'New '.$event['label'].' #'.$model->getKey().' requires attention.',
            $event['admin']
        );
    }

    public function updated(Model $model): void
    {
        if (! $model->wasChanged(['status', 'assigned_user_id', 'reviewed_at'])) return;
        $event = $this->describe($model);
        if (! $event) return;
        $statusValue = $model->getAttribute('status');
        $state = $statusValue instanceof \BackedEnum ? (string)$statusValue->value : (string)($statusValue ?? 'updated');
        app(RequestBellNotifier::class)->updated(
            $event['type'],(int)$model->getKey(),auth()->id(),
            $event['owner'],
            ucfirst($event['label']).' #'.$model->getKey().' was updated (status: '.$state.').',
            $event['admin'], $event['member']
        );
    }

    private function url(string $name, array $parameters = [], string $fallback = '/admin/notifications'): string
    {
        return Route::has($name) ? route($name,$parameters,false) : $fallback;
    }

    /** @return array{type:string,label:string,actor:?int,owner:?int,admin:string,member:string}|null */
    private function describe(Model $model): ?array
    {
        $id = (int)$model->getKey();
        if ($model instanceof SupportTicket) {
            return ['type'=>'support_ticket','label'=>'support ticket',
                'actor'=>(int)$model->sender_user_id,'owner'=>(int)$model->sender_user_id,
                'admin'=>$this->url('support-tickets.index',['ticket_id'=>$id]),
                'member'=>$this->url('client.scope.support',['ticket_id'=>$id],'/dashboard')];
        }
        if ($model instanceof PaymentRefundRequest) {
            return ['type'=>'refund_request','label'=>'refund request',
                'actor'=>(int)$model->user_id,'owner'=>(int)$model->user_id,
                'admin'=>$this->url('admin.payment_refunds.index',['refund_id'=>$id]),
                'member'=>$this->url('member.payment_refunds.index',[], '/dashboard')];
        }
        if ($model instanceof HelpChatThread) {
            return ['type'=>'help_chat','label'=>'support chat',
                'actor'=>(int)$model->user_id,'owner'=>(int)$model->user_id,
                'admin'=>$this->url('admin.help-chat.show',['id'=>$id]),
                'member'=>$this->url('client.scope.support',[], '/dashboard')];
        }
        if ($model instanceof ReportedUser) {
            return ['type'=>'profile_report','label'=>'profile report',
                'actor'=>(int)$model->reported_by,'owner'=>(int)$model->reported_by,
                'admin'=>$this->url('reported_members',['all']),
                'member'=>$this->url('member.listing',[],'/dashboard')];
        }
        if ($model instanceof WalletWithdrawRequest) {
            return ['type'=>'wallet_withdrawal','label'=>'wallet withdrawal request',
                'actor'=>(int)$model->user_id,'owner'=>(int)$model->user_id,
                'admin'=>$this->url('wallet-withdraw-requests.index',[], '/admin/notifications'),
                'member'=>$this->url('dashboard',[], '/dashboard')];
        }
        if ($model instanceof ViewProfilePicture) {
            return ['type'=>'photo_view_request','label'=>'profile photo access request',
                'actor'=>(int)$model->requested_by,'owner'=>(int)$model->user_id,
                'admin'=>$this->url('admin.client.scope.profiles',[], '/admin/notifications'),
                'member'=>$this->url('profile-picture-view-request.index',[],'/dashboard')];
        }
        if ($model instanceof ExpressInterest) {
            return ['type'=>'interest_request','label'=>'interest request',
                'actor'=>(int)$model->interested_by,'owner'=>(int)$model->user_id,
                'admin'=>$this->url('admin.client.scope.profiles',[],'/admin/notifications'),
                'member'=>$this->url('interest_requests',[],'/dashboard')];
        }
        return null;
    }
}
