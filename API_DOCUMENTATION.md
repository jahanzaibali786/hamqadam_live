# HamQadam App API v1

Curated API documentation for the Flutter mobile app.

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
| Profile | `/profile`, `/profiles/{profile}`, `/profile-views`, `/profile-views/received`, `/profile-views/balance`, `/profile/privacy`, `/profile/visibility`, `/partner-preferences`, `/profile/dropdown-reference-data` |
| Discovery | `/search/profiles`, `/search/saved`, `/search/history`, `/matches`, `/matches/recommended`, `/matches/daily`, `/profiles/{profile}/compatibility` |
| Proposals | `/proposals`, `/proposals/{proposal}/accept`, `/proposals/{proposal}/reject`, `/proposals/favourites`, `/proposals/shortlists`, `/proposals/ignored` |
| Chat | `/chat/threads`, `/chat/threads/{thread}/messages`, `/chat/threads/{thread}/typing`, `/chat/threads/{thread}/block`, `/chat/threads/{thread}/unblock`, `/chat/threads/{thread}/clear`, `/chat/threads/{thread}/report`, `/chat/messages/{message}` |
| Interests | `/interests`, `/interests/sent`, `/interests/received`, `/interests/coin-balance`, `/interests/{interest}/accept`, `/interests/{interest}/reject` |
| Packages & Payments | `/payments/plans`, `/payments/current`, `/payments/packages/{package}`, `/payments/usage`, `/payments/checkout`, `/payments/history`, `/payments/invoices/{payment}`, `/payments/coupons/validate` |
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

The mobile app handles all 18 registration steps locally. Submit the complete data in one request, then verify via email OTP.

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

The mobile app handles all 18 registration steps locally and submits the complete payload in one request. Mandatory steps: 1-14, 17, 18. Optional steps: 15, 16.

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

The legacy step-wise endpoints remain available for backward compatibility. New mobile apps should use `/auth/register/complete` with the OTP flow.

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

### Profile views

Profile views now use the same package allowance as the website. Opening a profile via `GET /profiles/{profile}` or `POST /profile-views/{profile}` will spend one `remaining_profile_viewer_view` coin when the member has an active package and an unused view.

#### GET /profile-views

Returns the profiles the current member has viewed.

Query parameters:
- per_page (integer, optional, default 20)

#### GET /profile-views/received

Returns the members who viewed the current user.

Query parameters:
- per_page (integer, optional, default 20)

#### GET /profile-views/balance

Returns the current profile-view balance, the active package, and the remaining count.

#### POST /profile-views/{profile}

Consumes one profile-view allowance and returns the public profile payload.

Path parameters:
- profile (integer, required)

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
| List shortlists | GET | `/proposals/shortlists` | None |
| Add shortlist | POST | `/proposals/shortlists` | `user_id` | Accepted interest required; costs shortlist coins. |
| Check shortlist | GET | `/proposals/shortlists/{user}/check` | None |
| Remove shortlist | DELETE | `/proposals/shortlists/{user}` | None |
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

## Chat (Mobile API)
Realtime chat is powered by the mobile REST endpoints below and broadcasts updates over Pusher.

Broadcast channels
- `App.User.{userId}` for inbox, unread-count, sidebar badge, and preview updates
- `chat-thread.{threadId}` for the active conversation stream

| Feature | Method | Endpoint | Payload | Notes |
|---|---:|---|---|---|
| Chat threads | GET | `/api/v1/chat/threads` | `per_page` (optional) | Returns the authenticated user's conversation list. |
| Thread messages | GET | `/api/v1/chat/threads/{thread}/messages` | `per_page` (optional) | Paginates one conversation thread. |
| Send message | POST | `/api/v1/chat/threads/{thread}/messages` | `message`, `message_type`, `reply_to_chat_id`, `attachments[]` | Creates the message and broadcasts it in realtime. |
| Typing indicator | POST | `/api/v1/chat/threads/{thread}/typing` | None | Emits realtime typing state. |
| Block chat | POST | `/api/v1/chat/threads/{thread}/block` | None | Blocks the thread for the authenticated user. |
| Unblock chat | POST | `/api/v1/chat/threads/{thread}/unblock` | None | Restores chat access for the user who blocked it. |
| Clear chat | POST | `/api/v1/chat/threads/{thread}/clear` | None | Hides the thread history from the authenticated user's side only. |
| Report chat | POST | `/api/v1/chat/threads/{thread}/report` | `reason` | Reports the thread, writes a moderation record, and blocks the thread from the reporter's side. |
| Delete message for me | DELETE | `/api/v1/chat/messages/{message}` | None | Hides the message from the authenticated user's view only. |

Sample request bodies

Send message:
```json
{
  "message": "Assalamualaikum, how are you?",
  "message_type": "text",
  "reply_to_chat_id": null,
  "attachments": []
}
```

Report chat:
```json
{
  "reason": "Unwanted messages"
}
```

Attachment payload
The chat message response includes attachment metadata so the mobile app can preview and download without refreshing the conversation.
```json
{
  "id": 40,
  "thread_id": 16,
  "message": "Hi",
  "attachments": [
    {
      "id": 812,
      "name": "contract.pdf",
      "original_name": "contract.pdf",
      "type": "file",
      "url": "https://example.com/uploads/contract.pdf",
      "download_url": "https://example.com/aiz-uploader/download/812",
      "preview_url": null,
      "size": 245671
    }
  ]
}

Realtime behavior
- A successful send updates the sender and receiver inbox badges immediately.
- When the active thread is open, incoming messages append locally without page refresh.
- Read events clear the unread badge and keep the member sidebar in sync.
- Block, unblock, clear, delete, and report actions should refresh the thread state from the API response so the app can re-render the list, preview, and composer state immediately.
- The mobile app should treat the API response and the broadcast payload as the same source of truth for message content.

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
| Run verification now (mobile alias) | POST | `/auth/register/ai-verification/run` | None (throttle 3/min) |

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

### Verification in the profile endpoints

| Endpoint | What it carries |
|---|---|
| `GET /profile` | Full block: `verification.status` + `verification.ai` (status, recommendation, attempts, verified_at, last_attempt_at) |
| `PUT /profile` | Same as above - it returns the profile resource |
| `PATCH /profile/visibility` | Same as above |
| `GET /profiles/{profile}` | **Badge only**: `verification.identity_verified` and `verification.verified_at` |
| `GET /search/profiles`, `/matches`, proposal listings | Same badge on every row |
| `PATCH /profile/privacy` | Nothing - it returns privacy settings only, by design |
| `GET /profiles/{profile}/compatibility` | Nothing - it is a scoring endpoint |

Another member's profile deliberately exposes only a boolean and a date. The
recommendation, attempt count, fraud score and last error are the owner's
business; a viewer has no need to know that somebody failed verification three
times or why.

`identity_verified` is true when **either** path succeeded - a moderator
approved the documents, or the model returned APPROVE. `verified_at` is only
populated for the AI path; the moderator path keeps its timestamp on the
verification request, and reading it per row would add a query to every search
result, so a moderator-verified member can read `true` with a null date.

### Where verification fires, per flow

| Flow | Trigger | Blocking? |
|---|---|---|
| `POST /auth/register` | after response | No |
| `POST /auth/register/complete` | after response | No |
| `POST /auth/register/step/13` (Identity Verification) | after response | No |
| `POST /api/signup` (legacy) | after response | No |
| `POST /verification/ai/run` | synchronous | Yes, by design |
| `POST /auth/register/ai-verification/run` | synchronous | Yes, by design |

Registration itself is never blocked. Step 13 stores the CNIC front, CNIC back
and selfie, so from that point on the model performs a real identity comparison
rather than only inspecting the profile photo - confirmed by
`images_sent: ["cnic_image","live_selfie","profile_image"]` in
`/verification/ai/history`.

### Recommended app sequence

1. Complete registration. The response carries an `ai_verification` block with
   `status: "pending"` plus `status_url` and `retry_url`.
2. Show a "verifying" screen and poll `GET /verification/ai/status`.
3. `status: "approved"` -> continue into the app.
4. Anything else -> the account is registered but unverified. Send the user on,
   and surface a retry that calls `POST /verification/ai/run` or the alias `POST /auth/register/ai-verification/run`. `can_retry` tells
   you whether to show it.
---|---|---|
| `POST /auth/register` | after response | No |
| `POST /auth/register/complete` | after response | No |
| `POST /auth/register/step/13` (Identity Verification) | after response | No |
| `POST /api/signup` (legacy) | after response | No |
| Web `POST /register` | redirect to the identity gate, which runs it synchronously | No - the account is created first |
| `POST /verification/submit` | after response | No |
| `POST /verification/ai/run` | synchronous | Yes, by design |\n| `POST /auth/register/ai-verification/run` | synchronous | Yes, by design |

Registration itself is never blocked. Step 13 stores the CNIC front, CNIC back
and selfie, so from that point on the model performs a real identity comparison
rather than only inspecting the profile photo - confirmed by
`images_sent: ["cnic_image","live_selfie","profile_image"]` in
`/verification/ai/history`.

### Recommended app sequence

1. Complete registration. The response carries an `ai_verification` block with
   `status: "pending"` plus `status_url` and `retry_url`.
2. Show a "verifying" screen and poll `GET /verification/ai/status`.
3. `status: "approved"` -> continue into the app.
4. Anything else -> the account is registered but unverified. Send the user on,
   and surface a retry that calls `POST /verification/ai/run`. `can_retry` tells
   you whether to show it.

The web does exactly this: after registration the member lands on
`/register/ai-verification`, which waits for the model, sends verified members to
the dashboard and everyone else to login with the account intact and the
dashboard's verification button available.

---

## Payments

| Feature | Method | Endpoint | Payload |
|---|---:|---|---|
| Plans | GET | `/payments/plans` | None |
| Current package | GET | `/payments/current` | None |
| Package details | GET | `/payments/packages/{package}` | None |
| Package usage | GET | `/payments/usage` | Optional `feature` and `per_page` |
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

## Mobile API Summary
This section is the quick reference for the Flutter app and the web client. The same resources power both frontends, so the payloads and responses below are the shared contract.
### Auth and Onboarding
Use: sign up, sign in, verify email/OTP, device sessions, and account shutdown.
| Endpoint group | Key endpoints | Payload | Success response |
|---|---|---|---|
| Registration | `/auth/register/complete`, `/auth/register/request-otp`, `/auth/register/verify-otp` | Full 18-step payload, then `email` and `code` | `token`, `user`, `registration.completed_steps`, `registration.next_step`, `registration_completed` |
| Login | `/auth/login/email`, `/auth/login/mobile`, `/auth/login/google` | `email/password`, `phone/country_code/otp`, or `id_token` | `token`, `token_type`, `expires_at`, `user`, `device_session` |
| Session | `/auth/me`, `/auth/devices`, `/auth/logout`, `/auth/logout/all`, `/auth/account` | None or device id | Current user, active sessions, or `success: true` |
| Recovery | `/auth/forgot-password`, `/auth/reset-password`, `/auth/email/verification-code`, `/auth/email/verify` | `email`, `otp`, `password`, `password_confirmation` | `success`, `message`, `expires_at` or user state |
Typical response:
```json
{
  "success": true,
  "message": "Registration submitted successfully.",
  "data": {
    "token": "1|abc123...",
    "user": { "id": 101, "code": "20260899", "name": "Ayesha Khan" },
    "registration": {
      "total_steps": 18,
      "completed_steps": ["step1", "step2"],
      "next_step": "step3",
      "registration_completed": false
    }
  },
  "errors": null
}
```
### Reference Data
Use: populate registration and filter dropdowns from the backend instead of hardcoding them in the app.
| Endpoint | Payload | Response |
|---|---|---|
| `/profile/dropdown-reference-data` | None | Countries, states, cities, areas, religions, sects, castes, sub-castes, languages, education levels, degrees, fields of study, institutions, professions, hobbies, and fixed option lists |
Typical response:
```json
{
  "success": true,
  "data": {
    "countries": [{ "id": 166, "name": "Pakistan" }],
    "states": [{ "id": 2728, "country_id": 166, "name": "Punjab" }],
    "cities": [{ "id": 85568, "state_id": 2728, "name": "Lahore" }],
    "religions": [{ "id": 1, "name": "Islam" }]
  },
  "errors": null
}
```
### Profile and Preferences
Use: load the signed-in member profile, public profile cards, partner preferences, and compatibility preview.
| Endpoint group | Key endpoints | Payload | Success response |
|---|---|---|---|
| Own profile | `/profile`, `/profile/privacy`, `/profile/visibility`, `/profile/deactivate` | Profile form, privacy flags, visibility flags | Full `ProfileResource` with `user`, `member`, `photos`, `verification`, `registration`, `privacy` |
| Public profile | `/profiles/{profile}`, `/profiles/{profile}/compatibility` | None | Public profile card, compatibility percentage, explanation, score breakdown |
| Partner preferences | `/partner-preferences` | Age/height ranges, religion, caste, languages, location, profession, income, lifestyle | Stored preference set or empty defaults |
Typical response:
```json
{
  "success": true,
  "data": {
    "user": { "id": 101, "name": "Ayesha Khan" },
    "member": { "gender": 2, "current_package_id": 1, "coin_balance": 8 },
    "photos": { "profile_photo": "https://...", "gallery": ["https://..."] },
    "verification": { "status": "verified", "ai": { "status": "approved" } },
    "registration": { "completion_percentage": 100, "steps": ["step1", "step2"] }
  },
  "errors": null
}
```
### Search and Matching
Use: browse profiles, filter search results, save searches, and read AI/rule-based match explanations.
| Endpoint group | Key endpoints | Payload | Success response |
|---|---|---|---|
| Search | `/search/profiles`, `/search/history`, `/search/saved`, `/search/hidden-users` | Search filters and pagination | Paged profile collection with `total`, `current_page`, `data[]` |
| Matches | `/matches`, `/matches/recommended`, `/matches/daily`, `/matches/recalculate`, `/matches/feedback` | Match filters or feedback | Match list with `compatibility_percentage`, `compatibility_explanation`, `score_breakdown` |
### Proposals, Favorites, and Interests
Use: send interest/proposal, approve or reject, save favorites, and manage the coin-based interest flow.
| Endpoint group | Key endpoints | Payload | Success response |
|---|---|---|---|
| Proposals | `/proposals`, `/proposals/{proposal}/accept`, `/proposals/{proposal}/reject`, `/proposals/{proposal}/withdraw`, `/proposals/{proposal}/cancel`, `/proposals/{proposal}/notes`, `/proposals/{proposal}/timeline`, `/proposals/{proposal}/meetings` | Proposal action, note text, meeting fields | `ProposalResource` with `status`, `sender`, `recipient`, `notes`, `timeline`, `expires_at` |
| Favorites | `/proposals/favourites`, `/proposals/favourites/{user}/check`, `/proposals/favourites/{user}` | User id | Favorite state or removal success |
| Shortlists | `/proposals/shortlists`, `/proposals/shortlists/{user}/check`, `/proposals/shortlists/{user}` | User id | Shortlist list/state or removal success; requires accepted interest and coin balance |
| Ignored | `/proposals/ignored`, `/proposals/ignored/{user}` | User id | Ignored state or removal success |
| Interests | `/interests`, `/interests/sent`, `/interests/received`, `/interests/coin-balance`, `/interests/{interest}/accept`, `/interests/{interest}/reject` | `user_id`, `initial_note` | Interest status plus `remaining_interest` coin balance |
Typical proposal response:
```json
{
  "success": true,
  "data": {
    "id": 55,
    "status": "pending",
    "status_value": 1,
    "initial_note": "Assalamualaikum",
    "compatibility_percentage": 82,
    "expires_at": "2026-08-28T10:00:00Z"
  },
  "errors": null
}
```
### Realtime Chat
Use: load threads, open a conversation, send messages, upload attachments, and keep the sidebar badges synced with Pusher.
| Method | Endpoint | Payload type | Sample payload | Success response |
|---|---|---|---|---|
| GET | `/chat/threads` | Query params | `?page=1` | `ChatThreadResource` collection with `other_user`, `unread_count`, `last_message` |
| GET | `/chat/threads/{thread}/messages` | Query params | `?page=1` | `ChatMessageResource` collection with `sender`, `message_type`, `attachments`, `read_at`, `seen` |
| POST | `/chat/threads/{thread}/messages` | JSON + multipart form-data | JSON: `{ "message": "Hi", "message_type": "text", "reply_to_id": null, "attachments": [] }` or multipart with `message` and one or more `attachments[]` files | Broadcast payload and API response use the same message resource |
| POST | `/chat/threads/{thread}/typing` | JSON | `{ "is_typing": true }` | Typing event payload |
| POST | `/chat/threads/{thread}/block` | No body | `{}` | `success: true` or blocked status |
| POST | `/chat/threads/{thread}/unblock` | No body | `{}` | `success: true` or unblocked status |
| POST | `/chat/threads/{thread}/report` | JSON | `{ "reason": "spam", "details": "User shared fake details." }` | Moderation report created |
| DELETE | `/chat/messages/{message}` | No body | `{}` | `success: true` |
Typical message response:
```json
{
  "success": true,
  "data": {
    "id": 40,
    "thread_id": 16,
    "message": "Hi",
    "message_type": "text",
    "attachments": [
      {
        "id": 812,
        "name": "contract.pdf",
        "url": "https://...",
        "download_url": "https://.../aiz-uploader/download/812"
      }
    ],
    "seen": false,
    "read_at": null
  },
  "errors": null
}
```
### Verification and Trust
Use: submit CNIC/selfie, check verification status, and run the AI verification model when needed.
| Endpoint group | Key endpoints | Payload | Success response |
|---|---|---|---|
| Verification requests | `/verification/current`, `/verification/history`, `/verification/submit` | CNIC fields and uploaded documents | `VerificationRequestResource` with `status`, `face_match_status`, `documents`, `submitted_at` |
| AI verification | `/verification/ai/status`, `/verification/ai/history`, `/verification/ai/run`, `/auth/register/ai-verification/run` | None | AI status object with `status`, `recommendation`, `attempts`, `can_retry` |
### Payments and Packages
Use: show plans, show the active package, inspect usage, start checkout, inspect invoices, and track package history.
| Endpoint group | Key endpoints | Payload | Success response |
|---|---|---|---|
| Plans | `/payments/plans` | None | `PlanResource` collection with package limits, duration, coins, and price |
| Current package | `/payments/current` | None | Current package info with remaining entitlements |
| Package details | `/payments/packages/{package}` | None | Single `PlanResource` with package limits |
| Usage | `/payments/usage` | Optional `feature` and `per_page` | Paginated usage history with summary totals |
| Checkout | `/payments/checkout` | `plan/package_id`, gateway fields, coupon code | `PaymentResource` with invoice and gateway details |
| History | `/payments/history`, `/payments/invoices/{payment}` | Optional filters | Paginated payment list or invoice detail |
| Coupons | `/payments/coupons/validate` | `package_id`, `code` | Coupon validation status and discount |
Typical payment response:
```json
{
  "success": true,
  "data": {
    "id": 77,
    "payment_code": "PAY-20260826-001",
    "payment_status": "Due",
    "gateway_reference": "TXN-9981",
    "amount": 2500,
    "payable_amount": 2000,
    "currency": "PKR"
  },
  "errors": null
}
```
### Notifications
Use: show in-app notifications, update read state, and register push tokens for FCM.
| Endpoint group | Key endpoints | Payload | Success response |
|---|---|---|---|
| Notifications | `/notifications`, `/notifications/unread-count`, `/notifications/mark-all-read`, `/notifications/{notification}/read` | None | `NotificationResource` collection with `type`, `title`, `message`, `deep_link`, `read_at` |
| Preferences | `/notifications/preferences`, `/notifications/push-tokens` | Notification toggles, FCM token, device type | Preference resource or token registration success |
### Safety and Moderation
Use: report abuse, block or mute users, and inspect moderation cases where supported.
| Endpoint group | Key endpoints | Payload | Success response |
|---|---|---|---|
| User safety | `/safety/report`, `/safety/block`, `/safety/mute`, `/safety/restrict` | `user_id`, `reason`, `severity` | Moderation action success |
| Moderation queue | `/safety/moderation-cases`, `/safety/moderation-cases/{case}/resolve` | Resolution payload | Moderation case list or resolved case |
### AI Helpers
Use: generate bios, conversation starters, profile quality checks, scam checks, and red-flag scoring.
| Endpoint group | Key endpoints | Payload | Success response |
|---|---|---|---|
| AI tools | `/ai/bio`, `/ai/conversation-starters`, `/ai/profile-quality`, `/ai/scam-check`, `/ai/red-flag-check` | Prompt text or matched user id | AI result object with `status`, `score`, `insights`, `suggestions` |
### Family and Guardian
Use: manage parents/guardians, wali mode, family approvals, and family-to-family messaging.
| Endpoint group | Key endpoints | Payload | Success response |
|---|---|---|---|
| Guardians | `/family/dashboard`, `/family/guardians`, `/family/guardians/{guardian}`, `/family/guardians/{guardian}/approve`, `/family/wali-mode` | Guardian and permission fields | Guardian list, updated guardian resource, wali mode flag |
| Approvals and notes | `/family/approval-requests`, `/family/approval-requests/{approval}/decision`, `/family/notes`, `/family/notes/{profile}` | Decision/note fields | Approval resource or note resource |
| Family chat | `/family/conversations`, `/family/conversations/{conversation}/messages`, `/family/digest/preview` | Conversation and message fields | Conversation list, message list, digest preview |
### Content and Community
Use: browse blog posts, success stories, advice, expert Q&A, forums, webinars, tips, and regional updates.
| Endpoint group | Key endpoints | Payload | Success response |
|---|---|---|---|
| Content feed | `/content/articles`, `/content/articles/{slug}`, `/content/success-stories`, `/content/advice`, `/content/expert/questions`, `/content/forums`, `/content/webinars` | Query filters or content payloads | Paginated collections or detail resources |
Typical content response:
```json
{
  "success": true,
  "data": {
    "items": [],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 0
    }
  },
  "errors": null
}
```
### Response Rules
- All endpoints return JSON.
- Authenticated endpoints require `Authorization: Bearer {{token}}`.
- Validation errors return `success: false` with a field-level `errors` map.
- Realtime chat endpoints also emit Pusher events so the web sidebar and mobile inbox stay in sync.
- The mobile app should treat the API response as the primary source of truth and use the broadcast event as the realtime mirror.

## App Developer Notes

- Use `/auth/me` as the only app authentication check.
- Use `/payments/plans` to show packages and `/payments/current` to show the active package and remaining limits. Plan feature payloads include `coins`.
- Basic Free package is applied automatically after registration.
- For EasyPaisa/JazzCash, show the returned `checkout.instructions`, `account_msisdn`, `gateway_reference`, and `amount` to the user.
- Treat payment status `Due` as pending approval, not failed.
- Do not call legacy endpoints under `/api/member/...`.
- Do not call admin endpoints from the app.


