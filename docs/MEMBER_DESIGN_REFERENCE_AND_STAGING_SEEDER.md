# Hamqadam Member Design Suite — Review Notes

## Reference package
20 JPEG screen references from the user-supplied WhatsApp ZIP (2026-10-22). Do **not** embed screenshots as the interface; all components must remain dynamic.

## Implemented in this PR
- Main member dashboard rebuild using live `users`, `members`, match, interest, shortlist and notification data.
- Reference-aligned color palette: pink #ED2977, burgundy #A91550, pale pink #FFF1F6, white cards, responsive KPI and match grids.
- Shared styling of existing member routes/forms, profile editing, referrals, wallet, conversations, client scope pages and account controls. Existing transaction/navigation behavior retained.
- Member match cards link to the existing `member_profile` flow, which owns profile entitlement/charging behavior.
- Existing sticky member sidebar remains independent of main document scroll; no new fixed main scrollbar.
- Opt-in guard-protected QA demo fixture seeder.

## How to run the demo SAFELY
**Never run on production.** Back up and configure a separate *staging* installation and DB; APP_ENV must be `staging` or `local` (and not share the live database).
```sh
php artisan db:seed --class=HamqadamMemberDesignDemoSeeder
php artisan view:clear
php artisan optimize:clear
```
Test member email: `demo.member.design@hamqadam.test`. The seeder generates a **random unknown password**. Set a private password yourself only on the isolated staging account with Laravel Tinker or use an existing local test login mechanism. Other dummy email identities are `demo.ayesha.design@hamqadam.test`, `demo.fatima.design@hamqadam.test`, `demo.sana.design@hamqadam.test`, `demo.noor.design@hamqadam.test`.
The seeder does not create real payments, verified marriage certificates, actual provider verifications, subscriptions, or connected social messages. It is designed to be repeatable without changing existing records; repeated runs do not reset passwords. It uses schema-aware fields for compatibility across current migrations.
Notice: `approved` is set **only for isolated QA users** to allow manual navigation and should never be used as real identity verification evidence.

## UI Review
Check dashboard at 1440px, 1280px, 768px and 375px. Check existing member profile, wallet, messages, interests, shortlist, guardian mode, referrals, stories, notifications and support for consistent design while preserving their forms. Inspect mobile viewport and independent sticky-sidebar scroll. Verify match profile-click charging using a QA account with test entitlements.

## Known gaps / further UI validation
The 20 supplied images cover distinct product screens. The complete pixel-by-pixel parity of all 20 separate screens is **not claimed** by this patch. The dashboard is substantially rebuilt; other panels inherit the shared design styling but preserve their original Blade structures for business continuity. Exact photograph assets shown in the reference JPEGs are not available separately, so demo cards use privacy-respecting site avatar placeholders unless genuine permitted QA photos are uploaded. Full browser visual diff, seed run against your staging migration and multi-device QA remain required before merge.
