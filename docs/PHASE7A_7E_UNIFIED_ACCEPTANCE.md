# Hamqadam Phases 7A–7E — unified integration and honest acceptance

This PR is a **single Git-based, incremental Laravel review package**. No Python installer or full project replacement.

## Phase 7A — Notification and Support
- Notification click destinations now use a first-party relative-path allowlist and authenticated member routes.
- Existing reminder, digest, read status, click logging, and user preference services retained.
- Browser support ticket form validates category ownership against actual categories, ticket conversation context clarified.
- Remaining: true real-time support transport, multi-agent assignment, push-delivery callbacks, full email engagement reporting, device-side deep-link fallback and multilingual support reply UX.

## Phase 7B — Analytics
- New linked registered-user conversion funnel, with progressive intersection so each successive stage can only contain people from the previous stage.
- Signup retention denominator and mature D1/D7/D30 checks corrected to avoid including immature observation windows.
- Existing NPS, adoption, city distributions and event audit retained.
- Remaining: instrument every event from actual app/web actions; accurate order-sensitive event sequences, a geographic coordinate heatmap, verified marriage outcome attribution and HERA quality/performance measurement. No synthetic metrics.

## Phase 7C — Localization and Accessibility
- Mobile translation endpoint now exposes a non-empty-key count and explicitly declares full translation completion unverified.
- Existing RTL/language controls and member font/contrast preferences retained.
- Remaining: full translations in English, Urdu, Arabic and Hindi; WCAG 2.1 AA test, complete RTL screens, offline privacy-safe profiles, lite mode, region-specific copy, Flutter UI wiring.

## Phase 7D — Premium / Referrals / Ads / Rewards
- Existing shared PremiumEntitlementService, guarded priority interest, profile boost, referee anti-duplicate rewards, labeled sponsored listing placements and milestone scheduler are preserved.
- Remaining: comprehensive paid plan entitlement scenarios, wallet-reward reconciliation, Flutter client screens, premium ad exposure/browser QA, gamification full milestone catalog.

## Phase 7E — Payments / OTP / QA
- OTP email generation fails closed when the official sender or mailer delivery fails; local debug OTP is restricted to local + debug mode.
- Existing Stripe refund feature remains OFF by default, merchant settlement untouched. No live refunds are initiated by this PR.
- Added PHP 8.4 source lint, Blade compilation and regression tests workflow.
- Remaining: configure official mail domain and SPF/DKIM/DMARC outside source control, verify actual SMTP, sandbox reconciliation, staged invoice/refund cases, GitHub branch protection/check requirements.

## New migrations
**None**. No `php artisan migrate --path=...` command is required for this Phase 7 PR itself.

For earlier Phase 6C, the separate migration remains:

```bash
php artisan migrate --path=database/migrations/2026_10_19_000024_create_payment_refund_attempts.php --force
```

Only run if pending, after backup and in approved maintenance scope.

## After merge (only after green CI and staging validation)

```bash
php artisan optimize:clear
php artisan view:cache
php artisan route:list --path=api/v1
php artisan schedule:list
php artisan payments:audit
```

Never enable real refunds or use production mail until independent sandbox checks are complete.

## Scope confirmation
The Laravel repository does **not** include the Flutter project or service account keys; full App/Web/Admin end-to-end certification is therefore impossible solely from this PR. Absence of validation is not proof of completion.
