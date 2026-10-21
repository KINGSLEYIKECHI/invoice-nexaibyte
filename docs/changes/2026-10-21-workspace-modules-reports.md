# User-selected workspace modules and operational reports — 2026-10-21

## Behavior and boundaries

Each user chooses Simple, Advanced or individual Custom modules at /modules. Existing users remain Advanced; registrations and new team members start Simple. Core invoices/clients/overview and role-permitted settings remain available. Module navigation, direct-route redirects and document catalogue/delivery actions follow the choice without deleting records, stopping scheduled work or expanding permissions. Role/tenant checks remain server-side. Mobile navigation is contained and sign-out remains accessible.

Added client statement and ageing modules with separate per-currency balances, date cutoffs, pre-invoice credit handling, CSV formula neutralization, safe precision and bounded invoice/payment counts. Reports reflect currently retained issued invoice/payment records; no immutable ledger or historical reversal reconstruction is claimed. CSV amounts use integer minor units and currency precision.

Paypod review was read-only after the user signed in directly. No credentials, session state, private account identifiers, imported financial data or copied branding is included. Not full Paypod parity: proformas, purchasing/bills, credit notes, recurrence, double-entry accounting/VAT, bank import/reconciliation and signatures remain unimplemented. See PAYPOD-MODULE-REVIEW.md for inventory and prerequisites. Only working modules appear in the selector.

## Schema / installation

2026_10_21_000008 adds users.workspace_mode (default advanced) and nullable users.enabled_modules JSON. No invoice/business financial values change. Reversing migration drops saved interface preferences. No new external provider, package, environment variable or cron job. WORKSPACE-MODULES-UPDATE.md directly includes scoped Hostinger down/up, backup, worker pause/resume, migration, cache and failure recovery steps. Cumulative ZIPs and source ZIP are reproduced by package-workspace-modules-update.py and package-source.py.

## Actual validation

- Full isolated-MySQL regression: 72 tests / 453 assertions passed.
- Final focused reports after adding bounds/default-date/pre-invoice cases: 6 tests / 40 assertions passed. These overlap existing report checks and must not be added to the full-suite count as independent tests.
- Frontend: 22 Vitest tests passed with --maxWorkers=1; TypeScript and Vite production build passed with /backend/api.
- Headless Edge compiled-UI test with mocked APIs: one test passed, covering presets, custom checkbox save, reload persistence, hidden-route handling, statement display, 390px horizontal containment and visible mobile logout. Desktop/mobile screenshots visually reviewed. This does not claim the hosted API was exercised by the browser test; the report APIs were verified separately against MySQL.
- Existing production-mirror E2E scenario updated for registration's Simple default. GitHub Linux production-mirror execution, actual Hostinger rollout, real SMTP/providers and all future Paypod module workflows remain unverified.
- Git whitespace checks and package contents/checksums verified before commit. No direct server deployment performed; commit/push follow the owner's standing instruction.

## File-level changes

- `CHANGELOG.md` — Document current install instructions, validation, behavior, reviewed scope or release history.
- `README.md` — Document current install instructions, validation, behavior, reviewed scope or release history.
- `backend/app/Http/Controllers/AuthController.php` — Start newly registered/invited users Simple without accepting client privilege fields.
- `backend/app/Http/Controllers/BusinessReportController.php` — Tenant-owned client statements/ageing, date/currency balances, limits and safe CSV exports.
- `backend/app/Http/Controllers/TeamController.php` — Start newly registered/invited users Simple without accepting client privilege fields.
- `backend/app/Http/Controllers/WorkspacePreferencesController.php` — Authenticated per-user preference read/write with role-aware module validation.
- `backend/app/Models/User.php` — Cast stored module selections as arrays.
- `backend/app/Services/WorkspaceModules.php` — Working module registry, role filtering and canonical presets.
- `backend/database/migrations/2026_10_21_000008_add_user_workspace_preferences.php` — Additive user mode and JSON selection columns, preserving existing defaults.
- `backend/routes/api.php` — Register authenticated preference endpoints and bounded report routes.
- `backend/tests/Feature/BusinessReportsTest.php` — Regression coverage for preference ownership, role boundaries, preset/custom behavior or dated currency-safe reports.
- `backend/tests/Feature/WorkspaceModulesTest.php` — Regression coverage for preference ownership, role boundaries, preset/custom behavior or dated currency-safe reports.
- `docs/PAYPOD-MODULE-REVIEW.md` — Document current install instructions, validation, behavior, reviewed scope or release history.
- `docs/WORKSPACE-MODULES-UPDATE.md` — Document current install instructions, validation, behavior, reviewed scope or release history.
- `frontend/e2e/modules.spec.ts` — Compiled UI module regression or adapt production smoke to new-registration mode defaults.
- `frontend/e2e/production.spec.ts` — Compiled UI module regression or adapt production smoke to new-registration mode defaults.
- `frontend/src/App.vue` — Chooser entry points, role-safe optional navigation, mode header and hidden-route handling.
- `frontend/src/components/LineItemsEditor.vue` — Respect the selected product/delivery modules while retaining saved document data.
- `frontend/src/components/Navbar.vue` — Chooser entry points, role-safe optional navigation, mode header and hidden-route handling.
- `frontend/src/router/index.ts` — Chooser entry points, role-safe optional navigation, mode header and hidden-route handling.
- `frontend/src/style.css` — Module layout and contained mobile navigation with accessible sign-out.
- `frontend/src/types/index.ts` — Workspace preference/user types.
- `frontend/src/utils/workspace.test.ts` — Regression coverage for preference ownership, role boundaries, preset/custom behavior or dated currency-safe reports.
- `frontend/src/utils/workspace.ts` — Shared frontend mode/module visibility rules with legacy and role handling.
- `frontend/src/views/Dashboard.vue` — Show actual default currency instead of stale NGN-only footer text.
- `frontend/src/views/documents/DocumentForm.vue` — Respect the selected product/delivery modules while retaining saved document data.
- `frontend/src/views/invoices/InvoiceDetail.vue` — Respect the selected product/delivery modules while retaining saved document data.
- `frontend/src/views/invoices/InvoiceForm.vue` — Respect the selected product/delivery modules while retaining saved document data.
- `frontend/src/views/reports/BusinessReports.vue` — Client/date/currency report controls, grouped totals, rows and CSV download.
- `frontend/src/views/settings/WorkspaceModules.test.ts` — Regression coverage for preference ownership, role boundaries, preset/custom behavior or dated currency-safe reports.
- `frontend/src/views/settings/WorkspaceModules.vue` — User-facing presets, custom checkboxes and persistent module selection.
- `scripts/package-workspace-modules-update.py` — Cumulative isolated update archives and SHA-256 sidecars without secrets/runtime data.
- `docs/changes/2026-10-21-workspace-modules-reports.md` — This release record.
- `docs/DELIVERY-VALIDATION.md` — Verification evidence and explicit live-check limits.
