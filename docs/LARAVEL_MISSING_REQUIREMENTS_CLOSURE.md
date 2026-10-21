# Hamqadam — Laravel / Website Missing Requirements Closure

Scope is strictly the Laravel backend, website, admin panel and Laravel APIs. **Flutter source is not present**, so this is not an app implementation or a claim of end-to-end acceptance for both client documents.

## Changes in this PR
- Mutual Got Match confirmations nominate a distinct staff-verification candidate; previously reported mutual matches can be enumerated with a bounded dry-run/backfill command.
- Admin can review a pair with approval/rejection, verification method, staff identity, optional associated story and persisted append-only review events. Unilateral matches cannot be marked verified and a linked story must belong to a participant.
- Admin analytics distinguishes self-reported Got Match, mutually reported review candidates, staff-verified outcomes and linked stories; the terms are **not synonymous with legally verified marriage**.
- Geographic city distribution can now be placed on a real latitude/longitude coordinate-grid bubble visualization, with staff-supplied city-center coordinates and explicit count of records without coordinates. It **does not fabricate member GPS locations**.
- Admin translation coverage audit compares nonempty Urdu, Arabic, Hindi and English keys to English database reference keys, with representative missing key names and no false claim of linguistic certification.
- Client analytics events explicitly identify the untrusted mobile-client origin; server-owned sensitive milestone events remain restricted.

- Admin notification engagement reports include actual sent/read/click/reminder counters and explicitly label delivery receipts.
- HERA reporting now surfaces stored AI-assisted recommendation coverage and average recorded match scores, explicitly not model ground-truth accuracy.
- Member website and API interest/chat events are instrumented from successful server actions, with no message text in analytics.
- Admin can export/import approved translations in CSV with role permissions, known-key validation, HTML prevention, and spreadsheet formula protection.
- Website keyboard skip navigation, focus outlines and reduced-motion accessibility improve base navigation without falsely claiming WCAG certification.
- Support tickets and Help Chat have optional requested-language preferences for EN/UR/AR/HI; private messages are not forwarded for automatic translation.

## New migrations — individually, after database backup and approval
```bash
php artisan migrate --path=database/migrations/2026_10_19_000026_create_verified_match_outcome_reviews.php --force
php artisan migrate --path=database/migrations/2026_10_19_000027_create_city_geo_coordinates.php --force
php artisan migrate --path=database/migrations/2026_10_19_000028_add_support_language_preferences.php --force
```

Previously pending migrations must be applied individually if not already applied:
```bash
php artisan migrate --path=database/migrations/2026_10_19_000024_create_payment_refund_attempts.php --force
php artisan migrate --path=database/migrations/2026_10_19_000025_add_help_chat_agent_assignment.php --force
```

## Opt-in candidate reconciliation
First inspect mutual reports without database changes:
```bash
php artisan hamqadam:match-outcome-candidates --limit=500 --dry-run
```
After review, run the same command without `--dry-run` to create pending review candidates. This **never verifies** any outcome; verification requires explicit administrator review.

## Release acceptance checklist
After the migration, operators can run:

```bash
php artisan hamqadam:match-outcome-candidates --limit=500 --dry-run
```

Existing match reports are only *candidate nominations* until a real staff review. For the language CSV editor visit Admin → Languages → selected language → Bulk Import/Export.

```bash
php artisan optimize:clear
php artisan view:cache
php artisan route:list --name=admin.client.scope
php artisan hamqadam:readiness
php artisan payments:audit
```
In an authenticated staging environment, confirm:
1. Mutual reports create one review candidate regardless of which participant reports last.
2. A single report cannot be marked verified.
3. Admin approval/rejection appends audit history, and linked story ownership is enforced.
4. Aggregate map shows no exact user address; only city counts with staff-entered coordinates.
5. Translation dashboard reports missing keys and correct coverage denominators.
6. Notification audit and HERA reports display only genuinely recorded evidence and never claim missing provider receipts or ground truth.
7. Bulk translation CSV export/import runs using the language-translation permission; sample Urdu/Arabic/Hindi strings render correctly.
8. Support ticket and Help Chat language preferences are saved, visible and validated; actual agent-language routing is approved by the operations team.
9. Notifications, support, search, packages, referrals, Stripe sandbox and OTP are regression-tested.

## What is NOT yet certified
A source-code PR cannot independently certify **complete** 30-point client signoff: mail sender SPF/DKIM/DMARC and delivery, Stripe/Easypaisa/JazzCash processing and refunds, device push receipts, real-time Pusher, full professional Urdu/Arabic/Hindi text, third-party map data, WCAG 2.1 AA, external HERA/AI model outcome benchmarks and production database state remain outstanding acceptance dependencies. Full translation coverage still requires editorial content and application-wide extraction of strings. HERA performance must be backed by genuine model invocation logs, never imputed from member matches. Do not silently switch on real financial actions.

## Rollout policy
This is a draft GitHub pull request; leave it unmerged until all CI checks and staging database migrations have passed. Deployment does not run migrations.
