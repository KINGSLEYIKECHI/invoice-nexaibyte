# Workspace modes and reporting modules — 2026-10-21

This is the current update installation guide. Use invoice-workspace-modules-backend-update.zip and invoice-workspace-modules-frontend-update.zip. They are cumulative, including earlier settings, numbering and light-PDF changes. For code/workflow scope see [Paypod review](PAYPOD-MODULE-REVIEW.md); broader Paypod accounting/purchasing tools remain future work, not selectable placeholders.

## User experience

Open **My modules** in the sidebar or the mode button in the header. Simple mode shows the everyday core plus quotations; Advanced shows every implemented module permitted for your role. Check/uncheck individual modules for a Custom layout and Save my modules. You may turn all optional modules off. Overview, invoices, clients, the chooser and role-permitted settings remain accessible.

Selectable modules: quotations, delivery notes, products/services, team (owners/admins), client statements and due/overdue reports. Choices persist in your user account across devices and sign-ins. Existing users remain Advanced after migration. New registrations and new team members start Simple. Other teammates' choices and roles are unchanged. Mobile navigation scrolls within its own row and keeps sign-out accessible.

Hidden module routes lead back to My modules, and the product picker/delivery-note action follows your selection. Hiding a module does not delete products/documents, revoke API permissions, stop cron jobs or cancel previously scheduled work. This is interface customization, not a security/feature-entitlement mechanism. Server authorization remains authoritative; selecting Team never grants staff management permissions. You can enable tools again anytime.

## Statements and ageing

Statements require a client and a date range, with an optional ISO currency filter. Opening balance includes issued invoices before From minus payments recorded before From. Period activity includes invoices and payments within the range; payments after To are excluded. A payment before a later invoice issue date appears as client credit without prematurely counting that invoice; closing balances can therefore be negative. Running and closing balances are shown separately per currency/decimal precision. Ageing uses invoice due dates and payments through the selected As of date; bands are not overdue, 1–30, 31–60, 61–90 and over 90 days. Mixed currencies are never added together.

Download CSV after generating a report; the download uses the last generated filters. CSV amounts are integer minor units with currency/decimal-precision columns, and formula-like text is neutralized for spreadsheets. Reports are bounded to 5000 invoices and 10000 payment events per report (checked before loading payment rows), with safe-integer checks; narrow filters if the limit is reached. Only active clients appear in the selector; a known archived client ID remains supported by the API.

These reports reflect currently retained records and current void/draft status, not an immutable accounting history. Deleted payments and later voids cannot be reconstructed as of an earlier date. No credit-note, general-ledger, VAT, balance-sheet, bank reconciliation or signature workflow is introduced. Statement PDF/email dispatch is not included; these reports are on-screen and CSV. No new report cron is needed.

## Database and compatibility

Migration 2026_10_21_000008_add_user_workspace_preferences adds users.workspace_mode (default advanced) and nullable enabled_modules JSON. It does not rewrite invoices, counters, amounts, currency, bank details, clients or business settings. Registration/team creation explicitly starts new users simple. Existing records and permissions remain. Rolling back this migration deletes saved interface preferences; code rollback can leave the additive columns in place.

New authenticated API: GET/PUT /api/workspace/preferences. PUT accepts mode simple/advanced/custom and enabled_modules (array, including empty). Presets are canonical; custom lists must contain distinct known module IDs available to the user's role. User IDs and privilege fields are ignored. No administrator may target another user through this endpoint.

New authenticated read-only reports: GET /api/reports/statement and /api/reports/ageing. Optional format=csv downloads. Reports retain original tenant and role boundaries independent of presentation settings.

## Hostinger installation: original layout

Confirm this exact private backend and dedicated invoice database first:

```sh
ls /home/u491120861/invoice-backend/artisan
```

Verify DB_DATABASE=u491120861_invoice and dedicated invoice credentials in the private .env. Back up that database, backend, frontend, uploaded storage and private .env. Preserve APP_KEY. Pause only this invoice application's queue/reminder cron entries and let its worker finish.

Enter maintenance mode before replacing files:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan down --no-ansi
```

Extract invoice-workspace-modules-backend-update.zip into /home/u491120861/invoice-backend. Preserve .env, vendor and storage. No new Composer dependency is required. Run only this application's commands:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan config:clear --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan migrate --force --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan route:clear --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan view:clear --no-ansi
```

Extract invoice-workspace-modules-frontend-update.zip into /home/u491120861/domains/nexaibyte.com/public_html/invoice, keeping its backend gateway. Ensure the app's cache/view configuration remains isolated as documented in ADMIN-UPDATE.md. Pending migrations from older cumulative updates also run; review their guides if you skipped releases. Do not seed demo users or run migrate:fresh on production.

After both packages and migrations succeed, exit maintenance mode:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan up --no-ansi
```

Check https://invoice.nexaibyte.com/backend/up and sign in. Existing users should see Advanced mode; save a Custom layout, reload and confirm it remains. Check a second user is unaffected, staff cannot manage the team, and report currencies/date filters are correct. Resume only this app's paused cron entries. No environment, queue service or cron schedule changes are required. Do not restart shared PHP services or other sites.

If installation fails, keep maintenance enabled while diagnosing or restoring known-good files. Do not blindly reverse schema or discard live financial data. Bring the app back with the same scoped up command only after it is ready. Code rollback does not automatically undo data/schema changes.

Managed CI-release installations must use their release mechanism and verified active Artisan path rather than overwriting releases. Exact-host automation isolation and first production deployment verification remain separate work; this update does not enable AUTO_DEPLOY or directly deploy to Hostinger.

## Reproducing and accepting the release

Build frontend with VITE_API_URL=/backend/api, then run python scripts/package-workspace-modules-update.py and python scripts/package-source.py. Deployment ZIPs exclude credentials, dependencies and runtime/customer storage and include SHA-256 sidecars. The backend ZIP includes this guide and the scope review. No Paypod credential or session data is included.

Validate custom selection and preset changes, re-enable hidden tools, test a second account, and compare a dated client statement/ageing report against known invoices/payments in a disposable workspace. Never use production invoice counters for test transactions. See docs/changes/2026-10-21-workspace-modules-reports.md for actual verification and remaining limitations.
