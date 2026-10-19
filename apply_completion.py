#!/usr/bin/env python3
"""Hamqadam completion patch; prepare all edits, validate, then write with backups.

Required: existing Hamqadam Laravel repo on the *current* 2026-10-19 main family.
Run python3 apply_completion.py --check, then --apply. No network access.
"""
from pathlib import Path
import argparse, datetime, shutil, sys, re, subprocess, tempfile
ROOT = Path(__file__).resolve().parent

def swap(source, old, new, label):
    if new in source:
        return source
    count = source.count(old)
    if count != 1:
        raise ValueError(f'{label}: expected exactly 1 anchor, found {count}')
    return source.replace(old, new, 1)

def payment_service(s):
    # State machine replacement compatible with previous Step3 installer OR GitHub main.
    start = s.find('    public function processWebhook(PaymentGateway $gateway, array $data): PackagePayment\n')
    end = s.find('    /**\n     * Verify a real Stripe webhook', start)
    if start < 0 or end < 0: raise ValueError('payment webhook boundary not found')
    if 'COMPLETION_HARDENED_WEBHOOK' not in s[start:end]:
        s = s[:start] + '''    /** COMPLETION_HARDENED_WEBHOOK: validated provider event, locked invoice, idempotent settlement. */
    public function processWebhook(PaymentGateway $gateway, array $data): PackagePayment
    {
        $eventId = trim((string) ($data['event_id'] ?? ''));
        $status = strtolower(trim((string) ($data['status'] ?? '')));
        $allowed = ['paid','success','completed','pending','processing','failed','declined','cancelled','expired'];
        if ($eventId === '' || ! in_array($status, $allowed, true)) {
            throw new ApiException('Invalid verified payment event.', 422, ApiErrorCode::ValidationFailed->value);
        }
        $code = trim((string) ($data['payment_code'] ?? ''));
        $reference = trim((string) ($data['gateway_reference'] ?? ''));
        if ($code === '' && $reference === '') {
            throw new ApiException('Verified payment reference is missing.', 422, ApiErrorCode::ValidationFailed->value);
        }
        return DB::transaction(function () use ($gateway, $data, $eventId, $status, $code, $reference) {
            $payment = PackagePayment::query()->where('payment_method', $gateway->value)
                ->when($code !== '', fn ($query) => $query->where('payment_code', $code))
                ->when($reference !== '', fn ($query) => $query->where('gateway_reference', $reference))
                ->lockForUpdate()->first();
            if (! $payment) {
                throw new ApiException('Payment record not found for verified event.', 404, ApiErrorCode::NotFound->value);
            }
            if (isset($data['amount_minor']) && (int) $data['amount_minor'] !==
                (int) round(((float) ($payment->payable_amount ?? $payment->amount)) * 100)) {
                throw new ApiException('Verified amount mismatch.', 409, ApiErrorCode::ValidationFailed->value);
            }
            if (! empty($data['currency']) && strcasecmp((string) $payment->currency, (string) $data['currency']) !== 0) {
                throw new ApiException('Verified currency mismatch.', 409, ApiErrorCode::ValidationFailed->value);
            }
            $record = PaymentWebhookEvent::query()->where('gateway', $gateway->value)
                ->where('event_id', $eventId)->lockForUpdate()->first();
            if ($record && $record->processed_at) return $payment->fresh(['package','coupon']);
            // Terminal Paid never moves backwards because of delayed failure messages.
            if ($payment->payment_status !== 'Paid') {
                if (in_array($status, ['paid','success','completed'], true)) {
                    $this->markPaid($payment, $data);
                } elseif (in_array($status, ['failed','declined','cancelled','expired'], true)) {
                    $payment->forceFill([
                        'payment_status' => in_array($status,['cancelled','expired'],true) ? 'Cancelled' : 'Failed',
                        'gateway_status' => $status,
                        'payment_details' => json_encode($data),
                    ])->save();
                } else {
                    $payment->forceFill(['gateway_status'=>$status])->save();
                }
            }
            if ($record) {
                $record->forceFill(['payload'=>$data,'processed_at'=>now()])->save();
            } else {
                PaymentWebhookEvent::query()->create([
                    'gateway'=>$gateway->value,'event_id'=>$eventId,
                    'event_type'=>$data['event_type'] ?? 'payment.updated',
                    'payload'=>$data,'processed_at'=>now(),
                ]);
            }
            return $payment->fresh(['package','coupon']);
        }, 3);
    }

''' + s[end:]
    # Stripe settled sessions must acquire the same invoice lock as callbacks.
    old='''        return DB::transaction(fn (): PackagePayment => $this->markPaid($payment, [
            'source' => 'stripe_checkout_session',
            'event_id' => 'stripe_session_' . $session->id,
            'session_id' => $session->id,
            'status' => 'paid',
        ])->fresh(['package', 'coupon']));'''
    new='''        return DB::transaction(function () use ($payment, $session): PackagePayment {
            $locked = PackagePayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            return $this->markPaid($locked, [
                'source' => 'stripe_checkout_session',
                'event_id' => 'stripe_session_' . $session->id,
                'session_id' => $session->id,
                'status' => 'paid',
            ])->fresh(['package', 'coupon']);
        }, 3);'''
    s=swap(s,old,new,'Stripe concurrency lock')
    # Stripe terminal status: asynchronous completed is pending until funds paid.
    if "'status' => $paid ? 'paid' : 'failed'," in s:
        s=swap(s,"'status' => $paid ? 'paid' : 'failed',",
            "'status' => $paid ? 'paid' : ($type === 'checkout.session.completed' ? 'pending' : ($type === 'checkout.session.expired' ? 'cancelled' : 'failed')),",
            'async card payment status')
    elif "'status' => $paid ? 'paid' : ($type === 'checkout.session.completed'" not in s:
        raise ValueError('Stripe pending-state code differs from expected version')
    if "'amount_minor' => isset($session->amount_total)" not in s:
        s=swap(s,"            'payload' => json_decode($payload, true) ?: [],",
            "            'amount_minor' => isset($session->amount_total) ? (int) $session->amount_total : null,\n            'currency' => isset($session->currency) ? strtoupper((string)$session->currency) : null,\n            'payload' => json_decode($payload, true) ?: [],",
            'signed Stripe amount and currency')
    # New per-user limit is available after migration. Only count paid & recent reserved rows.
    anchor='''            if ($coupon) {
                $lockedCoupon = PaymentCoupon::query()->whereKey($coupon->id)->lockForUpdate()->firstOrFail();'''
    if anchor in s:
        extra='''            if ($coupon && $coupon->per_user_limit !== null) {
                $already = PackagePayment::query()->where('coupon_id',$coupon->id)
                    ->where('user_id',$user->id)
                    ->where(fn ($q) => $q->where('payment_status','Paid')->orWhere(fn ($pending) =>
                        $pending->where('payment_status','Due')->where('created_at','>=',now()->subMinutes(45))))
                    ->count();
                if ($already >= (int) $coupon->per_user_limit) {
                    throw new ApiException('Promo code per-member limit reached.', 409, ApiErrorCode::ValidationFailed->value);
                }
            }
'''
        s=swap(s,anchor,extra+anchor,'checkout per-member coupon cap')
    else:
        raise ValueError('Step3 coupon reservation is missing. Run apply_step3.py --apply before this patch.')
    return s

def payment_coupon_controller(s):
    return swap(s,"            'usage_limit' => ['nullable', 'integer', 'min:1'],",
                "            'usage_limit' => ['nullable', 'integer', 'min:1'],\n            'per_user_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],",
                'coupon admin validation')

def payment_coupon_model(s):
    return swap(s,"        'usage_limit',", "        'usage_limit',\n        'per_user_limit',", 'coupon mass assignment')

def coupon_fields(s):
    marker='''<div class="form-row">
    <div class="form-group col-6"><label>{{ translate('Starts At') }}'''
    addition='''<div class="form-group"><label>{{ translate('Maximum uses per member') }}</label><input class="form-control" type="number" min="1" max="1000" name="per_user_limit" value="{{ $value('per_user_limit') }}" placeholder="{{ translate('Unlimited') }}"><small class="form-text text-muted">Completed payments and recently reserved checkouts count toward this limit.</small></div>
'''
    return swap(s,marker,addition+marker,'coupon admin per-user control')

def coupon_api_controller(s):
    marker="return $this->success($this->payments->validateCoupon($request->validated('code'), (int) $request->validated('package_id')));"
    return swap(s,marker,"return $this->success($this->payments->validateCoupon($request->validated('code'), (int) $request->validated('package_id'), $request->user()));",'coupon API user validation')

def coupon_check(s):
    s=swap(s,'public function validateCoupon(string $code, int $packageId): array',
        'public function validateCoupon(string $code, int $packageId, ?User $user = null): array','coupon service signature')
    anchor='''        $coupon = $this->couponByCode($code);
        $discount = $this->discountFor($coupon, (float) $package->price);'''
    new='''        $coupon = $this->couponByCode($code);
        if ($user && $coupon->per_user_limit !== null) {
            $previous = PackagePayment::query()->where('coupon_id', $coupon->id)
                ->where('user_id',$user->id)
                ->where(fn ($q) => $q->where('payment_status','Paid')->orWhere(fn ($pending) =>
                    $pending->where('payment_status','Due')->where('created_at','>=',now()->subMinutes(45))))
                ->count();
            if ($previous >= (int) $coupon->per_user_limit) {
                throw new ApiException('Promo code per-member limit reached.',422,ApiErrorCode::ValidationFailed->value);
            }
        }
        $discount = $this->discountFor($coupon, (float) $package->price);'''
    return swap(s,anchor,new,'coupon preview limit')

def interest(s):
    return swap(s,"if ($priority && ! $this->hasFeatureFlag($featureFlags, 'priority_interest')) {",
        "if ($priority && ! app(\\App\\Services\\ClientScope\\PremiumEntitlementService::class)->has($sender, 'priority_interest')) {",'priority interest active entitlement')

def v1_chat(s):
    old='''        $message = $this->chat->send(
            $request->user(),
            $thread,
            $request->validated(),
            $request->file('attachments', [])
        );'''
    new='''        $message = app(\\App\\Services\\ClientScope\\MessagingAllowanceService::class)
            ->sendWithinLimit($request->user(), fn () => $this->chat->send(
                $request->user(), $thread, $request->validated(), $request->file('attachments', [])
            ));
        if (\\Illuminate\\Support\\Facades\\Schema::hasTable('analytics_events')) {
            \\App\\Models\\AnalyticsEvent::query()->create([
                'user_id'=>$request->user()->id,'event_name'=>'message_sent',
                'feature_key'=>'messaging','source'=>'api_v1', 'occurred_at'=>now(),
            ]);
        }'''
    return swap(s,old,new,'v1 chat send & tracking')

def legacy_chat(s):
    old='''        $new_chat = $chat->store($request->except(['_token']), $attachments);'''
    new='''        $new_chat = app(\\App\\Services\\ClientScope\\MessagingAllowanceService::class)
            ->sendWithinLimit($request->user(), fn () => $chat->store($request->except(['_token']), $attachments));'''
    return swap(s,old,new,'legacy chat send allowance')

def web_chat(s):
    start=s.find('    public function chat_reply(Request $request)')
    end=s.find('    public function unread_count(Request $request)',start)
    if start < 0 or end < 0: raise ValueError('website chat reply method boundaries missing')
    block=s[start:end]
    old="""        $chat = new Chat();
        $chat->chat_thread_id = $chat_thread->id;"""
    replacement="""        $chat = app(\\App\\Services\\ClientScope\\MessagingAllowanceService::class)
            ->sendWithinLimit(Auth::user(), function () use ($request, $chat_thread) {
        $chat = new Chat();
        $chat->chat_thread_id = $chat_thread->id;"""
    block=swap(block,old,replacement,'web chat allowance')
    block=swap(block,"        $chat->save();\n        $recipientId = (int) $chat_thread->sender_user_id",
        "        $chat->save();\n        return $chat;\n        });\n        $recipientId = (int) $chat_thread->sender_user_id",'web chat transaction end')
    return s[:start]+block+s[end:]

def search_api(s):
    anchor='''        $this->applySorting($query, $viewer, $filters);'''
    boost='''        // Verified active paid boosts rank above ordinary results without hiding
        // or removing candidates. No boost is honored once its validity expires.
        if (\\Illuminate\\Support\\Facades\\Schema::hasTable('member_profile_boosts')) {
            $query->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM member_profile_boosts boost WHERE boost.user_id = users.id AND boost.ends_at > ?) THEN 1 ELSE 0 END DESC', [now()]);
        }
        $this->applySorting($query, $viewer, $filters);'''
    return swap(s,anchor,boost,'boosted mobile discovery')

def web_home(s):
    start=s.find('    public function member_listing(Request $request)')
    end=s.find('    public function profile_edit(Request $request)',start)
    if start<0 or end<0:raise ValueError('web member search bounds missing')
    block=s[start:end]
    old="""            ->orderBy('created_at', 'desc')
            ->where('user_type', 'member')"""
    new="""            ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM member_profile_boosts boost WHERE boost.user_id = users.id AND boost.ends_at > ?) THEN 1 ELSE 0 END DESC', [now()])
            ->orderBy('created_at', 'desc')
            ->where('user_type', 'member')"""
    if 'member_profile_boosts' not in block:
        block=swap(block,old,new,'web boosted ranking')
    # Avoid costly account-wide search if no table migrated by checking before query
    # The deployment instructions mandate php artisan migrate --force before browse.
    return s[:start]+block+s[end:]

def api_bridge(s):
    anchor="Route::get('/connector-c', [BridgeController::class, 'channelC'])->name('connector_c');"
    extra="""Route::get('/premium/boost', [\\App\\Http\\Controllers\\Api\\V1\\Premium\\BoostController::class, 'status'])->name('api.v1.bridge.premium.boost.status');
Route::post('/premium/boost', [\\App\\Http\\Controllers\\Api\\V1\\Premium\\BoostController::class, 'activate'])->middleware('throttle:3,1')->name('api.v1.bridge.premium.boost.activate');
"""
    extra += "Route::get('/localization', [\\App\\Http\\Controllers\\Api\\V1\\Localization\\LocalizationController::class, 'index'])->name('api.v1.bridge.localization');\n"
    return swap(s,anchor,anchor+'\n'+extra,'mobile boost + localization')

def web_route(s):
    marker="require __DIR__.'/referral.php';"
    route="""// Active paid profile-boost activation (CSRF protected, authenticated member).
\\Illuminate\\Support\\Facades\\Route::middleware(['auth','member','verified'])
    ->post('/member/profile-boost', function (\\Illuminate\\Http\\Request $request) {
        app(\\App\\Services\\ClientScope\\ProfileBoostService::class)->activate($request->user());
        return back()->with('success', 'Your profile boost is active for 24 hours.');
    })->middleware('throttle:3,1')->name('member.profile_boost.activate');
"""
    return swap(s,marker,marker+'\n'+route,'web boost action')

def member_dashboard(s):
    anchor='''    @endphp
    <div class="row gutters-5 mb-4">'''
    widget='''    @if(app(\\App\\Services\\ClientScope\\PremiumEntitlementService::class)->has($user, 'profile_boost'))
    @php($boostStatus = app(\\App\\Services\\ClientScope\\ProfileBoostService::class)->current($user))
    <section class="alert alert-info" aria-labelledby="boost-title">
        <h2 class="h6" id="boost-title">{{ translate('Boost your profile') }}</h2>
        <p>{{ $boostStatus['active'] ? translate('Your profile is currently boosted in discovery.') : translate('Appear above regular results for 24 hours. Once every seven days.') }}</p>
        @unless($boostStatus['active'])
        <form method="POST" action="{{ route('member.profile_boost.activate') }}">@csrf<button class="btn btn-primary btn-sm">{{ translate('Activate premium boost') }}</button></form>
        @endunless
    </section>
    @endif
'''
    return swap(s,anchor,widget+anchor,'member boost dashboard')

def admin_dashboard(s):
    anchor="@section('content')"
    s=swap(s,anchor,anchor+"\n@include('admin.client_scope.partials.completion_insights')",'admin insights panel')
    # Add config of two server-verified milestones to existing Got Match config.
    anchor="""<form method="POST" action="{{ route('admin.client.scope.rewards.rules') }}">@csrf<input type="hidden" name="event_key" value="got_match">"""
    extra='''<section class="border rounded p-3 mb-3" aria-labelledby="milestones-heading"><h3 id="milestones-heading" class="h6">Additional verified milestone rewards</h3><p class="small text-muted">Set rewards explicitly; these are disabled unless a rule is enabled. Duplicate credits are prevented by the reward ledger.</p>
@foreach(['profile_completed'=>'Completed verified profile','first_interest'=>'First real Interest sent'] as $event => $label)
@php($configuredRule = $rewardRules->firstWhere('event_key', $event))
<form method="POST" action="{{ route('admin.client.scope.rewards.rules') }}" class="border-top py-2">@csrf<input type="hidden" name="event_key" value="{{ $event }}">
<label class="font-weight-bold">{{ $label }}</label><input class="form-control mb-2" name="title" maxlength="150" required value="{{ $configuredRule?->title ?? $label }}">
<div class="form-row"><div class="col"><label>Coin reward</label><input type="number" min="0" max="1000" class="form-control" required name="coin_reward" value="{{ $configuredRule?->coin_reward ?? 0 }}"></div>
<div class="col"><label>Maximum per member</label><input type="number" min="1" max="20" class="form-control" required name="max_per_user" value="{{ $configuredRule?->max_per_user ?? 1 }}"></div></div>
<label class="mt-2"><input type="checkbox" name="active" value="1" @checked((bool)($configuredRule?->active ?? false))> Enabled</label>
<button class="btn btn-outline-primary btn-sm ml-2">Save milestone</button>
</form>
@endforeach</section>
'''
    return swap(s,anchor,extra+anchor,'admin milestone setup')

def scope_controller(s):
    return swap(s,"'event_key'=>['required','in:got_match'],",
                "'event_key'=>['required','in:got_match,profile_completed,first_interest'],",'milestone event permission')

def kernel(s):
    marker="        $schedule->command('referrals:award-verified --limit=500')->hourly()->withoutOverlapping();"
    extra="""        $schedule->command('notifications:send-digests --limit=200')->hourly()->withoutOverlapping();
        $schedule->command('rewards:award-milestones --limit=500')->dailyAt('04:30')->withoutOverlapping();"""
    return swap(s,marker,marker+'\n'+extra,'digest/milestone scheduler')

def language_controller(s):
    old="""        $request->session()->put('locale', $request->locale);
        $language = Language::where('code', $request->locale)->first();"""
    new="""        $request->validate(['locale'=>['required','string','exists:languages,code']]);
        $language = Language::where('code', $request->locale)->firstOrFail();
        $request->session()->put('locale', $language->code);"""
    return swap(s,old,new,'validated website language change')

def api_search_controller(s):
    anchor='''        $page = $this->profiles->search($request->user(), $request->validated());'''
    event='''        $page = $this->profiles->search($request->user(), $request->validated());
        if (\\Illuminate\\Support\\Facades\\Schema::hasTable('analytics_events')) {
            \\App\\Models\\AnalyticsEvent::query()->create([
                'user_id'=>$request->user()->id, 'event_name'=>'search_performed',
                'feature_key'=>'discovery', 'source'=>'api_v1', 'occurred_at'=>now(),
                'metadata'=>['has_filter'=>count($request->validated()) > 0],
            ]);
        }'''
    return swap(s,anchor,event,'discovery search event')

TRANSFORMS = {
    'app/Services/Api/V1/Payment/PaymentService.php': lambda s: coupon_check(payment_service(s)),
    'app/Http/Controllers/Admin/PaymentCouponController.php': payment_coupon_controller,
    'app/Models/PaymentCoupon.php': payment_coupon_model,
    'resources/views/admin/payment_coupons/partials/fields.blade.php': coupon_fields,
    'app/Http/Controllers/Api/V1/Payment/PaymentController.php': coupon_api_controller,
    'app/Http/Controllers/Api/V1/Interest/InterestController.php': interest,
    'app/Http/Controllers/Api/V1/Chat/ChatController.php': v1_chat,
    'app/Http/Controllers/Api/ChatController.php': legacy_chat,
    'app/Http/Controllers/ChatController.php': web_chat,
    'app/Services/Api/V1/Search/ProfileSearchService.php': search_api,
    'app/Http/Controllers/HomeController.php': web_home,
    'app/Http/Controllers/Api/V1/Search/SearchController.php': api_search_controller,
    'routes/api_bridge.php': api_bridge,
    'routes/web.php': web_route,
    'resources/views/frontend/member/dashboard.blade.php': member_dashboard,
    'resources/views/admin/client_scope/dashboard.blade.php': admin_dashboard,
    'app/Http/Controllers/ClientScope/ClientScopeController.php': scope_controller,
    'app/Console/Kernel.php': kernel,
    'app/Http/Controllers/LanguageController.php': language_controller,
}

def main():
    p=argparse.ArgumentParser(description=__doc__)
    p.add_argument('--check',action='store_true')
    p.add_argument('--apply',action='store_true')
    args=p.parse_args()
    if not args.check and not args.apply: p.error('use --check or --apply')
    if not (ROOT/'artisan').is_file() or not (ROOT/'composer.json').is_file():
        raise SystemExit('Extract into Laravel root beside artisan and composer.json, not public/.')
    edits={}
    for name,fn in TRANSFORMS.items():
        path=ROOT/name
        if not path.is_file(): raise SystemExit(f'Missing project file: {name}')
        old=path.read_text(encoding='utf-8')
        try: new=fn(old)
        except Exception as exc:
            raise SystemExit(f'PATCH PRE-FLIGHT FAILED for {name}: {exc}; no files modified.')
        if new!=old: edits[name]=new
    # Lint ALL proposed changed plain PHP files BEFORE writing anything.
    with tempfile.TemporaryDirectory(prefix='hamqadam-php-preflight-') as scratch:
        for name, modified in edits.items():
            if not name.endswith('.php') or name.endswith('.blade.php'):
                continue
            preview = Path(scratch) / (name.replace('/', '__') + '.php')
            preview.write_text(modified, encoding='utf-8')
            result = subprocess.run(['php','-l',str(preview)], capture_output=True, text=True)
            if result.returncode:
                raise SystemExit(f'PHP syntax error in proposed {name}: {result.stdout} {result.stderr}; no changes written.')
    print(f'Validated {len(TRANSFORMS)} source files and linted {sum(n.endswith(".php") and not n.endswith(".blade.php") for n in edits)} changed PHP files; {len(edits)} need updating.')
    for name in edits:print('  '+name)
    if not args.apply:
        print('Dry run only; run python3 apply_completion.py --apply after backups.')
        return
    backups = ROOT/'storage'/'app'/'completion-backups'/datetime.datetime.now().strftime('%Y%m%d-%H%M%S')
    for name,updated in edits.items():
        path=ROOT/name
        dst=backups/name;dst.parent.mkdir(parents=True,exist_ok=True)
        shutil.copy2(path,dst)
        temp=path.with_suffix(path.suffix+'.completion.tmp')
        temp.write_text(updated,encoding='utf-8'); temp.replace(path)
    print('Patched successfully; backups:',backups)
    print('Run php artisan migrate --force, optimize:clear and test commands in README.')

if __name__=='__main__': main()
