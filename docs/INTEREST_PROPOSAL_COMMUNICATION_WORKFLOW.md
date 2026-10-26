# Hamqadam interest → proposal → communication → family workflow

This guide accompanies the full Hamqadam Interest, Proposal & Communication Developer Specification.

## Rollout

1. Back up the database. Historical proposal records share the interests table.
2. Run php artisan migrate to install workflow, meeting reminders, and legacy classification migrations.
3. Run php artisan db:seed --class=ConnectionMessageTemplateSeeder. The 30 editable English message templates are created once; existing admin edits are retained.
4. Ensure scheduled tasks run: php artisan schedule:run, or run the Laravel scheduler continuously via cron.
5. Configure the existing mail and FCM settings; optional AI drafts require OPENAI_API_KEY and optionally HAMQADAM_PROPOSAL_AI_MODEL.
6. Run php artisan test --filter=MemberConnectionWorkflowSpecificationTest, then the existing message, call, interest and proposal regression suites.

## Member web routes

- GET /matched-profiles/{member}/connection
- POST /matched-profiles/{member}/connection/interest
- POST /connection/interests/{interest}/respond
- POST /matched-profiles/{member}/connection/proposal/generate (generates draft; never sends)
- POST /matched-profiles/{member}/connection/proposal
- POST /connection/proposals/{proposal}/respond
- POST /connection/proposals/{proposal}/meetings
- POST /connection/meetings/{meeting}/notes
- POST /connection/proposals/{proposal}/relationship
- POST /connection/preferences

Admin templates: /admin/workflow-templates. Mobile API: /api/v1/connection-workflow.

## Workflow and safety guarantees

- Interests require a selected active template and user approval. Proposals require an accepted interest and final text approval.
- Accepted interest unlocks chat; calls additionally require both members to allow the relevant call type.
- Blocked pairs cannot communicate. Proposals cannot unlock contact when interest has not been accepted.
- The AI endpoint only drafts. A service error leaves static templates available.
- Staff can create, edit, deactivate, reorder and localize message templates.
- Accepted marriage proposals allow family meeting planning, private notes and moderated relationship milestone submissions.
- One-time scheduled reminders use recorded timestamp fields, plus event delivery deduplication.

## Mandatory pre-merge checks

- Ensure legacy web and mobile actions do not bypass preview and confirmation. Some old endpoints remain available for compatibility and require a full regression audit.
- Verify accepted interest permissions with accounts in both directions and after decline/block/report.
- Verify migration backfill of old proposal rows and ensure only genuine interests grant permission.
- Exercise insufficient-coin, double-submit, race condition, malformed message, and guardian permission cases.
- Test scheduler timing, preference opt-outs, duplicate avoidance, push and SMTP configurations.
- Verify family meetings and milestone review with real users; inspect browser layouts at mobile/tablet/desktop sizes.
- Verify AI-generated English and Urdu drafts, editing and explicit reapproval on regeneration.

## Deployment note

The GitHub integration can commit and open a draft PR, but cannot run your Laravel app/database or browser-based QA. This branch is not production verified.
