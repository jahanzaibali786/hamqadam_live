# HamQadam MVP QA Handoff

Date: 2026-07-09

## Automated Checks Run

| Check | Result | Notes |
|---|---|---|
| `php artisan about` | Passed | Laravel boots on 12.36.1, PHP 8.2.12, local env. |
| `php artisan route:list --path=api/v1` | Passed | 137 v1 routes registered. |
| `php artisan route:list --path=member` | Passed | Member dashboard/search/profile routes are registered. |
| `php artisan route:list --path=api-docs` | Passed | Browser API docs route exists. |
| `php artisan view:cache` | Passed | Blade templates compile successfully. |
| `php artisan view:clear` | Passed | Compiled views cleared after validation. |
| `php artisan storage:link` | Passed | `public/storage` linked to `storage/app/public`. |
| `php -l` key controllers | Passed | Home, PackagePayment, ProfileMatch, V1PlatformConsole controllers compile. |
| OpenAPI JSON parse | Passed | `public/api/openapi-v1.json` is valid JSON. |
| Postman JSON parse | Passed | `public/api/postman-v1.json` is valid JSON. |
| `git diff --check` | Passed with warnings | Only existing CRLF normalization warnings reported. |
| `composer validate --strict` | Passed with warnings | Warnings: unbound Apple package version, exact core-component version. |
| `php artisan test` | Blocked | PHPUnit vendor mismatch: missing `Configuration::registerMockObjectsFromTestArgumentsRecursively()`. |

## Environment Notes

- `public/storage` is linked locally. Run `php artisan storage:link` again on the deployment server after release.
- Migration status shows legacy pending migrations:
  - `0001_01_01_000002_create_jobs_table`
  - `2023_10_09_094817_create_manual_payment_methods_table`
  - `2025_11_03_114019_create_permission_tables`
- The new MVP migrations through `2026_07_09_000002_create_family_meetings_and_community_modules` are marked as run.
- PHPUnit 11.5.43 is installed, but the current vendor tree fails before running tests. Reinstall/update dev dependencies before trusting automated tests.

## App API Docs

- Browser docs: `/api-docs`
- OpenAPI: `/api/openapi-v1.json`
- Postman: `/api/postman-v1.json`
- App-facing docs intentionally exclude legacy `/api/member/...` endpoints and backend gateway webhook requests.

## Manual QA Flow

1. Register a new user from web and mobile API.
2. Confirm registration requires the full onboarding profile and partner preferences.
3. Confirm the user is approved/verified after successful registration.
4. Confirm Basic Free package is applied automatically and coin balance is added.
5. Log in through `/api/v1/auth/login/email`.
6. Call `/api/v1/auth/me` and confirm the token/session works.
7. Open member dashboard and AI dashboard.
8. Run match recalculation and confirm `/api/v1/matches/recommended`, `/daily`, and profile compatibility return scores/reasons.
9. Search profiles with age, city, religion, verified, photo, and compatibility filters.
10. Send proposal, accept/reject, and verify timeline updates.
11. Add/remove favourite and ignore profile.
12. Test chat thread listing and message send after an accepted proposal.
13. Submit verification documents and approve from admin.
14. Open packages page and confirm Basic Free is marked active.
15. Start Stripe checkout and confirm payment remains pending until gateway confirmation.
16. Start EasyPaisa/JazzCash checkout and confirm payment is pending admin/gateway approval.
17. Approve pending payment from admin and confirm membership limits update.
18. Check payment history and invoice on user dashboard.
19. Register/update FCM token and confirm notification preferences save.
20. Report/block a user and confirm admin moderation queue sees the case.
21. Add guardian, enable Wali/Mehram mode, create family note, and start family conversation.
22. Submit success story, ask expert question, create forum thread, and register for webinar.

## Known Launch Blockers

- Fix PHPUnit dependency mismatch before relying on automated test coverage.
- Run `php artisan storage:link` on the target server.
- Configure real Stripe, EasyPaisa, JazzCash, mail, SMS, and FCM credentials.
- Decide whether legacy pending migrations should be marked migrated or safely applied in the target database.
- Review timezone; current app reports `Europe/London`, while Pakistan deployment should usually use `Asia/Karachi`.
