# IMPORTANT: CURRENT QA IMPLEMENTATION

The original walkthrough below describes intended fixture scenarios. The production-safe code in this branch **does not fabricate Paid gateway invoices, provider refunds, mutual confirmations, marriage verification, or login credentials**. The revised `DemoMatrimonialSeeder` only checks for existing test member accounts; it does not create or reset them. Use the staging admin/member UI and test-mode provider to prepare those scenarios. Never change `APP_ENV` of a live site or run these tests with a production database. Successful GitHub Actions execution does **not** deploy files to your hosting server.

---

# Hamqadam — Phase 10 QA Test Cases, Demo Seeders and UI Acceptance Guide

**Basis:** Hamqadam_Missing_Feature(2).docx (30 features), Hamqadam updates.docx (additional client issues), Laravel repository `jahanzaibali786/hamqadam_live` at `main` SHA `48cad8b` (2026-10-19); verify newer commits before merging the QA patch. This guide covers **Laravel, website, admin and API interfaces**, not Flutter UI implementation or provider certification.

**Status:** This ZIP contains source files with PHP syntax validated by `php -l`. The automated Laravel test suite, MySQL migrations, actual email/Pusher/FCM/Stripe/Easypaisa/JazzCash calls, and browser/mobile acceptance have **not** been run in this offline packaging environment. Run the tests in your own isolated staging deployment. **Do not claim that a route existing proves a feature is fully functional.**

## 1. Safety first

1. **Use an isolated copy of the application with a dedicated staging database and storage.** Never point `.env` at production database, Redis, SMTP, push/FCM, webhooks, or merchant credentials.
2. The QA seeders are **not** attached to `database/seeders/DatabaseSeeder.php`. They refuse to run unless `APP_ENV` is `local`, `testing` or `staging`. Never rename a production environment to staging merely to bypass the guard.
3. The existing `DemoMatrimonialSeeder` resets the passwords of four hardcoded `.test` accounts and updates those profiles. **Only** run it against the isolated QA database. It sets their password to `password` in its current source. Do not expose a staging installation on the public internet with these credentials.
4. Refund seed rows use `payment_method=qa_demo_no_provider`, `gateway_status=demo_only`; these are **synthetic database fixtures**, not evidence of a real bank charge or provider refund. Do not enable the Stripe execution button for these fixtures.
5. Synthetic analytics are tagged `source=qa_demo_fixture` and can affect **staging** dashboard totals. Demo satisfaction entries use context `qa_demo_nps`, excluded from real NPS (context `nps`). Demo mail logs are stored records only; no messages are sent by the seeders.
6. The QA mutual match is only *user-reported test data*, not a verified marriage; its review status stays `pending`. QA blog posts and success story are unpublished. Unapproved content must not be published publicly.
7. Verify with separate admin and member browsers/accounts. Never test permissions by disabling middleware on a live environment.

## 2. Install the patch (the ZIP contains additions only)

Copy the following files into the **existing project root**, retaining their paths:

```text
database/seeders/HamqadamQaGuard.php
database/seeders/HamqadamQaDemoSeeder.php
database/seeders/HamqadamQaConfigurationSeeder.php
database/seeders/HamqadamQaActivitySeeder.php
database/seeders/HamqadamQaPaymentsSeeder.php
database/seeders/HamqadamQaMatchSeeder.php
database/seeders/HamqadamQaContentSeeder.php
tests/Feature/Phase10/QaSeederConfigurationTest.php
tests/Feature/Phase10/QaSponsoredVisibilityTest.php
tests/Feature/Phase10/QaRefundRequestTest.php
tests/Feature/Phase10/QaRouteContractsTest.php
docs/PHASE10_QA_TESTING_AND_UI_GUIDE.md
```

This package does **not** replace any existing controllers, views, migrations, routing files or `DatabaseSeeder.php`.

In the isolated staging project directory:

```bash
php artisan about
php artisan migrate:status
# after staging DB backup and review of all pending migration SQL:
php artisan migrate --force
php artisan optimize:clear
php artisan route:list --name=admin.client.scope
php artisan route:list --name=member.payment_refunds
php artisan route:list --name=client.scope
php artisan view:cache
```

**Important:** The existing release notes additionally identify `2026_10_19_000024_create_payment_refund_attempts.php`, `000025_add_help_chat_agent_assignment.php`, `000026_create_verified_match_outcome_reviews.php`, `000027_create_city_geo_coordinates.php`, and `000028_add_support_language_preferences.php` as files that must be reviewed/applied. `migrate:status` tells you exactly which are pending; do not blindly re-run files already applied. For production, create a backup and stage changes first, then run only approved migrations.

Populate **only your isolated QA database**:

```bash
php artisan db:seed --class=DemoMatrimonialSeeder
php artisan db:seed --class=HamqadamQaDemoSeeder
# run again to ensure fixture rows do not duplicate:
php artisan db:seed --class=HamqadamQaDemoSeeder
```

The four existing synthetic members are `demo.user@hamqadam.test`, `ayesha.khan@hamqadam.test`, `fatima.ahmed@hamqadam.test`, and `sana.malik@hamqadam.test`. Their sample profile and match data come from the pre-existing `DemoMatrimonialSeeder`. If this original seeder cannot be run cleanly on your staging schema, fix *that* migration/reference-data prerequisite before loading dependent QA fixtures.

Run the isolated automated suite:

```bash
php artisan test tests/Feature/Phase10 --stop-on-failure
php artisan test tests/Feature/Completion
php artisan test tests/Feature/Phase5
php artisan test tests/Feature/Phase6A
php artisan test tests/Feature/Phase6B
php artisan test tests/Feature/Phase6C
php artisan test tests/Feature/Phase7
php artisan test tests/Feature/Phase8
php artisan test tests/Feature/Phase9
php artisan test tests/Feature/RequirementClosure
php artisan test
php artisan hamqadam:readiness
php artisan payments:audit
```

The **24 Phase10 tests in 9 PHPUnit classes** cover registration/sponsor/refund route access and views, idempotent and capped reward issuance, verified milestone triggers, reciprocal match nomination, notification engagement evidence, refund ownership and real-provider confirmation safeguards, and seeder safety. They explicitly switch to **SQLite `:memory:`** and construct minimal isolated schema. They never use your staging data. PHP must have `pdo_sqlite` installed. If existing Laravel tests require additional environment setup, configure `.env.testing` and database access separately.

## 3. What demo fixtures are provided?

| Demo dataset | Expected result after successful seeding | Admin/member surface |
|---|---|---|
| Gamification | Three `reward_rules`, keys `profile_completed` (15), `first_interest` (5), `got_match` (30); existing configured rules remain unchanged | `/admin/client-scope` → Gamification |
| Sponsored listings | Home, Discovery and Match entries; label `QA DEMO`; active 30-day windows | `/admin/client-scope` → Sponsored Listings; `/member/client-scope` as eligible member |
| Ad gating | `show_ads_to_paid=0` only if setting absent | `/admin/client-scope` → Show sponsored listings to paid members |
| Coupons | `QADEMO10` at 10% off, limited, if coupon table exists | `/admin/payment-coupons` |
| Payment & refund | One synthetic paid invoice, one due invoice, one `requested` refund | `/admin/payment-refunds`; member refund history route |
| Activity analytics | Seven synthetic events per available demo member, provenance `qa_demo_fixture` | `/admin/client-scope` → feature adoption, observed funnels |
| NPS examples | 3 synthetic records in **`qa_demo_nps`**, intentionally not in real NPS dashboard | Query staging database; submit authentic NPS through member UI for real dashboard |
| Notification examples | Synthetic `email` delivery log with `qa_demo_notification` (one simulated sent, one simulated failure) | `/admin/client-scope` → Notification delivery status; `/member/client-scope/notifications` |
| Support | One open and one already-rated closed ticket if optional table exists | `/member/client-scope/support`; `/admin/client-scope` → support rating summary |
| Match outcomes | Two fake reciprocal `got_match` reports, one **pending** staff-review candidate, plus demo ↔ Fatima reciprocal profile match with **no confirmations** so the UI can be exercised | `/member/client-scope`; `/admin/client-scope/match-outcomes` |
| Geographic heatmap | Approximate Lahore, Islamabad and Karachi **city center** coordinates if these Pakistan cities exist; preserves entered coordinates | `/admin/client-scope` → Member geographic concentration |
| Blog | Two unpublished/draft `QA DEMO` blog posts and draft category | `/admin/blog`, `/admin/blog-category`; public `/blog` only after *deliberate* publication |
| Success story | One unpublished synthetic story | Admin Happy Stories section, not public until explicitly approved |
| Referral | Existing `/member/referrals` UI; **no fabricated earned referrals or financial rewards** | Member referrals and Admin referral management |
| Payment refunds actually paid | None. Provider refund attempts are never fabricated | Provider sandbox approval test separately |

**Hands-on Got Match demonstration:** Login as `demo.user@hamqadam.test` on QA and select Fatima in the suggested matches; click **Got Match** once. Login separately as `fatima.ahmed@hamqadam.test` and confirm the demo user's reciprocal match. Refresh Admin → Client Scope → Independent Match Outcome Reviews. The pair should produce exactly one **pending** review candidate. This can issue configured virtual coins but cannot establish a verified marriage. Do not use the Ayesha pair for initial-click testing: it is seeded with both confirmations already recorded.

Re-running seeders uses stable demo identities and `firstOrCreate` so it does not duplicate its own rows. It **does not reset** edited demo records, apart from the separately invoked original `DemoMatrimonialSeeder`. To get fresh values, work in a disposable QA database restored from backup.

## 4. Direct UI map: where to go and how to test

The paths shown below are relative to your deployed website domain. Use the **real frontend/admin login** and required permissions before opening them. Not every route appears in every sidebar theme; a route can be opened directly if properly authorized.

| Feature | URL / screen | How to test in the UI | Expected outcome |
|---|---|---|---|
| Admin completion dashboard | `/admin/client-scope` | Login as admin, scroll through dashboard charts | Dashboard loads without Blade error; totals are labeled by evidence source |
| Member advanced search | `/admin/client-scope/profiles` | Search `QA DEMO`, `demo.user@hamqadam.test` or full name; change filters | Matching rows and accurate pagination; non-admin denied |
| Sponsored listings | `/admin/client-scope` → Sponsored Listings & Ad Gating | Find the three `QA DEMO` cards; create another, disable it | Active/disabled state and placement persist |
| Sponsor audience | `/member/client-scope` | Login as free demo member; scroll until Sponsored section enters viewport | Eligible active sponsor appears; impression increases at most once per user/hour; clicking increases clicks |
| Sponsored visibility by subscription | `/admin/client-scope` checkbox | Leave paid-member ads disabled, compare free and genuinely activated paid test plan | Free sees eligible sponsors; paid does not unless permitted and not `ad_free` |
| Gamification & Reward Rules | `/admin/client-scope` → Gamification | Observe three keys, change reward value, save; then trigger real authorized milestone in QA | Rule saves; reward only on verified server-side milestone, respects max/user, never repeats for same reference |
| Match confirmation | `/member/client-scope` | On an unconfirmed test pair, click Got Match from both accounts | First shows awaiting; second shows both confirmed; no verified marriage label |
| Outcome review | `/admin/client-scope/match-outcomes` | Find pending demo pair, inspect audit and reject/return as appropriate in QA | Only eligible mutual pair can be reviewed; record preserves who reviewed and status transitions |
| Reward history | Admin rule area / coin balance and database `reward_transactions` | Repeat the same match milestone | No duplicate reward issuance or duplicate ledger for same reference |
| NPS survey | `/member/client-scope/satisfaction` | Submit score 9–10 using demo member | Visible success; same user prevented from submitting again within 30 days; Admin shows recorded NPS |
| Notification preferences | `/member/client-scope/notifications` | Adjust timezone, quiet hours, digest frequency, enable/disable email or push | Values retained after refresh; invalid one-sided quiet hours rejected |
| Support ticket language / rating | `/member/client-scope/support` | Create ticket with Urdu; reply; close via authorized agent; rate closed ticket | Language retained, status/ETA visible, rating allowed once after closure |
| Profile share | `/member/client-scope` → Share my profile | Copy URL; open via another logged-in allowed account | Permitted member profile shown; blocked/hidden rules respected |
| Promo code | `/admin/payment-coupons` | Locate `QADEMO10`, test date/amount and usage eligibility via checkout/sandbox | Correct 10% only when active and eligible; expired/exhausted rejected |
| Member refunds | `php artisan route:list --name=member.payment_refunds.index` → open returned URI | Review synthetic Paid and Due invoices; one requested refund appears | Paid invoice only is eligible; Due invoice not eligible; duplicate request is idempotent |
| Admin refunds | `/admin/payment-refunds` | Filter requested/reviewing/rejected; mark QA synthetic request `reviewing` or `rejected` | Tracks internal status, **never claims funds returned**. Do not click provider refund execution for demo rows |
| Payment audit | `/admin/payment-audit` | Inspect missing references, state transitions and reconciliation checks | Appropriate pending/paid/failed statuses; mismatch alerts, no fake payouts |
| Geographic report | `/admin/client-scope` → geographic section | Check city bubbles and mapped/unmapped counts; add coordinates for a QA city | Aggregate counts only; no precise member locations |
| Feature adoption / funnel | `/admin/client-scope` | Look at recorded activity and 90-day cohort with QA demo members | Counts reflect recorded events, labels disclose observed vs authoritative source |
| Blog management | `/admin/blog` and `/admin/blog-category` | Edit an unpublished QA sample, preview before deliberate publishing | Draft is editable, category exists; public `/blog` shows only published articles |
| Happy stories | Admin Happy Stories | Edit synthetic unapproved story; test approve/unapprove using QA image/content | Only approved stories shown on public happy stories section |
| Member referral | `/member/referrals` | Copy referral link, register another QA-only member with referral, verify rules | Referral ledger and coin reward subject to eligible referral and anti-duplicate guards |
| Admin referral | `/admin/referal/users`; `/admin/set-referral-commission` | Inspect unique attributed referrals, controls | Changes only for test rules; no double referral rewards |
| Translations | Admin → Languages → chosen language → Bulk Import/Export | Export Urdu CSV; add a valid key, import and view | Known keys accepted; unsafe HTML/formulas rejected; RTL displays correctly |
| Member accessibility | Logged-in website → accessibility tools | Toggle lite mode and color-blind option; keyboard navigate | Settings apply without hiding essential labels; keyboard focus is visible |
| Profile boost | Logged-in member UI / `member.profile_boost.activate` | Try free vs paid feature-enabled, attempt repeated boost | Free/expired denied; eligible paid permitted under limits |
| Logged-in compatibility score | Member suggested matches, profiles and match details | Compare two members' actual preferences and score mapping | Score, level, reasons and **Why this match?** use approved format and actual user pair, not sample placeholder text |
| Public pages | `/`, `/blog`, packages, Happy Stories, Help, etc. | Review at 360px, 768px, 1280px; images/logo/content and footer | No missing page, broken image, horizontal overflow, or dead navigation |

**Important:** The current member Client Scope portal lists sponsored placements without a placement filter. To verify actual placement-specific content, also inspect the corresponding API/display integration; do not assume the Home sponsor only renders on the home page merely because the record's `placement` is `home`.

## 5. Manual acceptance cases: original 30 client requirements

Record each case as **Pass / Fail / Blocked / Not run** with browser screenshot, test-user ID, timestamp, and related route. Negative tests are just as important as happy paths.

| ID | Original requirement | Action / negative case | Passing condition |
|---|---|---|---|
| HQ-01 | Promo codes | Apply valid `QADEMO10`, expired code, wrong minimum and exhausted limit | Only eligible discount applied once; correct totals and admin usage |
| HQ-02 | Payment failure/pending | Simulate sandbox failure, webhook duplication and out-of-order events | Failed/pending shown correctly; paid transaction never demoted; no double entitlement |
| HQ-03 | Happy stories | Admin create draft/approve/unapprove/delete QA story | Only approved item publicly visible; deletion hides story |
| HQ-04 | Advanced admin search | Search by name/ID/email/phone; apply filters over more than 25 records | Correct results and pagination; cross-user privacy intact |
| HQ-05 | Email OTP | Register with valid/expired/reused OTP; resend; verify DNS/email deliverability | Current address and OTP bound; wrong/replayed OTP denied |
| HQ-06 | Advanced notifications | Toggle quiet hours/digest; read/click; inspect logs | State accurate and restrictions enforced; receipts not fabricated |
| HQ-07 | Closed ticket rating | Close ticket, rate once, attempt second rating | Allowed only for ticket owner, only once after closure |
| HQ-08 | Live support chat | Member and admin exchange simultaneous messages | Delivery/history/status correct; another member cannot open thread |
| HQ-09 | Ticket status/ETA | Open/reply/close ticket, inspect expected response time | Status, ETA and resolved time updated correctly |
| HQ-10 | Support satisfaction | Rate and inspect admin aggregate | Exactly one rating per closed ticket; correct average |
| HQ-11 | Multilingual support | Create EN/UR/AR/HI tickets, check agent queue | Selected language stored/displayed and routing follows approved policy |
| HQ-12 | User profile links | Share valid, blocked and private profile | Permitted links work; restricted profiles are not leaked |
| HQ-13 | Referrals | Register unique eligible referred member, repeat/refuse invalid self-referral | One attributed referral, correct capped coin reward |
| HQ-14 | Premium categorization | Test free, paid, expired plan feature flags | Only explicitly included active paid privileges work |
| HQ-15 | Super Like / Priority Interest | Try action with and without entitlement or coins | Unauthorized denied; eligible action charges correct coins once |
| HQ-16 | Sponsored listings | Create listing, dates, placements, clicks, impressions | Only live/eligible listing visible; counts reflect actual interactions |
| HQ-17 | Ad gating | Compare free, paid, paid with `ad_free` | Same policy on web/API; paid `ad_free` never sees ads |
| HQ-18 | Gamification | Trigger real first interest, profile completion, mutual Got Match twice | Rule caps, atomic coin increment and idempotent ledger |
| HQ-19 | Funnel analytics | Register, discover, interest, match, chat in sequence | Same-user event linking, transparent denominators/source |
| HQ-20 | Retention cohorts | Use activity dates from several signup months | D1/D7/D30 only shown if genuinely implemented; observed 30-day distinctly labeled |
| HQ-21 | Feature adoption | Use search/profile/chat/support; inspect admin tally | Recorded and approved event keys only; no spoofed server milestones |
| HQ-22 | Geographic heatmap | Save city coordinates and compare address counts | Aggregated city bubbles, no private GPS/location leaks |
| HQ-23 | Success-story tracking | Link genuine approved story to verified candidate if policy permits | Outcome/story association correct; unlinked stories not miscounted |
| HQ-24 | NPS | Submit 10,7,4 with distinct QA users (manual, context `nps`) | Computed promoter/passive/detractor rates and monthly trend correct |
| HQ-25 | User behavior | Search/profile/chat/interest; inspect audit | Event sources/traces accurate without personal message text |
| HQ-26 | Match success reports | Compare reported vs mutual vs staff reviewed | Categories clearly separate; no unsupported AI accuracy claim |
| HQ-27 | Got Match | Both member confirmations, self-confirm, arbitrary non-match | Only real recorded pair allowed; no duplicate reward, admin review pending |
| HQ-28 | Blogs/articles | Admin create, categorize, publish, unpublish | Website list/detail and app API agree on published-only content |
| HQ-29 | Localization/accessibility | UR/AR RTL, HI, keyboard screen reader, zoom to 200% | No broken layouts/focus; formal WCAG audit separately required |
| HQ-30 | Search/discovery filters | Physical, caste, recent, new, mutual, hide-from-user | Exact API/Web filter behavior; privacy enforced in all searches |

## 6. Additional acceptance cases: “Hamqadam updates.docx”

| ID | Client update | How to test | Passing result |
|---|---|---|---|
| HU-01 | Compatibility “Why this match?” | Compare 85%, 30%, real user-pair summaries and reasons on profile/match UI | Approved text structure, percentage, level, AI summary and positive/warning bullets; dynamic, not hardcoded |
| HU-02 | Pending AI Bio | On model delivery, supply endpoint and reference input; generate/regenerate | Source owner confirms actual model response saved/displayed; rate limits and failures handled |
| HU-03 | Pending chatbot/proposal | Configure isolated model endpoint/subdomain and API keys | HTTPS/auth/timeout/retry/logging; response available on both authorized surfaces |
| HU-04 | Major payment gateways | Connect sandbox credentials after bank/merchant approval | Test approved/failed/pending, webhook, refund and duplicate replay with provider proof |
| HU-05 | 30-feature demonstration | Admin/member video walkthrough for HQ-01..30 | Each feature identified, used, recorded with pass/defect evidence |
| HU-06 | Logged-in web design/mobile parity | Compare approved screens for Home/profile/shortlist/score/packages on 3 widths | No missing action or inconsistent layouts; parity proven with actual mobile screenshots |
| HU-07 | Public website features | Guest Guardian/Expert, blog and package sections against approved screens | Correct content, links, responsive imagery and navigation |
| HU-08 | Duplicate email / change-email OTP | Enter existing email; select another; try old OTP and new OTP | Duplicate detected early; new email requires freshly bound OTP before proceeding; back-end enforces uniqueness |
| HU-09 | Logged-in responsive web | Check member profile edit, suggestions, filters and score at 360/768/1280px | No overlapping card/buttons, working interactions, proper typography |
| HU-10 | Admin website CMS & design | Admin changes approved hero/content/theme settings then opens public site | Changes reflected without code editing; unauthorized role blocked; supported design limits documented |

## 7. Further targeted negative/security tests

- Login as demo member A and request member B's ticket by numeric ID. Expect **403/404**, not B's data. Attempt a reply/second rating through UI and mobile API.
- Repeat `Got Match` with own user ID, a non-member, blocked member, or a member without a `profile_matches` row. Expect rejection.
- Submit `provider_refund_confirmed` for QA demo invoice without genuine Stripe provider proof. Expect rejection. Never provide fake `re_` refund IDs as evidence.
- Replay sandbox webhooks with identical provider IDs and inconsistent order. Verify payment state remains monotonic and no double charge/refund/coins.
- Change the registration email after OTP issue. Ensure the old OTP cannot verify the replacement email; inspect the DB/session transaction state.
- Try sponsor click for disabled/expired listing or wrong plan. Expect 404 and no increment.
- Verify hidden or blocked profiles are not revealed through direct link or API filter bypass.
- Export/import translation CSV with spreadsheet formula (`=SUM(1,1)`) or script HTML. Expect sanitization/validation error, no stored executable content.
- Test roles: ordinary member requests `/admin/client-scope`, `/admin/payment-refunds`, `/admin/blog`, admin translation batch endpoints; expect permission denial.

## 8. Test evidence and issue reporting template

For each case save:

```text
Test case: HQ-__/HU-__
Environment + commit SHA:
Staging URL or route name:
Browser/mobile OS + viewport:
Test account IDs (not passwords):
Steps and input:
Expected:
Actual:
Status: PASS / FAIL / BLOCKED / NOT RUN
Screenshot or video filename:
Relevant laravel.log lines / HTTP response:
DB record ID (safe/sanitized):
Defect / follow-up owner:
```

Do **not** store passwords, OTPs, private chat messages, CNIC details, gateway secrets or personal user information in screenshots/report exports.

## 9. Release exit criteria and known gaps

Before client sign-off, require: full Laravel test pass, no broken Blade templates, migrations applied on staging MySQL, controlled database backup/rollback plan, role/ownership checks, public + member mobile-size walkthrough, admin feature demonstrations, payment provider sandbox receipts, real email delivery validation, and a truthful inventory of Flutter parity.

Known external dependencies: HERA/model developer's actual AI deployment and technical docs, payment provider keys and approval, SMTP/domain SPF-DKIM-DMARC, FCM/Pusher delivery, certified translations/content/assets, formal accessibility audit, staging/prod server and mobile-app access. A passing PHP syntax check or GitHub merge is **not** proof of this sign-off.

## 10. Running the new GitHub Actions checks

The patch includes `.github/workflows/phase10-qa-validation.yml`. When the changed files are pushed to a branch and a PR is opened, GitHub Actions will install Composer dependencies, lint the new PHP files, compile Blade views, and run **24 isolated tests**. A passing CI run is useful regression evidence, **not** certification of production provider integrations or final client UI acceptance. Do not merge until this workflow and the existing Phase 7 validation pass and the staging MySQL/UI checks are complete.

### Troubleshooting

- `could not find driver (Connection: sqlite)`: install/enable `pdo_sqlite` for PHP CLI; inspect `php -m`.
- `Class ...Seeder not found`: run `composer dump-autoload`, verify `database/seeders` spelling/casing, then retry.
- `SQLSTATE table not found`: inspect `php artisan migrate:status` on staging; apply outstanding migrations after review and backup, not directly on production.
- HTTP 403: sign in with the intended admin/member account and inspect middleware/permission assignment; never bypass it for testing.
- Browser 500: check `storage/logs/laravel.log`, run `php artisan optimize:clear` and `php artisan view:cache` in staging, and capture the exact route and error.
- If zero demos appear in the live UI, ensure the browser is connected to the **QA** database, not production. The QA workbook distinguishes seeded data, recorded database events, and genuine provider/UI validation.
