# Member interface — design reference implementation checklist

Input: the 20 screenshot references previously supplied plus additional screens (member detail, meetings, wallet, interests, chat, match insights, packages, shortlist and settings). The visuals are design targets, not assets to paste over working UI.

## Implemented in this draft PR
- Independent viewport-height member navigation, bottom-anchored contextual promotion and Logout.
- Original editable vector couple illustration, route-based promo copy and actions.
- Profile: 9-step UI shell with retained Laravel edit forms, right preview and profile tips.
- Dashboard: 5 metric tiles; up to 6 suggested compatible profiles, retaining profile-view route.
- Matched and Shortlisted: denser responsive profile grids with right-side actual data summaries and links to existing discovery.
- Verification: process map and guidance rail surrounding the configured verification form.
- Scheduling: step-styled form using existing accepted-proposal validation and create route.
- AI tools: reference-like 3-column navigation/composer/actions using real AI service results.
- Blogs: feature article, article grid, search, popular reads.
- Happy Stories: approved story grid, encoded links and actual published-count metric.
- Packages: dynamic plan entitlements and comparison, using existing checkout route.
- Settings: account summary, profile preview and links to existing preference/security forms.
- Messaging: member companion panel tied to selected conversation; the existing Ajax list/voice/chat handling is retained.

## Functionality and data integrity
- DO NOT invent verified-member status, profile matches, service availability or successful payments.
- Public profile viewing and interest actions must continue to call the original routes to preserve charging, permission and consent logic.
- Meeting submissions remain restricted to accepted proposals and approved guardian mode when requested.
- Buttons for external billing or AI use existing named Laravel routes; no new payment gateway is introduced.
- Distinct editable profile cards still use their individual Save/Update actions; section navigation is **not an atomic one-click save**.
- Images shown within member cards are original member uploads or privacy-respecting placeholders; reference sample portraits have not been used as real account data.
- Client-provided screenshot examples may imply features that are not installed (e.g., newsletter delivery, general-purpose AI chatbot, predictive matches, coin checkout price tiers). This PR does not fabricate these services.

## Not complete or not verified
- Pixel-identical layout/typography/color at all desktop widths and phones is **not verified** by a browser screenshot diff.
- Full public member profile detail page remains based on the original legacy view; it requires a separate preservation-focused redesign.
- Some role-sensitive menus and advanced filters have not been changed beyond their look; existing backend filter behavior remains source of truth.
- Extended meeting time-slot inventory or integrated video-service scheduling is not claimed.
- AI assistant is a set of real AI tools; not a general conversational chat bot.
- The existing repository's Phase10 CI has been blocked by the unrelated 404 test expecting 404 and receiving 302. Regression CI after new changes must be checked before merge.

## Manual review routes
- `/dashboard`
- Profile: existing `profile_settings` route
- `/member/reference/meetings`
- `/member/reference/packages`
- `/member/reference/assistant`
- `/member/reference/blogs`
- `/member/reference/stories`
- `/member/reference/settings`
- Remaining pages: use authenticated member sidebar, which retains functional destinations.

## QA checklist
1. Screenshot compare each page against its corresponding image at 1280×853, desktop 1440 and mobile 375.
2. Verify sidebar promo varies by route and Logout stays pinned on small vertical screens.
3. Verify profile tabs show all original form sections and all Update actions persist unchanged.
4. Verify profile view charging and photo visibility/consent rules.
5. Verify shortlist add/remove and interest accept/reject remain protected.
6. Verify messaging unread, audio, call and report operations.
7. Verify scheduling rejects other people's proposals and refused guardian modes.
8. Verify package checkout uses configured payment gateway only.
9. Verify blog/story cards use approved/published content and privacy-respecting media.
10. Compare browser console and Laravel logs before considering merge.

Draft PR only. Do not merge automatically.
