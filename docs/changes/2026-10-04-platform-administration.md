# Change record: platform administration — 2026-10-04

## Scope and source history

This release adds operator controls and operational reporting to the existing free Invoice SaaS. It includes the 42 application/test/deployment/documentation files listed below, plus the tracking documentation added for this release. Its parent is baseline commit `320c02c`. Run `git log --oneline` and `git show --stat HEAD` to see the resulting commit and exact files; the commit hash is intentionally not embedded in its own contents.

Suggested/used commit title: `feat: add platform administration and documented Hostinger upgrade`.

No remote deployment or GitHub push is performed by this commit. Generated packages are ignored by Git; source and packaging scripts are versioned.

## Behavior and access

The platform operator uses `/admin` for integration configuration, test email/provider checks, registered users, daily distinct sign-ins, activity and payment-email delivery. Access requires the server-granted platform flag; business ownership/admin roles alone are insufficient. Branding and favicon uploads are reached through **Branding & favicon upload**. New registrations stay business owners of their own workspace, with no platform privilege.

SMTP passwords, Cloudinary API keys/secrets and WhatsApp tokens are encrypted at rest with the existing APP_KEY. API responses show only configured/not-configured flags. An empty secret input keeps the previous value; entering a value replaces it; selecting Clear stored secret stores an empty override and takes precedence over a newly entered value or an environment fallback. APP_KEY/database/bootstrap/queue settings remain in `.env`.

Owner/customer emails are queued for newly recorded partial or full payments. A notice confirms a team-recorded payment, not verified gateway settlement. Recipient switches default on; past payments are not backfilled. SMTP failure does not undo payment recording. Log mode records content privately and does not deliver email. Already-sent notices are skipped on normal job reprocessing, but a crash after SMTP acceptance can still cause a duplicate on retry.

Login collection starts after installation. Reports count distinct users per Lagos day; recent presence is an API-activity estimate. Request contents, secrets, tokens, query strings and IP addresses are excluded from activity metadata. GETs update recent activity; successful changes and tracked errors create action records. Rejections before the tracking middleware and failed public login attempts are not a complete security audit trail.

Advertising is preparation only: disabled by default, confined to `/about`, and requires publisher/slot IDs, Google approval, verified consent integration and affirmative advertising consent before loading Google code. A certified CMP is not bundled. Private workspaces and signed invoices have no ad placement. About/Privacy are editable source pages; the privacy disclosure needs operator review before live monetization.

## Database effects

Migration: `2026_10_04_000004_create_platform_operations.php`.

| Object | Added data / constraints | Deletion behavior |
| --- | --- | --- |
| `integration_settings` | Singleton encrypted settings text, timestamps | Credentials are not rotated/deleted by ordinary code rollout. |
| `users.last_seen_at` | Nullable timestamp with index | Account deletion removes that account's timestamp. |
| `activity_logs` | User/business references, event template, HTTP status, creation time; time/event/user indexes | Account/business deletion nulls associations; pruning removes old metadata. |
| `payment_notifications` | Payment/business references, recipient, kind, details snapshot, status, attempts, generic error, sent time; unique payment/recipient | Deleted payment/business cascades to its notices, cancelling unsent records. |

The migration adds schema; it does not reset existing users, invoices or payments. Earlier pending branding/media migrations also run if not already recorded. Back up the configured invoice database and preserve APP_KEY. The migration's `down()` drops newly added tables/column; it is destructive to their contents and is not recommended for routine code rollback. A code rollback does not reverse schema or saved settings.

## Shared-host boundaries and deployment

Follow [ADMIN-UPDATE.md](../ADMIN-UPDATE.md). Commands use the absolute invoice Artisan path so the current SSH directory cannot select another application. PHP is just the interpreter. Migrations and platform grants operate on the database configured for that app; confirm database `u491120861_invoice` before migration. Compiled-view directories and cache stores/prefixes must also be isolated from other applications. Config/route caches belong to the invoice project; queue restart signals are scoped by the configured cache, so shared cache paths/prefixes can cause cross-app signals.

For the original cron-based installation, pause queue/reminder cron and let the worker finish before the update. Queue restart is optional in that case: new cron workers load updated code. No PHP-service restart, other-site cache purge or database reset is required. Managed deployments use shared configuration/storage and the existing release/deployment lock. Database backup, application maintenance, migration and public/private file placement are detailed in the installation guide.

## File-by-file implementation record

| File | Change and purpose |
| --- | --- |
| `PLATFORM-EDITOR.md` | Replaces obsolete URL-only logo guidance with uploaded logo/favicon controls and points to the cumulative installation guide. |
| `README.md` | Adds the admin-update entry point and tracking-document links. |
| `backend/app/Http/Controllers/AdvertisingController.php` | Validates public advertising settings; requires approval/consent readiness before enablement; emits text/plain ads.txt. |
| `backend/app/Http/Controllers/AuthController.php` | Records registration/login events and last-login/last-seen timestamps; clears recent presence on logout. |
| `backend/app/Http/Controllers/PaymentController.php` | Queues payment owner/customer notifications after a payment is recorded, within the transaction with after-commit dispatch. |
| `backend/app/Http/Controllers/PlatformOperationsController.php` | Implements masked integrations, saved-setting connection tests, SQL daily usage aggregates, global user/activity lists and payment-mail delivery/retry endpoints. |
| `backend/app/Http/Middleware/PlatformAdmin.php` | Restricts private platform APIs to the explicit is_platform_admin flag. |
| `backend/app/Http/Middleware/RuntimeIntegrations.php` | Refreshes saved integration configuration for HTTP requests. |
| `backend/app/Http/Middleware/TrackActivity.php` | Tracks recent authenticated activity and action metadata, excludes request contents and handles deleted accounts. |
| `backend/app/Jobs/SendInvoiceMessage.php` | Refreshes database provider settings in invoice jobs; removes provider exception text from persisted delivery/queue errors. |
| `backend/app/Jobs/SendPaymentEmail.php` | Sends queued snapshot-based notices with explicit business constraints, row locking, retries and sent/logged/failed status. |
| `backend/app/Models/ActivityLog.php` | Stores action/time/status and nullable account/business associations. |
| `backend/app/Models/IntegrationSetting.php` | Encrypts stored settings with APP_KEY and hides them from ordinary model serialization. |
| `backend/app/Models/PaymentNotification.php` | Casts email snapshot details and sent_at for durable delivery tracking. |
| `backend/app/Models/User.php` | Casts the new last_seen_at timestamp. |
| `backend/app/Services/AdvertisingSettings.php` | Stores validated advertising settings under platform settings without leaking private image metadata. |
| `backend/app/Services/IntegrationSettings.php` | Loads environment fallback and database overrides; masks secrets; implements keep/replace/clear behavior and runtime mail/media/WhatsApp configuration. |
| `backend/app/Services/PaymentEmails.php` | Chooses owner/customer recipients, deduplicates addresses, snapshots payment details and dispatches notices after commit. |
| `backend/bootstrap/app.php` | Registers platform/activity middleware and runtime integration configuration for API and web requests. |
| `backend/database/migrations/2026_10_04_000004_create_platform_operations.php` | Adds three operations tables and users.last_seen_at, indexes and foreign-key deletion behavior. |
| `backend/routes/api.php` | Adds admin integration/report/delivery endpoints and public advertising configuration; applies authorization, activity and test/retry throttles. |
| `backend/routes/console.php` | Adds activity pruning and its daily schedule entry; preserves server-only platform-access granting. |
| `backend/routes/web.php` | Exposes dynamic ads.txt through the Laravel backend. |
| `backend/tests/Feature/PlatformOperationsTest.php` | Covers authorization, encrypted/masked credentials, keep/clear semantics, runtime provider settings/tests, timezone counts, activity, notifications, failure/retry, ads validation and retention. |
| `docs/ADMIN-UPDATE.md` | Provides cumulative installation, database/security effects, provider setup, cron, consent integration and acceptance steps. |
| `docs/CI-CD.md` | Explains persisted runtime settings and managed activity retention across releases. |
| `docs/DELIVERY-VALIDATION.md` | Records actual checks and separates local validation from hosted/provider acceptance. |
| `frontend/e2e/production.spec.ts` | Updates the admin-link assertion and adds non-admin endpoint denial, About/no-ad-script and ads.txt checks to production smoke. |
| `frontend/src/App.vue` | Renders public information outside the authenticated shell and adds About/Privacy links. |
| `frontend/src/admin.css` | Styles admin tabs/forms/reports, responsive tables and public information/ad placement. |
| `frontend/src/components/AdPlacement.test.ts` | Tests disabled/no-consent/private-page behavior, single script loading and unmount-before-response handling. |
| `frontend/src/components/AdPlacement.vue` | Loads only the fixed Google script after verified-consent hook and readiness settings; handles revocation and lifecycle races. |
| `frontend/src/components/Navbar.vue` | Renames the operator sidebar entry to Platform admin and routes it to /admin. |
| `frontend/src/main.ts` | Loads the new admin/public-page stylesheet. |
| `frontend/src/router/index.ts` | Adds public About/Privacy and restricted admin routes; forces new-document navigation when advertising is active. |
| `frontend/src/views/About.vue` | Adds public service explanation, usage steps, FAQ and the sole reserved ad placement. |
| `frontend/src/views/Privacy.vue` | Provides initial service/privacy disclosure for operator review. |
| `frontend/src/views/settings/PlatformAdmin.test.ts` | Tests the favicon entry point and blank-secret save/presence-flag behavior. |
| `frontend/src/views/settings/PlatformAdmin.vue` | Implements operator overview, users, activity, provider forms/tests, delivery retries, ad preparation and branding/favicon navigation. |
| `ops/cron.sh` | Adds managed activity-pruning task using the existing shared deployment lock. |
| `ops/spa.htaccess` | Redirects root ads.txt to the backend text response before SPA fallback. |
| `scripts/package-admin-update.py` | Builds cumulative manual update ZIPs, checks exclusions and produces SHA-256 sidecars. |

Additional tracking files in this commit:

| File | Purpose |
| --- | --- |
| `CHANGELOG.md` | Dated feature/database/validation summary and separate baseline history. |
| `docs/changes/2026-10-04-platform-administration.md` | This release's behavior, file map, schema effects and acceptance boundaries. |
| `docs/CHANGE-TRACKING.md` | Required template/process for documenting future changes and commits. |
| `CONTRIBUTING.md` | Links the tracking process to contribution requirements. |

## Verification evidence

The implementation run recorded on 2026-10-04 passed **52 backend tests / 296 assertions** against isolated MySQL 8.4.3, **13 frontend tests**, TypeScript and the production Vite build. Workflow syntax and cron Bash syntax checks passed; cumulative archives were checked for current frontend output, migration presence, checksums and secret/runtime exclusions. These are prior implementation-run results, not a claim that documentation-only edits reran all tests.

Provider/mail tests used fakes. Real SMTP inbox delivery, Cloudinary upload/delete, WhatsApp delivery, Google approval/CMP, hosted rollout and the new GitHub Linux/Apache run remain live acceptance checks. The exact Hostinger MySQL version is unknown. The existing workflow uses MySQL 8.0; local results are not a claim of identical infrastructure.

Reproduction (PowerShell, from project root):

```powershell
cd backend
# Use a disposable database whose name begins invoice_ci; never the production DB.
$env:DB_CONNECTION='mysql'
$env:DB_HOST='127.0.0.1'
$env:DB_DATABASE='invoice_ci_YOUR_DISPOSABLE_TEST_DB'
$env:DB_USERNAME='YOUR_TEST_USER'
$env:DB_PASSWORD='YOUR_TEST_PASSWORD'
$env:EXPECT_MYSQL='1'
php vendor/bin/pest --configuration phpunit.mysql.xml --colors=never
cd ../frontend
npm ci
npm test
$env:VITE_API_URL='/backend/api'
npm run build
cd ..
python scripts/package-admin-update.py
python scripts/package-source.py
```

The backend test bootstrap guards the database-name prefix; tests reset their disposable database. CI additionally exercises the packaged build under Apache. Follow the guide's live acceptance checklist after deploying.

## Artifacts and rollback

- `deployment/invoice-admin-backend-update.zip`: cumulative application files; no `.env`, vendor, storage or customer data.
- `deployment/invoice-admin-frontend-update.zip`: compiled index/assets, routing and release metadata; keep the public backend gateway.
- `deployment/invoice-saas-github-ready.zip`: source without installed dependencies, secrets or deployment output.

Rebuild using the commands above; SHA-256 sidecars accompany manual update ZIPs. Keep the preceding public/private code backup and database snapshot. Do not regenerate APP_KEY, rerun seeders or use migrate:fresh. Restoring source alone does not restore the database. Manual installation and versioned CI installation are separate paths; do not overwrite an adopted managed release with legacy-folder ZIPs.
