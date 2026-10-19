# Hamqadam Phase 6C — Deployment and acceptance

All changes are tracked directly in Git. Do not run any Python installer.

## New migration: run individually

After database backup and after merging the approved PR, run the following from the Laravel root:

```bash
php artisan migrate --path=database/migrations/2026_10_19_000024_create_payment_refund_attempts.php --force
```

Check first with `php artisan migrate:status`.

**Important:** The deployment workflow does not apply migrations. The refund execution feature remains disabled by default and MUST NOT be enabled until the new table exists, Stripe test-mode gateway credentials work, and a verified refund has been exercised in sandbox.

## Features in Phase 6C

- Stripe refund attempt ledger with one stable idempotency key per refund request, enabling safe retry after network ambiguity.
- Stripe refund execution is **opt-in** through `HAMQADAM_STRIPE_REFUNDS_ENABLED=true` in the production `.env`. Keep **false** by default. This performs real monetary refunds when enabled.
- Admin-only POST route for authorized refund execution. Review and member intake remain separate.
- Amount, currency and original Stripe checkout payment-intent checks before requesting a refund.
- Existing webhook, refund and premium entitlement logic is retained.
- Deployment is now gated on a reusable PHP syntax/Blade compilation/route-test workflow; failed preflight blocks the rsync deploy.

## Verification

```bash
php artisan optimize:clear
php artisan view:cache
php artisan route:list --name=admin.payment_refunds.execute
php artisan test --filter=PaymentRefundExecutionGuardTest
php artisan test --filter=PremiumPolicyTest
php artisan payments:audit
```

Run PHPUnit tests on staging or development; the live deployment workflow excludes `tests/`.

## Provider testing and limitations

The refund action is a feature-flagged implementation, not evidence that a provider payout occurred. Validate with Stripe test keys, both provider-success and provider-timeout cases, and re-opened requests. A pending refund is NOT the same as a successful refund. Refund records are only marked provider-confirmed when Stripe reports succeeded.

The action currently supports **Stripe full refunds only**, for a single refund request per paid transaction. JazzCash/Easypaisa monetary refund APIs remain unimplemented and unavailable. Automatic subscription revocation is not performed because business rules remain to be established. Do not enable in live mode without a merchant-approved refund policy and accounting sign-off.

## Repository settings

The preflight workflow is a deployment dependency, but GitHub branch protection (Settings > Rules) is still recommended to require successful checks and review before merge. Branch protection cannot be set by a source code change alone.
