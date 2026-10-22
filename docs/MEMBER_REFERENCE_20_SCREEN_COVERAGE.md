# Hamqadam member reference design — 20-screen progress and functional contract

Source: user-provided `WhatsApp Unknown 2026-10-22 at 2.24.47 PM.zip`, 20 screenshots (1280×853).
No screenshot is pasted into the application as a replacement for functional controls.

## Screen-by-screen mapping

| Reference | Subject | Existing location | Current progress |
|---|---|---|---|
| 01 | Member Dashboard | `frontend/member/dashboard.blade.php` | Previously rebuilt; visual acceptance pending |
| 02 | Received proposals | `frontend/member/interest_requests.blade.php` | Added reference top section; original filtering and accept/reject retained |
| 03 | Membership packages | Public `packages` route and purchase views | Needs dedicated pixel-level rebuild |
| 04 | Shortlist | `frontend/member/my_shortlists.blade.php` | Previously rebuilt as profile cards |
| 05 | Member profile | `frontend/member/profile/index.blade.php` | Previous PR added tabs and legacy forms; review pending |
| 06 | Wallet | `frontend/member/wallet/index.blade.php` | New wallet composition, existing purchases/history retained |
| 07 | Messages | `frontend/member/messages/index.blade.php` | New chat shell; existing AJAX messaging retained |
| 08 | Meeting scheduling | No confidently confirmed member scheduling UI in existing routes | Blocked pending confirmed route/backend contract — do not create fake scheduler |
| 09 | Sent interests | `frontend/member/my_interests.blade.php` | Added reference top section; original list and actions retained |
| 10 | Matched profiles | `frontend/member/matched_profiles.blade.php` | Previously rebuilt as score cards |
| 11 | Member verification | `frontend/member/member_verifiction_form.blade.php` | New verification summary; existing form inputs/submit retained |
| 12 | Profile/settings | `frontend/member/profile/index.blade.php` | Existing tabbed grouping; full ref parity pending |
| 13 | Referral program | `frontend/member/referrals/index.blade.php` | Reference summary and sharing theme; live link and reward history retained |
| 14 | Happy Stories | `frontend/member/happy_story/index.blade.php` | Existing create/view workflow; dedicated reference rebuild pending |
| 15 | AI Assistant | `frontend/member/v1_dashboard.blade.php` and available APIs | Dashboard styled; reference chat assistant requires real controller/route verification |
| 16 | Blogs | Public blog routes | Dedicated member blog-reading design pending |
| 17 | Notifications | `frontend/member/notifications.blade.php` | New summary and existing notification deep links retained |
| 18 | Profile privacy/settings | Existing profile/settings routes | Shared member design, custom controls pending |
| 19 | Other member feature layout | Original member route variants | Shared stylesheet only, reference-specific rebuild pending |
| 20 | Member secondary/detail view | Existing member routes | Reference-specific rebuild pending |

## Data/integrity rules

- Profile views must route through the existing `member_profile` controller, preserving entitlements and charges.
- Existing interest acceptance/rejection, chat submission, payout, coins, referral and verification actions are unchanged.
- No payment amount, plan tier, verified badge, chat, meeting, or marriage outcome is hard-coded as real.
- Unimplemented controls must not be made to look clickable without a real route.
- Review the existing `HamqadamMemberDesignDemoSeeder` on an isolated QA database only.

## QA requirements

1. Compare each screen side by side against the source screenshot at 1280×853 and mobile widths.
2. Submit forms and verify DB changes for interests, profile editing, verification and support.
3. Verify coins/profile views charged once and only once using test member accounts.
4. Check chat AJAX, unread counters, notification deep links and back-navigation.
5. Inspect browser console, Laravel logs and SQL errors after refreshing each page.
6. Existing Phase10 CI currently fails at `QaBrandedErrorPagesTest`: test expects 404 but receives 302; this is not considered a pass and is not automatically attributed to these UI changes.

This PR is a reviewable partial implementation, not a claim that the complete 20-screen clone has passed visual UAT.
