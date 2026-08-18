<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| AI Identity Verification Service
|--------------------------------------------------------------------------
|
| Talks to the Hamqadam AI Identity Verification service. Docs:
|   https://ai-modals.hamqadam.com/api-docs
|
| The service NEVER decides a user's account status. It returns a
| recommendation (APPROVE / REJECT / MANUAL_REVIEW) and this application
| owns the decision. Registration must never be blocked by it.
|
*/

return [
    'enabled' => env('AI_VERIFICATION_ENABLED', true),

    'base_url' => rtrim((string) env('AI_VERIFICATION_URL', 'https://ai-modals.hamqadam.com/verify'), '/'),

    // Sent as X-API-Key. NOT the gateway Bearer token - the verification
    // service enforces its own key on /v1/*.
    'api_key' => env('AI_VERIFICATION_API_KEY'),

    // The model runs CPU-only face + document inference, so a single request
    // can take several seconds. Keep this comfortably above that but below
    // PHP's max_execution_time.
    'timeout' => (int) env('AI_VERIFICATION_TIMEOUT', 120),
    'connect_timeout' => (int) env('AI_VERIFICATION_CONNECT_TIMEOUT', 10),
    'retries' => (int) env('AI_VERIFICATION_RETRIES', 2),
    'retry_delay_ms' => (int) env('AI_VERIFICATION_RETRY_DELAY', 1500),

    // Add the face to the model's duplicate gallery when the result is
    // APPROVE. Off by default: enrolling biometric templates is a data
    // decision, not a technical one.
    'enrol_on_success' => env('AI_VERIFICATION_ENROL_ON_SUCCESS', false),

    /*
    | Uploaded images live under <project>/public/<uploads.file_name> on the
    | live server (see static_asset()). A local clone restored from a live DB
    | dump has the rows but not the files, so the resolver also accepts a
    | remote base URL to fetch the bytes from. Leave null to disable.
    */
    'remote_asset_base' => env('AI_VERIFICATION_REMOTE_ASSET_BASE'),

    // Map the model's recommendation onto what this app records.
    'auto_apply' => [
        // Mark the request approved without a human when the model says
        // APPROVE. Off by default - a human should confirm an identity pass.
        'approve' => env('AI_VERIFICATION_AUTO_APPROVE', false),
        // Mark it rejected without a human when the model says REJECT.
        'reject' => env('AI_VERIFICATION_AUTO_REJECT', false),
    ],
];
