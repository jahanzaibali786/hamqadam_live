# Phase 6B — Dashboard recovery and Stripe reconciliation inspection

## Root-cause analysis and remediation
- The production dashboard previously ended in a PHP `ParseError` expecting `elseif`, `else`, or `endif` around the final `@endsection` line.
- Textual Blade directive counts were balanced, but that only proves the source directives balance, not that the Laravel Blade compiler generates valid PHP. This is why the prior “simplify @if blocks” fix was insufficient.
- The updated dashboard replaces all Blade conditional/loop control directives and inline/block `@php` sections with corresponding native PHP conditionals/loops and `<?php ... ?>` syntax. The Blade template retains only its outer `@extends`, `@section`, `@endsection`, and CSRF directives.
- A new test compiles the exact dashboard Blade source with `Blade::compileString()` and runs PHP syntax lint on the resulting compiled PHP, preventing future silent regressions.
- The PR validation workflow includes the dashboard compile test. **Do not merge until it passes**. The deployed server must clear stale compiled views after merge.

## Phase 6B: Stripe reconciliation
- New `php artisan payments:inspect-stripe --limit=30` command checks recent unpaid Stripe checkout sessions against the provider. It checks payment status, amount and currency and reports mismatches without mutating records.
- Existing `php artisan payments:audit` reports database inconsistencies without changing balances.
- This is a read-only observation mechanism and does not execute automatic payments, settlement or refunds.

## Production deployment
1. Verify the GitHub PR status and **success of the PHP lint + Blade view compilation checks** before merging.
2. Deploy through the existing approved GitHub merge workflow.
3. Run:
```bash
php artisan view:clear
php artisan optimize:clear
php artisan view:cache
php artisan payments:audit
php artisan payments:inspect-stripe --limit=20
```
4. Sign in as a real test member and visit `/dashboard` and both match panels.
5. Inspect `storage/logs/laravel.log` only if issues persist.

## Migrations
**No new migrations in Phase 6B**. Existing October 19 migrations have already been provided as independent `php artisan migrate --path=...` commands under `docs/PHASE5_MIGRATIONS_AND_VERIFICATION.md`.

## Important
Live MySQL, a Stripe merchant account and the production runtime are not accessible to this work. Source-level checks and GitHub CI should not be mistaken for successful production verification.
