#!/usr/bin/env python3
"""Targeted Hamqadam Step-3 upgrade. Run in project root with --check then --apply.
Only edits known files/anchors and writes timestamped backups to storage/app.
"""
from pathlib import Path
import argparse, shutil, datetime, sys

ROOT = Path(__file__).resolve().parent

def swap(source, old, new, label):
    if new in source:
        return source
    n = source.count(old)
    if n != 1:
        raise RuntimeError(f'{label}: expected 1 matching anchor, got {n}; project version may differ')
    return source.replace(old, new, 1)

def payment_service(s):
    start = s.find('    public function processWebhook(PaymentGateway $gateway, array $data): PackagePayment\n')
    end = s.find('    /**\n     * Verify a real Stripe webhook', start)
    if start<0 or end<0: raise RuntimeError('payment service webhook method anchors missing')
    if 'webhook_status_invalid' not in s[start:end]:
        s = s[:start]+'''    /** Accept only already-authenticated provider events. Row locking ensures
     * that repeat and concurrent deliveries cannot re-credit entitlements. */
    public function processWebhook(PaymentGateway $gateway, array $data): PackagePayment
    {
        $eventId = trim((string) ($data['event_id'] ?? ''));
        $status = strtolower(trim((string) ($data['status'] ?? '')));
        $accepted = ['paid','success','completed','failed','declined','cancelled','expired','pending','processing'];
        if ($eventId === '' || ! in_array($status, $accepted, true)) {
            throw new ApiException('Invalid payment event.', 422, ApiErrorCode::ValidationFailed->value);
        }
        if (empty($data['payment_code']) && empty($data['gateway_reference'])) {
            throw new ApiException('Payment reference is required.', 422, ApiErrorCode::ValidationFailed->value);
        }
        return DB::transaction(function () use ($gateway, $data, $eventId, $status) {
            $payment = PackagePayment::query()->where('payment_method', $gateway->value)
                ->when(! empty($data['payment_code']), fn ($q) => $q->where('payment_code', $data['payment_code']))
                ->when(! empty($data['gateway_reference']), fn ($q) => $q->where('gateway_reference', $data['gateway_reference']))
                ->lockForUpdate()->first();
            if (! $payment) {
                throw new ApiException('Payment record not found for webhook.', 404, ApiErrorCode::NotFound->value);
            }
            // Signed Stripe Checkout Sessions include authoritative charge amount/currency.
            if (isset($data['amount_minor']) && (int) $data['amount_minor'] !== (int) round((float) $payment->payable_amount * 100)) {
                throw new ApiException('Gateway amount does not match the invoice.', 409, ApiErrorCode::ValidationFailed->value);
            }
            if (! empty($data['currency']) && strcasecmp((string) $payment->currency, (string) $data['currency']) !== 0) {
                throw new ApiException('Gateway currency does not match the invoice.', 409, ApiErrorCode::ValidationFailed->value);
            }
            $existing = PaymentWebhookEvent::query()->where('gateway', $gateway->value)
                ->where('event_id', $eventId)->lockForUpdate()->first();
            if ($existing && $existing->processed_at) return $payment->fresh(['package','coupon']);
            // Never downgrade a settled payment because webhooks may arrive out of order.
            if ($payment->payment_status !== 'Paid') {
                if (in_array($status, ['paid','success','completed'], true)) {
                    $this->markPaid($payment, $data);
                } elseif (in_array($status, ['failed','declined','cancelled','expired'], true)) {
                    $payment->forceFill([
                        'payment_status' => in_array($status, ['cancelled','expired'], true) ? 'Cancelled' : 'Failed',
                        'gateway_status' => $status,
                        'payment_details' => json_encode($data),
                    ])->save();
                } else {
                    $payment->forceFill(['gateway_status' => $status])->save();
                }
            }
            if (! $existing) {
                $existing = PaymentWebhookEvent::query()->create([
                    'gateway' => $gateway->value,
                    'event_id' => $eventId,
                    'event_type' => $data['event_type'] ?? 'payment.updated',
                    'payload' => $data,
                    'processed_at' => now(),
                ]);
            } else {
                $existing->forceFill(['processed_at' => now(), 'payload' => $data])->save();
            }
            return $payment->fresh(['package','coupon']);
        }, 3);
    }

''' + s[end:]
    s=swap(s,'''            'status' => $paid ? 'paid' : 'failed',
            'payload' => json_decode($payload, true) ?: [],''','''            'status' => $paid ? 'paid' : ($type === 'checkout.session.completed' ? 'pending' : ($type === 'checkout.session.expired' ? 'cancelled' : 'failed')),
            'amount_minor' => isset($session->amount_total) ? (int) $session->amount_total : null,
            'currency' => isset($session->currency) ? strtoupper((string) $session->currency) : null,
            'payload' => json_decode($payload, true) ?: [],''','stripe delayed-payment reconciliation')
    # A retry is a brand-new transaction, never a re-use of a previously signed session.
    entry='    public function gateways(): array\n'
    retry='''    public function retryCheckout(User $user, int $paymentId): array
    {
        $previous = PackagePayment::query()->where('user_id', $user->id)
            ->with('coupon')->findOrFail($paymentId);
        if (! in_array($previous->payment_status, ['Failed','Cancelled'], true)) {
            throw new ApiException('Only failed or cancelled payments can be retried.', 409, ApiErrorCode::ValidationFailed->value);
        }
        if (! $previous->package_id || CustomCoinService::payloadOf($previous) !== null) {
            throw new ApiException('This purchase type requires a new checkout.', 422, ApiErrorCode::ValidationFailed->value);
        }
        $payload = [
            'package_id' => $previous->package_id,
            'gateway' => $previous->payment_method,
            'currency' => $previous->currency ?: 'PKR',
        ];
        if ($previous->coupon && $previous->coupon->active) {
            $payload['coupon_code'] = $previous->coupon->code;
        }
        $previousMeta = (array) ($previous->metadata ?? []);
        foreach (['jazzcash_phone','easypaisa_phone'] as $phoneKey) {
            if (filled($previousMeta[$phoneKey] ?? null)) $payload[$phoneKey] = $previousMeta[$phoneKey];
        }
        return $this->checkout($user, $payload);
    }

'''
    if 'function retryCheckout(' not in s: s=swap(s,entry,retry+entry,'payment retry')
    # Protect finite-use coupons during checkout with serialized reservations.
    anchor='''        $result = DB::transaction(function () use ($user, $package, $coupon, $discount, $payableAmount, $gateway, $reference, $data, $checkoutToken, $checkoutTokenHash, $checkoutTokenExpiresAt) {
            $payment = PackagePayment::create(['''
    replacement='''        $result = DB::transaction(function () use ($user, $package, $coupon, $discount, $payableAmount, $gateway, $reference, $data, $checkoutToken, $checkoutTokenHash, $checkoutTokenExpiresAt) {
            if ($coupon) {
                $lockedCoupon = PaymentCoupon::query()->whereKey($coupon->id)->lockForUpdate()->firstOrFail();
                $freshCoupon = $this->couponByCode($lockedCoupon->code);
                if (abs($this->discountFor($freshCoupon, (float) $package->price) - $discount) > 0.009) {
                    throw new ApiException('Promo code changed. Please revalidate it.', 409, ApiErrorCode::ValidationFailed->value);
                }
                if ($lockedCoupon->usage_limit !== null) {
                    $reserved = PackagePayment::query()->where('coupon_id',$lockedCoupon->id)
                        ->where('payment_status','Due')->where('created_at','>=',now()->subMinutes(45))->count();
                    if ((int)$lockedCoupon->used_count + $reserved >= (int)$lockedCoupon->usage_limit) {
                        throw new ApiException('Promo code is fully reserved. Please try later.', 409, ApiErrorCode::ValidationFailed->value);
                    }
                }
            }
            $payment = PackagePayment::create(['''
    s=swap(s,anchor,replacement,'coupon reservation')
    return s

def payment_controller(s):
    marker='    public function history(PaymentHistoryRequest $request): JsonResponse\n'
    new='''    public function retry(Request $request, int $payment): JsonResponse
    {
        $result = $this->payments->retryCheckout($request->user(), $payment);
        if ($result['gateway'] === PaymentGateway::JazzCash->value && $result['payment']->payment_status !== 'Paid') {
            $result['checkout'] = $this->wallets->jazzcashCheckout($result['payment']);
        }
        return $this->success([
            'payment' => new PaymentResource($result['payment']->fresh()),
            'checkout' => $result['checkout'],
            'security' => $result['security'],
        ], 'New checkout created for retry.', 201);
    }

'''
    return s if 'function retry(Request $request' in s else swap(s, marker,new+marker,'payment controller retry')

def api_v1(s):
    marker="        Route::post('/checkout', [PaymentController::class, 'checkout'])->middleware('throttle:10,1')->name('checkout');"
    new=(marker+"\n        Route::post('/checkout/{payment}/retry', [PaymentController::class, 'retry'])->whereNumber('payment')->middleware('throttle:5,1')->name('checkout.retry');")
    s=swap(s,marker,new,'payment retry route')
    anchor="        Route::get('/invoices/{payment}', [PaymentController::class, 'invoice'])->name('invoice');"
    s=swap(s,anchor,anchor+"\n        Route::get('/refund-requests', [\\App\\Http\\Controllers\\Api\\V1\\Payment\\RefundRequestController::class, 'index'])->name('refunds.index');\n        Route::post('/refund-requests/{payment}', [\\App\\Http\\Controllers\\Api\\V1\\Payment\\RefundRequestController::class, 'store'])->whereNumber('payment')->middleware('throttle:5,1')->name('refunds.store');",'refund API routes')
    return s

def api_bridge(s):
    imp='use App\\Http\\Controllers\\Api\\V1\\CompletionController;'
    s=swap(s,imp,imp+'\nuse App\\Http\\Controllers\\Api\\V1\\Premium\\PremiumEntitlementController;','API entitlements import')
    route="Route::get('/connector-c', [BridgeController::class, 'channelC'])->name('connector_c');"
    s=swap(s,route,route+"\nRoute::get('/entitlements', [PremiumEntitlementController::class, 'index'])->name('api.v1.bridge.entitlements');",'API entitlements route')
    return s

def search_service(s):
    s=swap(s,"$spiritual->where('sub_caste_id', $filters['sect_id']);","$spiritual->where('sect_main_id', $filters['sect_id']);",'sect mapping')
    old='''        if (! empty($filters['mutual_match'])) {
            $query->whereHas('received_proposals', function ($proposal) use ($viewer) {
                $proposal->where('interested_by', $viewer->id)
                    ->where('status', ProposalStatus::Accepted->value);
            })->whereHas('sent_proposals', function ($proposal) use ($viewer) {
                $proposal->where('user_id', $viewer->id)
                    ->where('status', ProposalStatus::Accepted->value);
            });
        }'''
    new='''        if (! empty($filters['mutual_match'])) {
            // An accepted interest from either direction is sufficient; requiring
            // two reciprocal rows makes normal mutually-accepted proposals vanish.
            $query->whereExists(function ($q) use ($viewer) {
                $q->selectRaw('1')->from('express_interests as accepted_interest')
                    ->where('accepted_interest.status', ProposalStatus::Accepted->value)
                    ->where(function ($pair) use ($viewer) {
                        $pair->where(function ($direction) use ($viewer) {
                            $direction->where('accepted_interest.interested_by', $viewer->id)
                                ->whereColumn('accepted_interest.user_id','users.id');
                        })->orWhere(function ($direction) use ($viewer) {
                            $direction->where('accepted_interest.user_id', $viewer->id)
                                ->whereColumn('accepted_interest.interested_by','users.id');
                        });
                    });
            });
        }'''
    s=swap(s,old,new,'accepted mutual search')
    marker='''        $this->applyFilters($query, $viewer, $filters);'''
    gate='''        $advancedKeys = ['height_min','height_max','income_min','income_max','compatibility_min',
            'exclude_viewed','mutual_match','lifestyle','caste_id','sub_caste_id','sect_id'];
        foreach ($advancedKeys as $key) {
            if (in_array($key, ['exclude_viewed','mutual_match'], true) &&
                ! filter_var($filters[$key] ?? false, FILTER_VALIDATE_BOOLEAN)) continue;
            if (isset($filters[$key]) && $filters[$key] !== '' && $filters[$key] !== null && $filters[$key] !== false) {
                abort_unless(app(\\App\\Services\\ClientScope\\PremiumEntitlementService::class)->has($viewer,'advanced_filters'),
                    403, 'Advanced filters require an active premium plan.');
                break;
            }
        }
        $this->applyFilters($query, $viewer, $filters);'''
    s=swap(s,marker,gate,'premium advanced filters')
    return s

def home_controller(s):
    # Restrict to just member_listing method.
    start=s.index('    public function member_listing(Request $request)')
    end=s.index('    public function profile_edit(Request $request)',start)
    a=s[start:end]
    ent='''        $premiumKeys = ['caste_id','sub_caste_id','min_height','max_height','mutual_match'];
        foreach ($premiumKeys as $filterName) {
            if ($request->filled($filterName) && (string) $request->input($filterName) !== '0') {
                abort_unless(app(\\App\\Services\\ClientScope\\PremiumEntitlementService::class)
                    ->has($request->user(), 'advanced_filters'), 403,
                    'Advanced filters require an active premium plan.');
                break;
            }
        }
        if (\\Illuminate\\Support\\Facades\\Schema::hasTable('analytics_events')) {
            app(\\App\\Services\\ClientScope\\EventRecorder::class)
                ->record($request, 'discovery_opened', 'discovery');
        }
'''
    a=swap(a,'    {\n        $age_from', '    {\n'+ent+'        $age_from','web search entitlement')
    a=swap(a,"        $gender         = ($request->gender != null) ? $request->gender : null;",'''        $gender         = ($request->gender != null) ? $request->gender : null;
        $recently_active = $request->boolean('recently_active');
        $online_now = $request->boolean('online_now');
        $new_profiles = $request->boolean('new_profiles');
        $mutual_match = $request->boolean('mutual_match');''','web filter variables')
    a=swap(a, """            ->where('deactivated', 0);""", r"""            ->where('deactivated', 0)
            ->whereHas('member', fn ($m) => $m->where(fn ($q) => $q->where('hide_profile', 0)->orWhereNull('hide_profile')))
            ->whereDoesntHave('profile_privacy_setting', fn ($p) => $p->where('invisible_mode', true))
            ->whereNotIn('users.id', \App\Models\HiddenProfileUser::query()
                ->where('hidden_from_user_id', $request->user()->id)->select('user_id'));""", 'web search privacy filtering')
    a=swap(a, """        elseif (!empty($profession)) {""", """        if (!empty($profession)) {""", 'combined profession search')
    # Avoid the old bug where an empty filter query results in an unfiltered list.
    a=a.replace('''            if (count($user_ids) > 0) {
                $users = $users->WhereIn('id', $user_ids);
            }''','''            $users = $users->whereIn('id', $user_ids);''')
    a=swap(a,'''        $users = $users->paginate(10);''','''        if ($recently_active) $users->where('last_active_at','>=',now()->subDays(30));
        if ($online_now) $users->where('last_active_at','>=',now()->subMinutes(15));
        if ($new_profiles) $users->where('users.created_at','>=',now()->subDays(14));
        if ($mutual_match) {
            $viewerId = (int) $request->user()->id;
            $users->whereExists(function ($q) use ($viewerId) {
                $q->selectRaw('1')->from('express_interests as ei')->where('ei.status', 1)
                    ->where(function ($pair) use ($viewerId) {
                        $pair->where(function ($dir) use ($viewerId) {
                            $dir->where('ei.interested_by',$viewerId)->whereColumn('ei.user_id','users.id');
                        })->orWhere(function ($dir) use ($viewerId) {
                            $dir->where('ei.user_id',$viewerId)->whereColumn('ei.interested_by','users.id');
                        });
                    });
            });
        }
        $users = $users->paginate(10)->withQueryString();''','web additional filters')
    a=swap(a,"'member_type', 'gender'));","'member_type', 'gender', 'recently_active', 'online_now', 'new_profiles', 'mutual_match'));",'web filter view vars')
    return s[:start]+a+s[end:]

def advanced_view(s):
    anchor='''                    <div class="row">'''
    fields='''                    <div class="form-group mb-3" role="group" aria-label="{{ translate('Activity and match filters') }}">
                        <label class="d-block"><input type="checkbox" name="recently_active" value="1" @checked(request()->boolean('recently_active'))> {{ translate('Recently active (30 days)') }}</label>
                        <label class="d-block"><input type="checkbox" name="online_now" value="1" @checked(request()->boolean('online_now'))> {{ translate('Online recently (15 minutes)') }}</label>
                        <label class="d-block"><input type="checkbox" name="new_profiles" value="1" @checked(request()->boolean('new_profiles'))> {{ translate('New profiles (14 days)') }}</label>
                        <label class="d-block"><input type="checkbox" name="mutual_match" value="1" @checked(request()->boolean('mutual_match'))> {{ translate('Accepted mutual interests (premium)') }}</label>
                    </div>
'''
    return swap(s,anchor,fields+anchor,'web search filter checkboxes')

def panel(s):
    anchor='''        <div class="d-flex align-items-start">'''
    addition='''        @include('frontend.member.partials.accessibility_tools')
        <div class="mb-3"><a href="{{ route('member.payment_refunds.index') }}" class="btn btn-sm btn-outline-secondary">{{ translate('Billing & Refund Requests') }}</a></div>
'''
    return swap(s,anchor,addition+anchor,'member accessibility and billing tools')

def completion_controller(s):
    old="""abort_if(in_array($data['event_name'], ['got_match','got_match_confirmed','referral_reward_issued'], true),
            422, 'This event must be recorded by the backend.');"""
    new="""$clientEvents = ['discovery_viewed','profile_viewed','search_performed','article_opened',
            'sponsor_viewed','help_center_opened','feature_explored'];
        abort_unless(in_array($data['event_name'], $clientEvents, true),
            422, 'This event is server-managed or unsupported.');"""
    return swap(s,old,new,'analytics event whitelist')

def report_service(s):
    marker = "            'cohorts' => $this->cohorts(),"
    addition = "\n            'observed_retention' => app(\\App\\Services\\ClientScope\\RetentionCohortService::class)->monthly(),"
    return swap(s, marker, marker + addition, 'observed cohort report')

def admin_dashboard_view(s):
    marker = "@section('content')"
    return swap(s, marker, marker + "\n@include('admin.client_scope.partials.observed_retention')", 'admin cohort panel')

def interest_controller(s):
    old = "if ($priority && ! $this->hasFeatureFlag($featureFlags, 'priority_interest')) {"
    new = "if ($priority && ! app(\\App\\Services\\ClientScope\\PremiumEntitlementService::class)->has($sender, 'priority_interest')) {"
    return swap(s, old, new, 'enforce active priority plan')

def web_routes(s):
    marker="require __DIR__.'/referral.php';"
    return swap(s, marker, marker + "\nrequire __DIR__.'/hamqadam_step3.php';", 'web billing routes')

def admin_routes(s):
    marker="    Route::resource('package-payments', PackagePaymentController::class);"
    additions=r"""    Route::get('/payment-refunds', [\App\Http\Controllers\Admin\PaymentRefundController::class, 'index'])->name('admin.payment_refunds.index');
    Route::post('/payment-refunds/{refund}/review', [\App\Http\Controllers\Admin\PaymentRefundController::class, 'review'])->name('admin.payment_refunds.review');
"""
    return swap(s,marker,additions+marker,'admin refund routes')

def admin_sidenav(s):
    marker='''                    <a href="{{ route('package-payments.index') }}" class="aiz-side-nav-link">'''
    additional='''                    <a href="{{ route('admin.payment_refunds.index') }}" class="aiz-side-nav-link">'''
    # Put a distinct link near existing package payments, under same permission block.
    block='''                @can('show_package_payments')
                <li class="aiz-side-nav-item">
                    <a href="{{ route('admin.payment_refunds.index') }}" class="aiz-side-nav-link">
                        <i class="las la-file-invoice-dollar aiz-side-nav-icon"></i>
                        <span class="aiz-side-nav-text">{{ translate('Refund Requests') }}</span>
                    </a>
                </li>
                @endcan
'''
    anchor='''                <!-- Earnings -->'''
    return swap(s,anchor,block+anchor,'admin refund menu')

def frontend_layout(s):
    locale = "$lang = \\App\\Models\\Language::where('code', $locale)->first();"
    updated = """$locale = $locale ?: env('DEFAULT_LANGUAGE', 'en');
$lang = \\App\\Models\\Language::where('code', $locale)->first();
if (! $lang) {
    $locale = env('DEFAULT_LANGUAGE', 'en');
    $lang = \\App\\Models\\Language::where('code', $locale)->first();
}
\\Illuminate\\Support\\Facades\\App::setLocale($locale);"""
    s=swap(s,locale,updated,'safe language fallback')
    old="\\App\\Models\\Language::where('code', Session::get('locale', Config::get('app.locale')))->first()->rtl == 1"
    if s.count(old) == 2:
        s=s.replace(old,"($lang?->rtl == 1 || in_array($locale, ['ur','ar'],true))")
    elif old not in s and s.count("($lang?->rtl == 1 || in_array($locale, ['ur','ar'],true))") == 2:
        pass
    else:
        raise RuntimeError('Unexpected language RTL condition layout')
    return s

TRANSFORMS={
 'app/Services/Api/V1/Payment/PaymentService.php':payment_service,
 'app/Http/Controllers/Api/V1/Payment/PaymentController.php':payment_controller,
 'routes/api_v1.php':api_v1,
 'routes/api_bridge.php':api_bridge,
 'app/Services/Api/V1/Search/ProfileSearchService.php':search_service,
 'app/Http/Controllers/HomeController.php':home_controller,
 'resources/views/frontend/member/member_listing/advanced_search.blade.php':advanced_view,
 'resources/views/frontend/layouts/member_panel.blade.php':panel,
 'app/Http/Controllers/Api/V1/CompletionController.php':completion_controller,
 'app/Services/ClientScope/ReportService.php':report_service,
 'resources/views/admin/client_scope/dashboard.blade.php':admin_dashboard_view,
 'app/Http/Controllers/Api/V1/Interest/InterestController.php':interest_controller,
 'routes/web.php':web_routes,
 'routes/admin.php':admin_routes,
 'resources/views/admin/inc/sidenav.blade.php':admin_sidenav,
 'resources/views/frontend/layouts/app.blade.php':frontend_layout,
}

def main():
    parser=argparse.ArgumentParser()
    parser.add_argument('--apply',action='store_true',help='write fixes into the current Laravel source tree')
    parser.add_argument('--check',action='store_true',help='only validate available code anchors')
    args=parser.parse_args()
    if not (ROOT/'artisan').exists() or not (ROOT/'composer.json').exists():
        raise SystemExit('Extract this ZIP into your Laravel project root (where artisan and composer.json are located).')
    modified={}
    for name, callback in TRANSFORMS.items():
        path=ROOT/name
        if not path.is_file(): raise SystemExit('Missing expected source file: '+name)
        old=path.read_text(encoding='utf-8')
        try: new=callback(old)
        except Exception as exc: raise SystemExit('Cannot safely patch '+name+': '+str(exc))
        if new!=old:modified[name]=new
    print('Anchor validation passed. Pending modifications:',len(modified))
    for p in modified:print('  '+p)
    if not args.apply:
        print('Dry-run only. Run python3 apply_step3.py --apply to write changes.')
        return
    folder=ROOT/'storage'/'app'/'step3-backups'/datetime.datetime.now().strftime('%Y%m%d_%H%M%S')
    for name, new in modified.items():
        target=ROOT/name
        backup=folder/name
        backup.parent.mkdir(parents=True,exist_ok=True)
        shutil.copy2(target,backup)
        tmp=target.with_name(target.name+'.step3.tmp')
        tmp.write_text(new,encoding='utf-8')
        tmp.replace(target)
    print('Applied. Backups:',folder)
    print('Remove this installation script from the web-accessible root after use.')

if __name__=='__main__':
    main()
