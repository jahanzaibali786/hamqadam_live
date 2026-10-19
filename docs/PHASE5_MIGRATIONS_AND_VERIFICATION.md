# Phase 5 — Git-only deployment and verification

All Laravel code changes are ordinary tracked files. No Python installer is needed.
This pull request introduces **no new migrations**. The six recent
October 19 migrations already on `main` should be confirmed individually
in `php artisan migrate:status`; run only those still marked Pending.

## Individual single-file migration commands

Run in the Laravel project root, after backing up the production DB:

```bash
php artisan migrate --path=database/migrations/2026_10_19_000001_restore_user_referral_columns.php --force
php artisan migrate --path=database/migrations/2026_10_19_000002_index_analytics_activity.php --force
php artisan migrate --path=database/migrations/2026_10_19_000003_create_payment_refund_requests.php --force
php artisan migrate --path=database/migrations/2026_10_19_000020_create_notification_digest_runs.php --force
php artisan migrate --path=database/migrations/2026_10_19_000021_create_member_profile_boosts.php --force
php artisan migrate --path=database/migrations/2026_10_19_000022_add_coupon_per_user_limit.php --force
```

Laravel tracks completed migrations, so rerunning one already applied normally reports Nothing to migrate. The profile-boost migration uses DATETIME for compatibility with older MySQL default timestamp configurations.

## After a reviewed PR merge

```bash
php artisan optimize:clear
php artisan route:list --name=member.referrals.index
php artisan route:list --name=member.payment_refunds.index
php artisan route:list --name=admin.payment_refunds.review
php artisan route:list --name=api.v1.payments.refunds
php artisan schedule:list
```

Run tests on a local or staging checkout with `require-dev` installed:

```bash
php artisan test --filter=MemberBillingAndRefundRoutesTest
php artisan test --filter=CompletionRoutesTest
php artisan test --filter=PremiumPolicyTest
```

## Refund policy and provider verification

- Members can open separate refund-request page through the persistent sidebar.
- Staff must review. A Stripe reference is verified by retrieving the successful Stripe refund and original checkout session, comparing payment intent, currency and amount before recording provider confirmation.
- The admin review action **does not create provider refunds**. Full refund issuance requires authorized Stripe refund initiation, refund-policy and provider transaction testing.
- EasyPaisa/JazzCash refund confirmations remain on hold until supported provider verification is connected. Do not mark those refunds confirmed manually through this UI.
- Refund confirmation currently does not revoke member entitlements; align that policy before accepting live payouts.
- Do not put Stripe secret keys into Git.

## QA checklist

Check sidebar links (mobile + desktop), member route authorization, refund request ownership, admin-only review, duplicate refund requests, Stripe successful/pending/failed refund records, webhook signature/duplicate delivery, paid-state immutability and migration status.

GitHub Actions deployment copies code and clears caches, **but does not run migrations or tests**. Test on staging, and review changes before merging to the auto-deploy branch.
