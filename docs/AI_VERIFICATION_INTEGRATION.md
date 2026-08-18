# AI Identity Verification Integration

Integrates the Hamqadam AI Identity Verification service into this Laravel app.

- Service: `https://ai-modals.hamqadam.com/verify`
- API docs: `https://ai-modals.hamqadam.com/api-docs` (Basic Auth, user `hamqadam`)

## Ground rules this integration follows

1. **Registration is never blocked.** Every hook uses
   `RunAiVerification::dispatchAfterResponse(...)`, which runs after the HTTP
   response has been sent. That matters because this project runs
   `QUEUE_CONNECTION=sync`, where a plain `dispatch()` executes inline and would
   add the model's multi-second CPU inference to the user's registration.
2. **Existing payloads are untouched.** Mobile and web registration request
   bodies are unchanged. `/api/v1/auth/register` also keeps its response shape,
   because `AuthTokenResource` is shared with login.
3. **The service never fails a flow.** `AiVerificationService` catches
   everything and records the outcome instead of throwing.
4. **The model recommends, this app decides.** Auto-applying APPROVE/REJECT is
   opt-in via `config('ai_verification.auto_apply')`, off by default.

## Where verification fires

| Trigger | Source tag | Images available |
|---|---|---|
| `POST /api/v1/auth/register` | `registration_api` | none — records `no_images` |
| `POST /api/v1/auth/register/complete` | `registration_api` | profile photo |
| `POST /api/v1/auth/register/step/11` (Photos) | `registration_api` | profile photo |
| Web `POST /register` | `registration_web` | none — records `no_images` |
| `POST /api/v1/verification/submit` | `document_submit` | selfie + CNIC front + profile |
| Dashboard button / retry API | `manual_retry` | whatever is on file |

### Why registration alone cannot fully verify anybody

Registration collects **one** photo (`profile_photo`) and no CNIC. The model
needs at least a `live_selfie`, and identity comparison needs two *different*
images of the same person.

So at registration the model can do face detection, quality scoring and
fraud/liveness checks — but not identity matching. The recommendation comes back
`MANUAL_REVIEW`, which is the honest answer.

The profile photo is sent as `live_selfie` **only**. Sending the same file as
both `live_selfie` and `profile_image` would make the model compare an image
with itself, score ~1.0, and report a meaningless "match" — a false APPROVE.

Real verification happens at `POST /api/v1/verification/submit`, where a selfie
and CNIC front both exist.

## New endpoints

    GET  /api/v1/verification/ai/status     current state
    GET  /api/v1/verification/ai/history    last 20 attempts
    POST /api/v1/verification/ai/run        run now (synchronous, throttle 3/min)

`POST .../run` takes **no uploads**. It rebuilds the model payload from the
database, preferring the newest non-final document request (CNIC + selfie) and
falling back to the profile photo. This is the recovery path for "registration
succeeded but verification did not".

Web equivalent: `POST /member/ai-verification/run`, wired to the dashboard button.

## Data model

`ai_verification_attempts` — one row per attempt, including skipped ones, so
"why is this user not verified?" is always answerable. Stores the recommendation,
scores, which images were sent, and the full model response. No image bytes.

Deliberately a **separate table** rather than reusing
`profile_verification_requests`: `VerificationService::submit()` throws 409 when
a non-final request exists, and only Approved/Rejected are final. Creating a
draft request at registration would lock the user out of ever submitting CNIC
documents.

Summary columns on `members`: `ai_verification_status`,
`ai_verification_recommendation`, `ai_verification_attempts`,
`ai_verification_last_attempt_at`, `ai_verified_at`.

On `profile_verification_requests` the AI result populates the pre-existing but
unused `face_match_status` / `face_match_score`, plus new `ai_recommendation`,
`ai_fraud_risk_score`, `ai_checked_at`.

## Configuration

See `config/ai_verification.php` and the `AI_VERIFICATION_*` block in
`.env.example`. The key is sent as `X-API-Key` — **not** the gateway Bearer
token, which only guards `/api/*`, `/qdrant/*` and the docs pages.

`AI_VERIFICATION_REMOTE_ASSET_BASE` exists because a local clone restored from a
live DB dump has `uploads` rows but not the files. Point it at live to let the
resolver fetch image bytes over HTTP.

## Production notes

- Set `QUEUE_CONNECTION=database` and run a worker (`php artisan queue:work
  --queue=ai`) for real retries and failure visibility. On `sync`,
  `dispatchAfterResponse` still works but has no retry.
- The model is CPU-only on 2 cores. Expect ~2 s for detection-only and longer
  when CNIC OCR runs. `AI_VERIFICATION_TIMEOUT` defaults to 120 s.

## Pre-existing issues found while integrating (NOT caused by this work)

1. **`careers.years_of_experience` is `NOT NULL` with no default**, but
   registration inserts into `careers` without it. Under MySQL strict mode this
   is a hard error and **every registration 500s**. This was the original local
   500. Worked around with `DB_STRICT=false` locally (`config/database.php` now
   reads `env('DB_STRICT', true)`, so production behaviour is unchanged).
   **The proper fix is to make that column nullable or have the code supply a
   value** — disabling strict mode only hides it.
2. `getBaseURL()` in `app/Http/Helpers.php` reads `$_SERVER['HTTP_HOST']`
   directly, which is undefined in CLI/queue contexts and throws
   "Undefined array key HTTP_HOST".
3. `FORCE_HTTPS=On` in `.env` makes plain `http://localhost` redirect to a
   self-signed HTTPS origin, which browsers block. Set `Off` for local dev.
