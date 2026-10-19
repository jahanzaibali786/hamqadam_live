# Phase 10 deployment and QA verification

This PR adds the previously missing nine PHPUnit files, a dedicated GitHub Actions workflow, seeded configuration/coupons/analytics (staging only), and a UI acceptance guide.

## Why the earlier workflow passed while Phase10 was missing

PR #66 was merged while incomplete. PR #67 added one `tests/Feature/Phase10/QaSeederSafetyTest.php` and guarded placeholder seeders. The earlier workflow did **not** run the complete Phase10 suite. GitHub Actions CI does **not** deploy or pull GitHub files to the production server.

## On production: read-only checks

```bash
pwd
git rev-parse --is-inside-work-tree
git remote -v
git rev-parse HEAD
git status --short --branch
git ls-files 'tests/Feature/Phase10/*'
php artisan about --only=environment
```

Never run `DemoMatrimonialSeeder`, `HamqadamQaDemoSeeder`, or a database-writing PHPUnit run against the live database. PR #67 replaced the unsafe legacy member seeder with a non-destructive QA-only preflight.

## For isolated staging only

Create a separate clone and separate QA database. Ensure `APP_ENV=testing` or `staging` (never switch the live site's environment just to bypass the guard). Ensure PHP has pdo_sqlite or a dedicated isolated test database and do not reuse production credentials.

```bash
php artisan migrate:status
php artisan db:seed --class=HamqadamQaDemoSeeder
php artisan test tests/Feature/Phase10 --stop-on-failure
```

The opt-in demo seeders provide named reward rules, sponsored placements, the QADEMO10 coupon, synthetic analytics and unpublished editorial entries. Existing records are preserved when present. The matcher and payments seeders deliberately require manual staging UI/test-mode provider exercises instead of inserting financially misleading Paid rows or fabricated match successes.

The previous failed production seeder may have updated records **before** failing at education.degree. Inspect the demo account emails and any package named Basic Free in a production database backup; do not attempt blind cleanup or overwrite.

## Review criteria

- The Phase 10 GitHub Actions workflow runs and reports explicit test results.
- Reward rules and sponsored placement rows are correct and do not overwrite customized rules.
- All manual cases are signed off in the provided QA workbook.
- No production `.env`, credentials or genuine member/payment records are copied into CI.
