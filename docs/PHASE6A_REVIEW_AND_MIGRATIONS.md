# Phase 6A — Review, deployment and verification

## Fixed production error
- `resources/views/frontend/member/dashboard.blade.php` replaces deeply nested auto-match Blade blocks with balanced branches and supported member match cards.
- PR CI runs `php artisan view:cache` before merge to catch Blade compilation problems.

## Audit and reconciliation
- Admin payment reconciliation dashboard: `/admin/payment-audit`, restricted to admin auth and `show_package_payments` permission.
- Read-only CLI: `php artisan payments:audit`; returns a non-zero code with `--fail-on-anomaly`. It does **not** initiate refunds, correct balances, poll gateways or activate packages.
- Stripe provider refund verification remains via the existing refund review controller.
- Wallet gateways require merchant contract integration and credentials before any automated refund execution. Do not treat stored webhook events as evidence of payout.

## New Phase 6A migrations
**None.** This PR adds no database tables or schema changes.

## Prior individual migration commands (run only if pending)
```bash
php artisan migrate --path=database/migrations/2026_10_19_000001_restore_user_referral_columns.php --force
php artisan migrate --path=database/migrations/2026_10_19_000002_index_analytics_activity.php --force
php artisan migrate --path=database/migrations/2026_10_19_000003_create_payment_refund_requests.php --force
php artisan migrate --path=database/migrations/2026_10_19_000020_create_notification_digest_runs.php --force
php artisan migrate --path=database/migrations/2026_10_19_000021_create_member_profile_boosts.php --force
php artisan migrate --path=database/migrations/2026_10_19_000022_add_coupon_per_user_limit.php --force
```

## Merge procedure
1. Review PR files and check the `Laravel PR Validation` action succeeds.
2. Make a database backup if migrations are outstanding, then apply pending migrations.
3. Merge into main (existing deploy workflow runs automatically).
4. Run `php artisan optimize:clear && php artisan view:cache` and visit `/dashboard` as a verified member.
5. Run `php artisan route:list --name=admin.payment_audit.index` and `php artisan payments:audit`.
6. Verify `/admin/payment-audit` as an authorized admin. Test non-admin access is denied.

## Known limitations
- No live gateway or MySQL tests executed in this ChatGPT session.
- Read-only audit flags anomalies but does not resolve them automatically.
- No provider refund API initiation added (admin review only).
- The PR action is a new check, separate from the SSH deploy action. Check protection rules before enabling auto-deployment.
