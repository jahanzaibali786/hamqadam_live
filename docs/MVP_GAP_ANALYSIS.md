# HamQadam Practical MVP Gap Analysis

This report is based on the supplied 25-module milestone document and the current Active Matrimonial CMS codebase. The implementation strategy is practical MVP first: keep Laravel monolith/admin, strengthen mobile APIs, reuse existing CMS tables, and skip expensive enterprise items until the client explicitly funds them.

## Scope Decision

### Already Implemented Or Strongly Covered

- Authentication: email login, phone OTP login, Google login endpoint, password reset, Sanctum tokens, device sessions, logout all devices, account deactivation.
- Profile: full onboarding, profile update API, privacy/visibility, profile completion percentage, partner preferences, registration Basic package assignment.
- Search: advanced profile search, saved searches, search history, compatibility filter, hidden profile-from-user control.
- Matching: rule-based compatibility scoring, admin-managed weights/settings, recommendations, daily/recommended aliases, profile compatibility endpoint, feedback loop.
- Proposals: send, accept, reject, withdraw/cancel, ignore, notes, timeline, expiry, package coin/interest limit.
- Favorites: add/remove plus mobile list/check endpoints.
- Chat: existing chat API, messages, typing, report/block, delete-for-me.
- Payments/packages: admin-managed packages, Stripe, EasyPaisa, JazzCash placeholder/manual checkout, coupons, invoices/history, webhooks.
- Verification/trust: document/selfie verification queue, admin approve/reject APIs, verified profile basis.
- Notifications: in-app notifications, preferences, push token registration, read/unread APIs.
- Safety: report, block, mute, restrict, moderation queue basics.
- Family/guardian: guardian links, approvals, notes, dashboard, Wali mode, family conversations.
- Content/CMS: existing blog/success stories plus v1 content/community APIs.
- API documentation: curated app-only Markdown, OpenAPI, and Postman files.

### Partially Implemented, Needs Refinement

- Admin dashboard: overview exists, but new content/community/relationship status admin screens still need full CRUD/moderation UI.
- Profile media moderation: legacy gallery/photo handling exists, but v1 app-specific photo approval APIs need refinement.
- Phone OTP delivery: OTP logic exists; production SMS provider credentials/integration must be configured.
- Google login: endpoint exists; production token verification/client configuration should be validated with mobile credentials.
- Payment verification: EasyPaisa/JazzCash are cleanly structured but require real merchant credentials and callback verification.
- Chat safety: basic report/block exists; configurable scam keyword masking/detection should be refined.
- Urdu/localization: translation infrastructure exists; full Urdu copy is not complete.
- Reporting/analytics: dashboard counts exist; deeper funnels/revenue reports are basic.

### Missing Practical MVP Items

- Admin CRUD/moderation screens for expert questions, forums, webinars, marriage tips, regional updates, relationship status updates, and proposal meetings.
- Manual payment approval screen for JazzCash/EasyPaisa/bank-style payments.
- Profile/photo approval workflow exposed cleanly in v1 admin panel.
- Support ticket app APIs and admin UI if the client wants customer support inside the app.
- Production FCM notification sending worker using real Firebase credentials.
- Final website UI polish pass across listing/detail/package/payment pages.

### Not Required For MVP

- Blockchain APIs.
- Microservices, API gateway, multi-region deployment, auto-scaling infrastructure.
- Deepfake detection, full liveness detection, photo attractiveness scoring, voice-tone personality scoring.
- Advanced WebRTC/video calling infrastructure.
- Apple Pay, Google Pay, PayPal, Paymob, crypto, complex multi-currency billing.
- Datadog/Vault/ISO 27001/bug bounty infrastructure.
- Enterprise A/B testing, cohort analytics, gamification leaderboards.

## Current Priority Roadmap

1. Stabilize and test v1 mobile API journeys end-to-end: auth, profile, search, match, proposal, favorites, chat, payment.
2. Complete admin moderation/configuration screens for the new MVP modules.
3. Configure real Pakistan payment credentials: EasyPaisa and JazzCash first, Stripe/card if available.
4. Add production FCM/email/SMS credentials and queue delivery jobs.
5. Refine website pages without rebuilding the whole CMS.
6. Run QA checklist and fix route/view/runtime issues before launch.

