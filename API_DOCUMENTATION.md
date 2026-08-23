# HamQadam App API v1

Curated API documentation for the Flutter mobile app and React web app.

Legacy CMS/mobile endpoints under `/api/...` are intentionally excluded. Admin-only endpoints are intentionally excluded. Use only `/api/v1/...` endpoints from the app.

Base URL:

```text
{{APP_URL}}/api/v1
```

Assets:

- Browser docs: `{{APP_URL}}/api-docs`
- OpenAPI JSON: `{{APP_URL}}/api/openapi-v1.json`
- Postman collection: `{{APP_URL}}/api/postman-v1.json`

Authentication header:

```http
Authorization: Bearer {{token}}
Accept: application/json
```

Single app auth-check endpoint:

```http
GET /api/v1/auth/me
```

Do not use legacy `/api/member-validate` or `/api/app-check` in the new app.

## Mobile App Endpoint Checklist

Use this checklist for Flutter/mobile QA. These are the app-facing groups only:

| Group | Required App Endpoints |
|---|---|
| Auth | `/auth/register/complete`, `/auth/register/steps`, `/auth/register/step1`, `/auth/register/step/{step}`, `/auth/register/status`, `/auth/login/email`, `/auth/login/mobile`, `/auth/me`, `/auth/logout`, `/auth/devices`, `/auth/account`, `/auth/register/request-otp`, `/auth/register/verify-otp` |
| Profile | `/profile`, `/profiles/{profile}`, `/profile/privacy`, `/profile/visibility`, `/partner-preferences` |
| Discovery | `/search/profiles`, `/search/saved`, `/search/history`, `/matches`, `/matches/recommended`, `/matches/daily`, `/profiles/{profile}/compatibility` |
| Proposals | `/proposals`, `/proposals/{proposal}/accept`, `/proposals/{proposal}/reject`, `/proposals/favourites`, `/proposals/ignored` |
| Chat | `/chat/threads`, `/chat/threads/{thread}/messages`, `/chat/threads/{thread}/typing`, `/chat/threads/{thread}/report` |
| Interests | `/interests`, `/interests/sent`, `/interests/received`, `/interests/coin-balance`, `/interests/{interest}/accept`, `/interests/{interest}/reject` |
| Packages & Payments | `/payments/plans`, `/payments/checkout`, `/payments/history`, `/payments/invoices/{payment}`, `/payments/coupons/validate` |
| Notifications | `/notifications`, `/notifications/unread-count`, `/notifications/preferences`, `/notifications/push-tokens` |
| Safety & Trust | `/verification/current`, `/verification/submit`, `/safety/report`, `/safety/block`, `/safety/mute` |
| Family | `/family/dashboard`, `/family/guardians`, `/family/wali-mode`, `/family/approval-requests`, `/family/conversations` |
| Content | `/content/articles`, `/content/success-stories`, `/content/advice`, `/content/expert/questions`, `/content/forums`, `/content/webinars` |
| AI Helpers | `/ai/bio`, `/ai/conversation-starters`, `/ai/profile-quality`, `/ai/scam-check`, `/ai/red-flag-check` |

Standard success envelope:

```json
{
  "success": true,
  "message": "Success message.",
  "data": {},
  "errors": null
}
```

Standard validation/error envelope:

```json
{
  "success": false,
  "message": "Validation failed.",
  "data": null,
  "errors": {
    "email": ["The email field is required."]
  }
}
```

## Auth

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| Register with complete onboarding (mobile) | POST | `/auth/register/complete` | Full 18-step account, profile, and partner preference payload |
| Register with full onboarding (legacy) | POST | `/auth/register` | Full account, profile, and partner preference payload |
| Get registration steps | GET | `/auth/register/steps` | None |`n| Start step-wise registration (legacy) | POST | `/auth/register/step1` | Step 1 account-for payload, returns token |`n| Save registration step (legacy) | POST | `/auth/register/step/{step}` | Step-specific payload for steps 2-18 |`n| Registration status | GET | `/auth/register/status` | None |
| Request registration OTP | POST | `/auth/register/request-otp` | `email` (optional, uses authenticated user email if omitted) |
| Verify registration OTP | POST | `/auth/register/verify-otp` | `email`, `code` |
| Login with email | POST | `/auth/login/email` | `email`, `password`, `device_name`, `device_type` |
| Request mobile OTP | POST | `/auth/otp/mobile` | `phone`, `country_code` |
| Login with mobile OTP | POST | `/auth/login/mobile` | `phone`, `country_code`, `otp`, `device_name` |
| Google login | POST | `/auth/login/google` | `id_token`, `device_name` |
| Check current user | GET | `/auth/me` | None |
| Logout current device | POST | `/auth/logout` | None |
| Logout all devices | POST | `/auth/logout/all` | None |
| Deactivate account | DELETE | `/auth/account` | None |
| Forgot password | POST | `/auth/forgot-password` | `email` |
| Reset password | POST | `/auth/reset-password` | `email`, `otp`, `password`, `password_confirmation` |

Register sample (complete 18-step payload):

```json
{
  "on_behalf": 1,
  "gender": 2,
  "marriage_timeline": "within_6_months",
  "willing_to_work_after_marriage": "depends_on_mutual_understanding",
  "expects_spouse_to_work": "depends_on_mutual_understanding",
  "full_name": "Ayesha Khan",
  "date_of_birth": "1998-04-15",
  "religion_id": 1,
  "mother_tongue": 1,
  "sect_main_id": 1,
  "school_of_thought_id": 1,
  "tradition_id": 1,
  "country_id": 166,
  "state_id": 2728,
  "city_id": 85568,
  "area": "Gulberg",
  "country_code": "+92",
  "phone": "3001234567",
  "email": "ayesha@example.com",
  "caste_id": 1,
  "sub_caste_id": 2,
  "marital_status_id": 1,
  "education_level_id": 6,
  "degree_id": 1,
  "field_of_study_id": 2,
  "institution_id": 45,
  "graduation_year": 2024,
  "education_status": "completed",
  "expected_graduation_year": null,
  "height": 5.4,
  "diet": "Vegetarian",
  "annual_income": 1800000,
  "employment_status": "private",
  "profession_category_id": 2,
  "profession_id": 20,
  "job_title": "Software Engineer",
  "organization": "Tech Company",
  "years_of_experience": 1,
  "profile_photo": "file_id_or_base64_string",
  "additional_photos": ["file_id_or_base64_string_1", "file_id_or_base64_string_2"],
  "about_me": "Family-oriented and serious about marriage.",
  "cnic_number": "12345-6789012-3",
  "cnic_front": "file_id_or_base64_string",
  "cnic_back": "file_id_or_base64_string",
  "selfie_verification": "file_id_or_base64_string",
  "hobbies": "Reading, Music, Travel, Photography",
  "father_occupation": "Engineer",
  "mother_occupation": "Teacher",
  "siblings_sisters": 2,
  "siblings_brothers": 1,
  "family_location": "Lahore",
  "live_with_family": "yes",
  "family_values": "Middle",
  "family_country_id": 166,
  "family_state": "Punjab",
  "family_city": "Lahore",
  "partner_age_min": 27,
  "partner_age_max": 34,
  "partner_height_min": 5.0,
  "partner_height_max": 6.0,
  "partner_marital_status_id": 1,
  "partner_religion_id": 1,
  "partner_caste_id": 1,
  "partner_language_id": 1,
  "partner_country_id": 166,
  "partner_state_id": 2728,
  "partner_city_id": 85568,
  "partner_education": "Graduate",
  "partner_profession": "Professional",
  "partner_income_min": 100000,
  "partner_income_max": 500000,
  "deal_breakers": ["No smoking", "Family-oriented"],
  "email_verify": "ayesha@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

The complete registration API creates a draft account and returns a Sanctum token. Use that token to request and verify the registration OTP.

### Mobile Registration Flow

The frontend handles all 18 registration steps locally. Submit the complete data in one request, then verify via email OTP.

```text
Frontend: 18 steps locally
  ↓
POST /auth/register/complete
  ↓
POST /auth/register/request-otp (authenticated)
  ↓
User enters OTP
  ↓
POST /auth/register/verify-otp (authenticated)
  ↓
Registration verified and finalized
```

**Complete Registration API**

```http
POST /auth/register/complete
Content-Type: application/json
Content-Type: multipart/form-data
```

The frontend handles all 18 registration steps locally and submits the complete payload in one request. Mandatory steps: 1-14, 17, 18. Optional steps: 15, 16.

```json
{
  "on_behalf": 1,
  "gender": 2,
  "marriage_timeline": "within_6_months",
  "willing_to_work_after_marriage": "depends_on_mutual_understanding",
  "expects_spouse_to_work": "depends_on_mutual_understanding",
  "full_name": "Ayesha Khan",
  "date_of_birth": "1998-04-15",
  "religion_id": 1,
  "mother_tongue": 1,
  "sect_main_id": 1,
  "school_of_thought_id": 1,
  "tradition_id": 1,
  "country_id": 166,
  "state_id": 2728,
  "city_id": 85568,
  "area": "Gulberg",
  "country_code": "+92",
  "phone": "3001234567",
  "email": "ayesha@example.com",
  "caste_id": 1,
  "sub_caste_id": 2,
  "marital_status_id": 1,
  "education_level_id": 6,
  "degree_id": 1,
  "field_of_study_id": 2,
  "institution_id": 45,
  "graduation_year": 2024,
  "education_status": "completed",
  "expected_graduation_year": null,
  "height": 5.4,
  "diet": "Vegetarian",
  "annual_income": 1800000,
  "employment_status": "private",
  "profession_category_id": 2,
  "profession_id": 20,
  "job_title": "Software Engineer",
  "organization": "Tech Company",
  "years_of_experience": 1,
  "profile_photo": "file_id_or_base64_string",
  "additional_photos": ["file_id_or_base64_string_1", "file_id_or_base64_string_2"],
  "about_me": "Family-oriented and serious about marriage.",
  "cnic_number": "12345-6789012-3",
  "cnic_front": "file_id_or_base64_string",
  "cnic_back": "file_id_or_base64_string",
  "selfie_verification": "file_id_or_base64_string",
  "hobbies": "Reading, Music, Travel, Photography",
  "father_occupation": "Engineer",
  "mother_occupation": "Teacher",
  "siblings_sisters": 2,
  "siblings_brothers": 1,
  "family_location": "Lahore",
  "live_with_family": "yes",
  "family_values": "Middle",
  "family_country_id": 166,
  "family_state": "Punjab",
  "family_city": "Lahore",
  "partner_age_min": 27,
  "partner_age_max": 34,
  "partner_height_min": 5.0,
  "partner_height_max": 6.0,
  "partner_marital_status_id": 1,
  "partner_religion_id": 1,
  "partner_caste_id": 1,
  "partner_language_id": 1,
  "partner_country_id": 166,
  "partner_state_id": 2728,
  "partner_city_id": 85568,
  "partner_education": "Graduate",
  "partner_profession": "Professional",
  "partner_income_min": 100000,
  "partner_income_max": 500000,
  "deal_breakers": ["No smoking", "Family-oriented"],
  "email_verify": "ayesha@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

**Dynamic Dropdowns:** All dropdown values are available from `GET /api/v1/profile/dropdown-reference-data`.

**Hardcoded Options:**
- `gender`: `1` (Male), `2` (Female)
- `marriage_timeline`: `immediate`, `within_3_months`, `within_6_months`, `within_1_year`
- `willing_to_work_after_marriage`: `yes`, `no`, `depends_on_mutual_understanding`
- `expects_spouse_to_work`: `yes`, `no`, `depends_on_mutual_understanding`
- `diet`: `Vegetarian`, `Non-Vegetarian`
- `employment_status`: `government`, `private`, `civil`, `defence`, `self_employed`, `unemployed`, `retired`
- `education_status`: `completed`, `in_progress`, `dropped`
- `live_with_family`: `yes`, `no`
- `family_values`: `Elite`, `High`, `Middle`, `Aspiring`, `Poor`

**Notes:**
- Photo fields (`profile_photo`, `additional_photos`) accept either actual image files in `multipart/form-data` format, or strings (file IDs from upload endpoints or base64 encoded strings) in JSON payload. These are stored as string references in the database.
- Verification documents (`cnic_front`, `cnic_back`, `selfie_verification`) also accept either actual image files in `multipart/form-data` format, or strings (file IDs or base64 encoded strings) for the API.
- `email_verify` is used to confirm the email address. Password must be at least 8 characters.
- Optional steps 15-16 may be omitted or included with partial data.

```json
{
  "success": true,
  "message": "Registration submitted successfully. Please verify your email to complete registration.",
  "data": {
    "token": "1|abc123...",
    "token_type": "Bearer",
    "expires_at": "2026-08-17T12:00:00.000000Z",
    "user": { "id": 101, "type": "member" },
    "registration": {
      "total_steps": 18,
      "completed_steps": ["step1", "step2", ... "step18"],
      "next_step": "completed",
      "optional_steps": ["step14", "step15", "step16"],
      "registration_completed": false
    }
  },
  "errors": null
}
```

### Request Registration OTP

After submitting the complete registration, request an email OTP to verify the account.

```http
POST /auth/register/request-otp
Authorization: Bearer {{token}}
Accept: application/json
```

Request body:

```json
{
  "email": "ayesha@example.com"
}
```

`email` is optional. If omitted, the authenticated user's email is used.

Response:

```json
{
  "success": true,
  "message": "Verification code sent.",
  "data": {
    "expires_at": "2026-08-15T12:10:00.000000Z"
  },
  "errors": null
}
```

In non-production environments, `meta.debug_otp` contains the plaintext code for testing.

### Verify Registration OTP

```http
POST /auth/register/verify-otp
Authorization: Bearer {{token}}
Accept: application/json
```

Request body:

```json
{
  "email": "ayesha@example.com",
  "code": "123456"
}
```

Response:

```json
{
  "success": true,
  "message": "Registration verified successfully.",
  "data": null,
  "errors": null
}
```

On success, the account is marked as verified, approved, membership is activated, the Basic Free package reward is applied, and `registration_completed_at` is set.

### Legacy Step-wise Registration (Deprecated)

The legacy step-wise endpoints remain available for backward compatibility. New mobile/web apps should use `/auth/register/complete` with the OTP flow.

```http
GET /auth/register/steps
POST /auth/register/step1
POST /auth/register/step/{step}
GET /auth/register/status
```

## Dynamic Dropdown Data API

All dropdown options are available from a single endpoint:

```http
GET /api/v1/profile/dropdown-reference-data
Authorization: Bearer {{token}}
```

Response includes all dropdown data:

```json
{
  "success": true,
  "data": {
    "countries": [{"id": 166, "name": "Pakistan"}, ...],
    "states": [{"id": 2728, "name": "Punjab", "country_id": 166}, ...],
    "cities": [{"id": 85568, "name": "Lahore", "state_id": 2728}, ...],
    "areas": [],
    "religions": [{"id": 1, "name": "Islam"}, ...],
    "languages": [{"id": 1, "name": "Urdu"}, ...],
    "castes": [{"id": 1, "name": "Arain"}, ...],
    "sub_castes": [{"id": 1, "name": "Arain", "caste_id": 1}, ...],
    "marital_statuses": [{"id": 1, "name": "Never Married"}, ...],
    "on_behalves": [{"id": 1, "name": "Self"}, {"id": 2, "name": "Son"}, ...],
    "profession_categories": [{"id": 1, "name": "Engineering"}, ...],
    "professions": [{"id": 1, "name": "Software Engineer", "profession_category_id": 1}, ...],
    "education_levels": [{"id": 1, "name": "Matriculation"}, ...],
    "degrees": [{"id": 1, "name": "Bachelor's", "education_level_id": 6}, ...],
    "fields_of_study": [{"id": 1, "name": "Computer Science", "degree_id": 1}, ...],
    "institutions": [{"id": 45, "name": "University of the Punjab", "country_id": 166}, ...],
    "sect_main": [{"id": 1, "name": "Sunni", "religion_id": 1}, ...],
    "school_of_thought": [{"id": 1, "name": "Hanafi", "sect_main_id": 1}, ...],
    "traditions": [{"id": 1, "name": "Barelvi", "school_of_thought_id": 1}, ...],
    "hobbies": [{"id": "Reading", "name": "Reading"}, {"id": "Cooking", "name": "Cooking"}, ...],
    "gender": [{"id": 1, "name": "Male"}, {"id": 2, "name": "Female"}],
    "marriage_timeline": [{"id": "immediate", "name": "Immediate"}, ...],
    "willing_to_work_after_marriage": [{"id": "yes", "name": "Yes"}, ...],
    "expects_spouse_to_work": [{"id": "yes", "name": "Yes"}, ...],
    "diet": [{"id": "Vegetarian", "name": "Vegetarian"}, ...],
    "employment_status": [{"id": "government", "name": "Government"}, ...],
    "education_status": [{"id": "completed", "name": "Completed"}, ...],
    "live_with_family": [{"id": "yes", "name": "Yes"}, ...]
  }
}
```

## Complete Dropdown Reference

### Dynamic Dropdowns (Database-backed)

All dynamic dropdown data and hardcoded options are available from a single endpoint:

| Field | Table | API Endpoint | Dependencies |
|-------|-------|-------------|--------------|
| `on_behalf` | `on_behalves` | `/api/v1/profile/dropdown-reference-data` | None |
| `religion_id` | `religions` | `/api/v1/profile/dropdown-reference-data` | None |
| `mother_tongue` | `member_languages` | `/api/v1/profile/dropdown-reference-data` | None |
| `sect_main_id` | `sect_main` | `/api/v1/profile/dropdown-reference-data` | `religion_id` |
| `school_of_thought_id` | `school_of_thought` | `/api/v1/profile/dropdown-reference-data` | `sect_main_id` |
| `tradition_id` | `traditions` | `/api/v1/profile/dropdown-reference-data` | `school_of_thought_id` |
| `country_id` | `countries` | `/api/v1/profile/dropdown-reference-data` | None |
| `state_id` | `states` | `/api/v1/profile/dropdown-reference-data` | `country_id` |
| `city_id` | `cities` | `/api/v1/profile/dropdown-reference-data` | `state_id` |
| `area` | (dynamic) | `/api/v1/profile/dropdown-reference-data` | `city_id` |
| `caste_id` | `castes` | `/api/v1/profile/dropdown-reference-data` | None |
| `sub_caste_id` | `sub_castes` | `/api/v1/profile/dropdown-reference-data` | `caste_id` |
| `marital_status_id` | `marital_statuses` | `/api/v1/profile/dropdown-reference-data` | None |
| `education_level_id` | `education_levels` | `/api/v1/profile/dropdown-reference-data` | None |
| `degree_id` | `degrees` | `/api/v1/profile/dropdown-reference-data` | `education_level_id` |
| `field_of_study_id` | `fields_of_study` | `/api/v1/profile/dropdown-reference-data` | `degree_id` |
| `institution_id` | `institutions` | `/api/v1/profile/dropdown-reference-data` | `country_id` (filtered by country only) |
| `profession_category_id` | `profession_categories` | `/api/v1/profile/dropdown-reference-data` | None |
| `profession_id` | `professions` | `/api/v1/profile/dropdown-reference-data` | `profession_category_id` |
| `hobbies` | `hobbies` | `/api/v1/profile/dropdown-reference-data` | None |
| `family_country_id` | `countries` | `/api/v1/profile/dropdown-reference-data` | None |
| `partner_marital_status_id` | `marital_statuses` | `/api/v1/profile/dropdown-reference-data` | None |
| `partner_religion_id` | `religions` | `/api/v1/profile/dropdown-reference-data` | None |
| `partner_caste_id` | `castes` | `/api/v1/profile/dropdown-reference-data` | None |
| `partner_language_id` | `member_languages` | `/api/v1/profile/dropdown-reference-data` | None |
| `partner_country_id` | `countries` | `/api/v1/profile/dropdown-reference-data` | None |
| `partner_state_id` | `states` | `/api/v1/profile/dropdown-reference-data` | `partner_country_id` |
| `partner_city_id` | `cities` | `/api/v1/profile/dropdown-reference-data` | `partner_state_id` |

### Hardcoded Dropdowns (Static values)

| Field | Options | Description |
|-------|---------|-------------|
| `gender` | `1` (Male), `2` (Female) | Gender selection |
| `marriage_timeline` | `immediate`, `within_3_months`, `within_6_months`, `within_1_year` | When planning to get married |
| `willing_to_work_after_marriage` | `yes`, `no`, `depends_on_mutual_understanding` | Work after marriage preference |
| `expects_spouse_to_work` | `yes`, `no`, `depends_on_mutual_understanding` | Spouse work expectation |
| `diet` | `Vegetarian`, `Non-Vegetarian` | Dietary preference |
| `employment_status` | `government`, `private`, `civil`, `defence`, `self_employed`, `unemployed`, `retired` | Employment type |
| `education_status` | `completed`, `in_progress`, `dropped` | Education completion status |
| `live_with_family` | `yes`, `no` | Family living arrangement |
| `family_values` | `Elite`, `High`, `Middle`, `Aspiring`, `Poor` | Family financial status |

## Profile

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| Current profile | GET | `/profile` | None |
| Public profile detail | GET | `/profiles/{profile}` | None |
| Profile compatibility | GET | `/profiles/{profile}/compatibility` | None |
| Update profile | PUT | `/profile` | Profile fields |
| Update privacy | PATCH | `/profile/privacy` | Privacy fields |
| Hide/show profile | PATCH | `/profile/visibility` | `hide_profile` |
| Deactivate profile | POST | `/profile/deactivate` | None |

### GET /profile returns everything registration collected

Registration writes its 18 steps across several tables, so the response is
grouped by area. `user`, `member` and `privacy` keep their original shape and
field names for backwards compatibility; the rest are additional sections:

| Section | Source | Contains |
|---|---|---|
| `religion_and_language` | step 3 | religion, sect, school of thought, tradition, languages, prayer frequency |
| `caste` | step 6 | caste, sub-caste, biradari, ethnicity, family/personal/community values |
| `location` | step 4 | country, state, city, `area`, address type, relocation, visa status |
| `education` | step 8 | level, degree, field of study, institution, graduation year, status |
| `career` | step 10 | profession, job title, organization, years of experience, income, employment status |
| `physical` | step 9 | height, weight, body type, complexion, blood group, eye/hair colour, `diet` |
| `lifestyle_and_interests` | step 14 | hobbies, interests, life values, love language, personality, communication |
| `family` | steps 15-16 | parents' names and occupations, siblings, family type/values/location |
| `marriage_expectations` | step 17 | looking for, timeline, children preference, expectations |
| `photos` | step 11 | profile photo, cover photo, gallery URLs |
| `verification` | step 13 | CNIC verification status plus the AI check under `verification.ai` |
| `registration` | — | completion percentage and completed steps |

List-shaped fields (`hobbies`, `known_languages`, `life_values`, …) always come
back as arrays even where the column holds a comma-separated string.

### PUT /profile

Accepts every field above, not just the original eighteen. Each one is written
to the same table registration writes to, so the two paths cannot disagree.
Only keys present in the request are touched, so a partial update never blanks
a field you did not send.

```json
{
  "religion_id": 1,
  "country_id": 167,
  "area": "Gulberg III",
  "education_level_id": 4,
  "height": "5.8",
  "diet": "halal",
  "job_title": "Backend Engineer",
  "hobbies": ["reading", "cricket"],
  "father_occupation": "Teacher",
  "siblings_brothers": 2
}
```

## Partner Preferences

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| Current preferences | GET | `/partner-preferences` | None |
| Update preferences | PUT | `/partner-preferences` | Preference fields |
| Clear preferences | DELETE | `/partner-preferences` | None |

```json
{
  "age_min": 27,
  "age_max": 34,
  "religion_id": 1,
  "city_id": 1,
  "deal_breakers": ["smoking"]
}
```

### Preferences now filter the candidate pool

Preferences used to affect only the compatibility *score* - the match query
itself ignored them, so a member asking for 22-30 year olds of a given religion
was still shown everybody. `/matches` and `/matches/recommended` now filter on:

- age range (`preferred_age_min` / `preferred_age_max`, derived from birthday)
- `marital_status_id`
- `religion_id` and `caste_id`
- preferred country / state / city

A candidate is excluded only when their value is **known** and outside the
preference. Candidates missing that field are kept, because a null is not a
mismatch and profiles are sparse - filtering on unknowns would empty the
results. Scoring still ranks better matches above the unknowns.

Height, education, profession and income are deliberately **not** hard filters.
Height is stored in metres on the preference side and feet on the member side,
and the others are free text against ids/ranges - comparing them in SQL silently
dropped valid candidates. They influence the score instead.

## Search And Matching

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| Search profiles | GET | `/search/profiles` | Query filters |
| Saved searches | GET | `/search/saved` | None |
| Save search | POST | `/search/saved` | `name`, `filters` |
| Delete saved search | DELETE | `/search/saved/{id}` | None |
| Search history | GET | `/search/history` | None |
| Hide profile from user | POST | `/search/hidden-users` | `user_id` |
| Unhide user | DELETE | `/search/hidden-users/{user}` | None |
| Smart matches | GET | `/matches` | Query filters optional |
| Recommended matches | GET | `/matches/recommended` | Query filters optional |
| Daily matches | GET | `/matches/daily` | Query filters optional |
| Match detail | GET | `/matches/{profile}` | None |
| Match feedback | POST | `/matches/feedback` | `user_id`, `feedback`, `source`, `note` |

Search sample:

```text
GET /search/profiles?age_min=24&age_max=32&verified_only=1&photo_only=1&compatibility_min=70&nearby=1&sort=compatibility
```

Match feedback sample:

```json
{
  "user_id": 25,
  "feedback": "like",
  "source": "daily_recommendation",
  "note": "Good suggestion"
}
```

## Proposals

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| List proposals | GET | `/proposals` | Query filters optional |
| Send proposal | POST | `/proposals` | `user_id`, `note` |
| Accept proposal | POST | `/proposals/{proposal}/accept` | `note` optional |
| Reject proposal | POST | `/proposals/{proposal}/reject` | `note` optional |
| Withdraw proposal | POST | `/proposals/{proposal}/withdraw` | `note` optional |
| Cancel proposal | POST | `/proposals/{proposal}/cancel` | `note` optional |
| Proposal timeline | GET | `/proposals/{proposal}/timeline` | None |
| Proposal meetings | GET | `/proposals/{proposal}/meetings` | None |
| Schedule meeting | POST | `/proposals/{proposal}/meetings` | Meeting payload |
| Update meeting | PATCH | `/proposals/meetings/{meeting}` | Meeting fields |
| Post-meeting feedback | POST | `/proposals/meetings/{meeting}/feedback` | Feedback payload |
| Recording consent | POST | `/proposals/meetings/{meeting}/recording-consent` | `consent`, `recording_url` optional |
| Engagement/Nikah status | POST | `/proposals/relationship-status` | Relationship status payload |
| List favourites | GET | `/proposals/favourites` | None |
| Add favourite | POST | `/proposals/favourites` | `user_id` |
| Check favourite | GET | `/proposals/favourites/{user}/check` | None |
| Remove favourite | DELETE | `/proposals/favourites/{user}` | None |
| Ignore profile | POST | `/proposals/ignored` | `user_id` |
| Remove ignored profile | DELETE | `/proposals/ignored/{user}` | None |

```json
{
  "user_id": 25,
  "note": "Assalamualaikum, I would like our families to connect."
}
```

Schedule meeting sample:

```json
{
  "meeting_type": "family",
  "scheduled_at": "2026-07-20 19:30:00",
  "duration_minutes": 45,
  "meeting_url": "https://meet.example.com/hamqadam-123",
  "chaperone_mode": true,
  "chaperone_user_id": 45,
  "recording_consent_requested": false,
  "pre_meeting_questionnaire": {
    "agenda": ["Family introduction", "Expectations", "Next step"],
    "questions": ["Are both families comfortable proceeding?"]
  },
  "notes": "Parents from both sides will join."
}
```

Meeting feedback sample:

```json
{
  "rating": 5,
  "interested_next_step": true,
  "notes": "Families aligned well. Proceed to another call."
}
```

Relationship status sample:

```json
{
  "partner_user_id": 25,
  "proposal_id": 101,
  "status": "nikah",
  "status_date": "2026-08-15",
  "notes": "Nikah completed with family consent.",
  "is_public": false
}
```

## Chat

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| Chat threads | GET | `/chat/threads` | None |
| Thread messages | GET | `/chat/threads/{thread}/messages` | None |
| Send message | POST | `/chat/threads/{thread}/messages` | `message`, `message_type`, `reply_to_id` |
| Typing indicator | POST | `/chat/threads/{thread}/typing` | None |
| Block chat | POST | `/chat/threads/{thread}/block` | None |
| Unblock chat | POST | `/chat/threads/{thread}/unblock` | None |
| Report chat | POST | `/chat/threads/{thread}/report` | `reason` |
| Delete message for me | DELETE | `/chat/messages/{message}` | None |

```json
{
  "message": "Assalamualaikum, how are you?",
  "message_type": "text",
  "reply_to_id": null
}
```

## Verification

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| Current verification | GET | `/verification/current` | None |
| Verification history | GET | `/verification/history` | None |
| Submit verification | POST | `/verification/submit` | Multipart |

Multipart fields:

```text
cnic_number: 35202-1234567-1
cnic_front: file
cnic_back: file
selfie: file
```

## Interests (Express Interest)

Express-interest proposals. Sending costs coins from the member's
`remaining_interest` balance; responding is free. These endpoints share
`InterestService` with the website, so coin cost, package-usage logging and
notifications are identical across web and app.

| Action | Method | Endpoint | Payload |
|---|---|---|---|
| Sent interests | GET | `/interests/sent` | `status` (optional), `per_page` |
| Received interests | GET | `/interests/received` | `status` (optional), `per_page` |
| Coin balance | GET | `/interests/coin-balance` | None |
| Send interest | POST | `/interests` | `user_id`, `initial_note` (optional) |
| Accept | POST | `/interests/{interest}/accept` | None |
| Reject | POST | `/interests/{interest}/reject` | None |
| Withdraw | DELETE | `/interests/{interest}` | None |

`status` accepts `pending`, `accepted`, `rejected`, `withdrawn`, `cancelled`,
`expired`.

### Coins

The cost per interest comes from `feature_coin_cost('express_interest')` and is
returned by every endpoint as `coin_balance`:

```json
{ "remaining_interest": 8, "cost_per_interest": 2, "can_send": true }
```

`POST /interests` deducts the cost, writes a `package_usages` row, and returns
`coins_spent`. The insert, the deduction and the usage log run in one
transaction, so a failure cannot leave a member charged for an interest that was
not created.

### Error codes

| HTTP | code | Meaning |
|---|---|---|
| 402 | `insufficient_coins` | Balance below the cost. Send the user to packages. |
| 409 | `interest_exists` | An interest is already pending or accepted either way. |
| 422 | `self_interest` | Cannot express interest in yourself. |
| 422 | `already_answered` | Interest is no longer pending. |
| 404 | `member_unavailable` | Recipient is blocked, deactivated or not approved. |

Accepting creates the chat thread, so `/chat/threads` becomes available for that
pair immediately.

Rejecting sets status `rejected` and keeps the row (it used to hard-delete it,
which emptied the "rejected" filter and let the sender pay again for the same
request). Withdrawing does **not** refund coins - the recipient was already
notified.

---

## AI Identity Verification

Runs the CNIC/selfie images through the AI service at
`ai-modals.hamqadam.com`. Fired automatically after registration (out of band,
so it never delays or fails a signup) and available on demand here.

| Action | Method | Endpoint | Payload |
|---|---|---|---|
| Current AI status | GET | `/verification/ai/status` | None |
| Attempt history | GET | `/verification/ai/history` | None |
| Run verification now | POST | `/verification/ai/run` | None (throttle 3/min) |

`POST /verification/ai/run` takes **no uploads**. It rebuilds the model payload
from the database, preferring the newest non-final verification request (CNIC +
selfie from registration step 13) and falling back to the profile photo.

```json
{
  "status": "manual_review",
  "recommendation": "MANUAL_REVIEW",
  "attempts": 2,
  "verified_at": null,
  "can_retry": true,
  "message": "Your verification needs a manual review.",
  "last_error": null
}
```

`status` is one of `not_started`, `pending`, `approved`, `rejected`,
`manual_review`, `failed`. The model returns a *recommendation*; the backend owns
the decision. A `not_started` or `failed` status with `can_retry: true` is what
the dashboard's "Verify My Identity" button acts on.

The same block is returned inside `GET /profile` under `verification.ai`.

---

## Payments

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| Plans | GET | `/payments/plans` | None |
| Checkout | POST | `/payments/checkout` | Stripe, EasyPaisa, or JazzCash payload |
| Payment history | GET | `/payments/history` | Query filters optional |
| Invoice | GET | `/payments/invoices/{payment}` | None |
| Validate coupon | POST | `/payments/coupons/validate` | `package_id`, `code` |

Payment behavior:

- `stripe`: returns checkout instructions and stays `Due` until server-side Stripe confirmation marks payment paid.
- `easypaisa`: creates a pending payment with instructions/admin credentials from settings. Package activates only after gateway callback or admin approval.
- `jazzcash`: creates a pending payment with instructions/admin credentials from settings. Package activates only after gateway callback or admin approval.
- Gateway callback/webhook endpoints are backend-only and intentionally excluded from the app OpenAPI/Postman docs.

Stripe:

```json
{
  "package_id": 2,
  "gateway": "stripe",
  "currency": "PKR",
  "coupon_code": "WELCOME20"
}
```

EasyPaisa:

```json
{
  "package_id": 2,
  "gateway": "easypaisa",
  "currency": "PKR",
  "easypaisa_phone": "03451234567"
}
```

JazzCash:

```json
{
  "package_id": 2,
  "gateway": "jazzcash",
  "currency": "PKR",
  "jazzcash_phone": "03001234567"
}
```

Webhook endpoints exist for gateways, but the app should not call them.

Coupon validation sample:

```json
{
  "package_id": 2,
  "code": "WELCOME20"
}
```

## Notifications

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| List notifications | GET | `/notifications` | None |
| Unread count | GET | `/notifications/unread-count` | None |
| Mark all read | POST | `/notifications/mark-all-read` | None |
| Mark notification read | POST | `/notifications/{notification}/read` | None |
| Preferences | GET | `/notifications/preferences` | None |
| Update preferences | PATCH | `/notifications/preferences` | Preference fields |
| Register push token | POST | `/notifications/push-tokens` | `token`, `device_type` |
| Delete push token | DELETE | `/notifications/push-tokens/{token}` | None |

```json
{
  "token": "fcm-token",
  "device_type": "android"
}
```

## Safety

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| Report user | POST | `/safety/report` | `user_id`, `reason`, `severity` |
| Block user | POST | `/safety/block` | `user_id`, `reason` optional |
| Mute user | POST | `/safety/mute` | `user_id`, `reason` optional |
| Restrict user | POST | `/safety/restrict` | `user_id`, `reason` optional |

```json
{
  "user_id": 25,
  "reason": "Asked for money and private bank details.",
  "severity": "high"
}
```

## AI

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| Bio generator | POST | `/ai/bio` | `text` |
| Conversation starters | POST | `/ai/conversation-starters` | `matched_user_id` |
| Profile quality | POST | `/ai/profile-quality` | `text` |
| Scam check | POST | `/ai/scam-check` | `text` |
| Red flag check | POST | `/ai/red-flag-check` | `text` |

```json
{
  "text": "I value faith, family, and simple living."
}
```

## Family

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| Family dashboard | GET | `/family/dashboard` | Optional `profile_user_id` query |
| Guardians | GET | `/family/guardians` | None |
| Invite guardian | POST | `/family/guardians` | `guardian_user_id`, `relationship`, `permissions` |
| Update guardian settings | PATCH | `/family/guardians/{guardian}` | Role, wali, digest, permissions |
| Enable Wali/Mehram mode | POST | `/family/wali-mode` | `enabled` |
| Managed profiles | GET | `/family/managed-profiles` | None |
| Approval requests | GET | `/family/approval-requests` | None |
| Create approval request | POST | `/family/approval-requests` | Request fields |
| Private notes | GET | `/family/notes/{profile}` | None |
| Add private note | POST | `/family/notes` | `profile_id`, `note` |
| Family conversations | GET | `/family/conversations` | None |
| Start family conversation | POST | `/family/conversations` | `proposal_id`, `profile_user_id`, `message` |
| Family messages | GET | `/family/conversations/{conversation}/messages` | None |
| Send family message | POST | `/family/conversations/{conversation}/messages` | `message`, `attachments` |
| Guardian digest preview | GET | `/family/digest/preview` | None |

```json
{
  "guardian_user_id": 45,
  "relationship": "father",
  "guardian_role": "father",
  "is_wali": true,
  "digest_frequency": "weekly",
  "permissions": ["approve_matches", "view_notes", "view_matches", "join_meetings"]
}
```

Wali mode sample:

```json
{
  "enabled": true
}
```

Family conversation sample:

```json
{
  "proposal_id": 101,
  "profile_user_id": 25,
  "message": "Assalamualaikum, our family would like to arrange an introduction call."
}
```

## Content And Community

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| Blog/articles | GET | `/content/articles` | Query `q`, `per_page` optional |
| Article details | GET | `/content/articles/{slug}` | None |
| Success stories wall | GET | `/content/success-stories` | None |
| Submit success story | POST | `/content/success-stories` | Story payload |
| Relationship advice | GET | `/content/advice` | Query `category` optional |
| Expert Q&A | GET | `/content/expert/questions` | None |
| Ask expert | POST | `/content/expert/questions` | Question payload |
| Forums | GET | `/content/forums` | None |
| Forum threads | GET | `/content/forums/{forum}/threads` | None |
| Create thread | POST | `/content/forums/{forum}/threads` | Thread payload |
| Thread posts | GET | `/content/threads/{thread}/posts` | None |
| Reply to thread | POST | `/content/threads/{thread}/posts` | `body` |
| Webinars/live events | GET | `/content/webinars` | None |
| Register webinar | POST | `/content/webinars/{webinar}/register` | None |
| Marriage tips | GET | `/content/marriage-tips` | None |
| Regional updates | GET | `/content/regional-updates` | Query `region` optional |

Submit success story sample:

```json
{
  "title": "Alhamdulillah, we found each other",
  "story": "Our families connected through HamQadam and everything moved respectfully.",
  "partner_name": "Ahmed",
  "is_anonymous": true
}
```

Ask expert sample:

```json
{
  "category": "islamic_guidance",
  "question": "How should families approach the first meeting?",
  "details": "We want to keep the process respectful and clear.",
  "is_anonymous": true
}
```

Forum thread sample:

```json
{
  "title": "What questions are useful in a first family meeting?",
  "body": "Please share respectful questions that helped your family understand compatibility."
}
```

## App Developer Notes

- Use `/auth/me` as the only app authentication check.
- Use `/payments/plans` to show packages. Plan feature payloads include `coins`.
- Basic Free package is applied automatically after registration.
- For EasyPaisa/JazzCash, show the returned `checkout.instructions`, `account_msisdn`, `gateway_reference`, and `amount` to the user.
- Treat payment status `Due` as pending approval, not failed.
- Do not call legacy endpoints under `/api/member/...`.
- Do not call admin endpoints from the app.
