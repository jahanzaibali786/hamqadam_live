# Hamqadam Phase 8B–8E and CI recovery

All changes are native tracked Laravel files, delivered in a review PR. No Python scripts.

## CI blocker fixed
Earlier CI exited during Composer package discovery because `get_setting()` did not handle both cache and MySQL being unavailable. The helper now returns the specified default if the database query also fails. CI sets SQLite test configuration before Composer scripts; PHP lint, Blade compilation and focused feature tests still must pass.

## 8B — Live support and notification audit
The existing broadcast-based Help Chat remains the real-time transport. Admin can now assign a conversation to a staff ID. Read-only notification audit lists recorded delivery states and reminders without pretending sent messages were delivered. Assignment is authenticated admin-only and requires a staff/admin account. Actual Pusher credentials, push delivery receipts, and Flutter screen behaviors require real environment testing.

## 8C — Analytics
Existing D1/D7/D30, NPS, linked cohort funnel, feature adoption, geography city distribution, and match confirmation services remain in place. Accurate final reporting still depends on comprehensive event instrumentation and cross-platform event provenance; verified marriage outcomes and HERA model benchmarking cannot be inferred.

## 8D — Localization
The server language endpoint reports available translated key counts, RTL direction and downloadable paginated content. Full translated assets, offline mobile profile cache and accessibility audit require Flutter source and editorial sign-off.

## 8E — Provider acceptance
`php artisan hamqadam:readiness` checks operational prerequisites without ever sending OTP, performing payment, refunding funds or altering subscribers. Stripe refund execution remains disabled unless explicitly enabled by merchant. Tests and sandbox verification remain necessary. DNS SPF/DKIM/DMARC verification is external to this repository.

## New migration: run separately after merge and backup

```bash
php artisan migrate --path=database/migrations/2026_10_19_000025_add_help_chat_agent_assignment.php --force
```

Prior unexecuted refund ledger migration (only if pending):

```bash
php artisan migrate --path=database/migrations/2026_10_19_000024_create_payment_refund_attempts.php --force
```

## After reviewed PR merge and schema update

```bash
php artisan optimize:clear
php artisan view:cache
php artisan route:list --name=admin.notification_audit.index
php artisan route:list --name=admin.help-chat.assign
php artisan hamqadam:readiness
php artisan schedule:list
```

Production readiness requires an actual authenticated member/admin regression test, mail DNS and delivery tests, help-chat broadcast test and payment gateway sandbox test. Do not interpret a green deployment as completed provider acceptance.
