# Settings and sign-out — 2026-10-10

Fixed the settings-only hardcoded NGN selector, independently documented phone normalization, added business bank accounts with invoice/quotation PDF flags and prevented desktop navigation from hiding logout. Logout redirects even when remote revocation fails.

Schema: nullable businesses.bank_accounts JSON. No secrets/environment/cron changes. Existing amounts, currencies and phone values remain unchanged. Accounts render from current business settings, not historical snapshots. See ../SETTINGS-UPDATE.md for scoped Hostinger deployment and acceptance.

Validation: 17 frontend tests, TypeScript and production build passed. Headless Edge isolated CSS layout checks passed at 1366x600, 1024x768 and 800x600. 60 backend tests / 364 assertions passed against isolated invoice_ci MySQL. No remote deployment or provider calls.

## File changes

- `CHANGELOG.md` — Bank account persistence, rendering, sidebar/logout fixes, regression checks or release documentation.
- `backend/app/Http/Controllers/BusinessSettingsController.php` — Manual account fields and currency selector.
- `backend/app/Models/Business.php` — Bank account persistence, rendering, sidebar/logout fixes, regression checks or release documentation.
- `backend/database/migrations/2026_10_10_000006_add_business_bank_accounts.php` — Bank account persistence, rendering, sidebar/logout fixes, regression checks or release documentation.
- `backend/resources/views/pdf/bank-accounts.blade.php` — Bank account persistence, rendering, sidebar/logout fixes, regression checks or release documentation.
- `backend/resources/views/pdf/commercial-document.blade.php` — Bank account persistence, rendering, sidebar/logout fixes, regression checks or release documentation.
- `backend/resources/views/pdf/invoice.blade.php` — Bank account persistence, rendering, sidebar/logout fixes, regression checks or release documentation.
- `backend/tests/Feature/BusinessSettingsTest.php` — Manual account fields and currency selector.
- `docs/SETTINGS-UPDATE.md` — Bank account persistence, rendering, sidebar/logout fixes, regression checks or release documentation.
- `frontend/src/components/Navbar.vue` — Bank account persistence, rendering, sidebar/logout fixes, regression checks or release documentation.
- `frontend/src/style.css` — Bank account persistence, rendering, sidebar/logout fixes, regression checks or release documentation.
- `frontend/src/types/index.ts` — Bank account persistence, rendering, sidebar/logout fixes, regression checks or release documentation.
- `frontend/src/views/settings/BusinessSettings.test.ts` — Manual account fields and currency selector.
- `frontend/src/views/settings/BusinessSettings.vue` — Manual account fields and currency selector.
- `scripts/check-sidebar.cjs` — Bank account persistence, rendering, sidebar/logout fixes, regression checks or release documentation.
- `scripts/package-settings-update.py` — Bank account persistence, rendering, sidebar/logout fixes, regression checks or release documentation.
- `docs/changes/2026-10-10-settings-and-sign-out.md` — File-level release record and actual validation results.

## Documentation follow-up: maintenance mode

- `docs/SETTINGS-UPDATE.md`: explicit scoped down/up commands, cron pause/resume, health check and failed-update handling.
- `docs/CHANGE-TRACKING.md` and `CONTRIBUTING.md`: require these steps directly in every future update guide and regenerated package documentation.
- This record documents the clarification. Runtime behavior is unchanged; reviewed commands and Git whitespace checks only, no application suite rerun.
- Regenerated settings update and source ZIPs so bundled instructions match.
