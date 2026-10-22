# Business documents update

Current manual Hostinger update guide for the business documents release dated 22 October 2026. It supersedes WORKSPACE-MODULES-UPDATE.md for this cumulative package. Existing installation and CI/CD layout guides remain applicable to their respective deployment methods.

## What businesses can do

Owners and admins have a dedicated **Business documents** section at `/business-documents`. It is included in Advanced mode. In Simple or Custom mode, open **My modules**, select Business documents and save. Staff do not have access, even if they enter the URL or call the API directly.

Open **Company profile** and save the legal name, address, contacts, registration reference, business definition, values, policies and authorised signatory details. These are document defaults and do not change invoice business settings. Choose a template, enter the recipient or role details, prepare a draft, edit the wording and save. Square brackets mark information still to complete. Downloads require saving first. Saved documents remain editable and can be permanently deleted after confirmation. Only documents in the current business can be opened or downloaded. All owners/admins of that business can access them; these are not private to one team member.

The fourteen templates are:

- Interview evaluation
- Fresh graduate interview questions
- Experienced candidate interview questions
- Guarantor form
- Letter of appointment
- Non disclosure agreement
- Staff induction checklist
- Employment termination letter
- Employee biodata form
- Employee welcome pack checklist
- Job description
- Job offer letter
- Non compete agreement
- Salary structure

Word (DOCX) and PDF downloads are available for all templates. Salary structures also have editable Excel (XLSX) downloads. These are clean original starting templates based on the requested categories, not replicas or redistributions of the supplied commercial files. Third-party promotional covers, copyrighted boilerplate, logos and sample identities are excluded. The interface does not upload/import arbitrary Word templates, execute instructions embedded in files, send documents, apply signatures, make hiring decisions or carry out employment actions. Review and complete employment/agreement wording for the business and relevant jurisdiction before issue. No AI or external account is needed.

New drafts interpolate known profile/recipient fields as plain text. Existing drafts keep their saved text and company snapshot after profile changes. To use revised profile defaults, create a new draft. Edit an existing document's full wording manually if needed; changing a profile never silently rewrites it. Editable wording is saved per document, not as a global replacement of the system template. PDFs use a lightweight text layout; uploaded company logos and rich text editing are not included in this release.

## Salary planning

Choose a supported currency, 1–10 years and a 0–100% annual increase (up to two decimal places). The default increase is zero. Add 1–50 role rows with starting amounts. Inputs are amounts for one employee in each role for a single pay period; choose and describe the pay period in the wording. Totals sum those role amounts, not employee headcounts or an annual payroll. No payroll deductions, tax, employment entitlements or automatic pay rises are applied.

The browser shows projections and totals immediately. The server recalculates for exports using BCMath integer currency minor units and half-up rounding each year, including zero-decimal currencies. Currency precision is validated. Each projected role amount is capped at 1,000,000,000,000 minor units and a year's total at 50,000,000,000,000 minor units. Reduce the values or horizon if these bounds are reached.

Excel has one Salary structure sheet. Blue cells are editable role labels, base amounts and the annual increase. Later years use ROUND(previous year * (1 + increase), currency precision), and totals use SUM. The year count is fixed in each export; change it in the app and download again to change the horizon. Formula results are cached and recalculate when opened. Changes made inside Word/Excel are not imported back into the app. Excel includes the company name, planner controls and table; it does not reproduce the document's custom prose. Use PDF or Word for that prose.

## Database and privacy

The additive migration `2026_10_22_000009_create_business_documents` creates:

- `business_document_profiles`: one business profile, encrypted definition, timestamps.
- `business_documents`: business ID, template identifier, encrypted title/body/company snapshot/salary payload, timestamps, tenant/date index.

It does not rewrite users, invoices, quotations, products, amounts, numbering or bank details. Both tables cascade when the parent business is deleted. Preserve the existing APP_KEY and back it up securely: changing it makes encrypted profiles and drafts unreadable. Encryption protects stored document fields; authorised application users can read them. Business IDs, template names and timestamps are not encrypted. Database backups require the same access protections and retention planning as the live database.

Downloads are authenticated, have no public token/URL and return private/no-store headers. Word/Excel exports are built in a random PHP temporary file and removed in a finally block; PDF is produced in memory. No generated document is placed under public storage or sent to cloud storage. Route activity logs contain route templates/status only, not document text. Limit stored personal information and delete drafts when no longer needed. This feature does not add automatic retention rules or audit snapshots of previous draft versions.

## Hostinger requirements

PHP 8.3 and the existing dependencies remain. Enable PHP **zip** for Word and Excel downloads. BCMath and Dompdf are already project requirements. The web PHP runtime and CLI runtime may have different extensions; check both in hPanel. If zip is unavailable, PDF remains available and Word/Excel return a clear 503 error. Writable PHP temporary storage is required. No new Composer/NPM dependency, external document service, environment variable, queue worker or cron job is required for this feature. Node, Python, Office and LibreOffice are not required on Hostinger.

## Original layout installation

Confirm `/home/u491120861/invoice-backend/artisan` exists and the private `.env` targets only `u491120861_invoice`. Back up that database, backend, public frontend, uploaded storage and private `.env`. Preserve APP_KEY, existing vendor files and public backend gateway. Pause **only this invoice application's** queue/reminder cron entries and allow its worker to finish.

Enter maintenance mode before replacing files:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan down --no-ansi
```

Extract `invoice-business-documents-backend-update.zip` into `/home/u491120861/invoice-backend`. Preserve `.env`, vendor and storage. Extract `invoice-business-documents-frontend-update.zip` into `/home/u491120861/domains/nexaibyte.com/public_html/invoice`, preserving its `backend` gateway directory. Do not upload the private backend to public_html.

Run only these app-specific commands:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan config:clear --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan migrate --force --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan route:clear --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan view:clear --no-ansi
```

This cumulative package also runs any older pending migrations. Review their guides if releases were skipped. Never run `migrate:fresh`, database resets or demo seeders on production. These paths scope commands to this app; do not restart shared PHP services or modify other applications' files or cron entries.

After successful file installation and migrations, leave maintenance mode:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan up --no-ansi
```

Check `https://invoice.nexaibyte.com/backend/up`, sign in as an owner/admin, enable the module if necessary, save a profile and create a fictional test document. Download Word and PDF, then test the salary planner and Excel. Verify a staff account is denied and another business cannot access the document. Delete the fictional document. Resume only this app's paused cron entries. No new cron schedule is necessary.

If an update fails, keep maintenance enabled while diagnosing or restoring known-good code. Do not drop the new tables as a routine rollback: they may already hold new documents. Older code can ignore the additive tables. Restore a database backup only through a deliberate recovery plan that preserves financial activity after that backup. Bring the site up with the same absolute `up` command only when ready. Restore the matching APP_KEY with encrypted data.

## Managed CI/CD installations

If using the versioned `invoice-deploy` release layout, deploy through the existing pipeline and its documented backup/rollback process. Do not extract manual ZIPs over a managed release. Pushes can trigger configured CI/CD; this guide does not assert a production deployment. The existing workflow runs backend MySQL tests, frontend unit/build checks and Playwright tests; the new browser test uses synthetic mocked APIs, while existing production-package tests retain their separate integration scope.

## API and validation

All routes below require authentication, tenant context and owner/admin role:

- GET `/api/business-documents/setup`: catalog and current company profile.
- PUT `/api/business-documents/profile`: known plain-text profile fields only.
- GET/POST `/api/business-documents`: paginated metadata / create an encrypted draft.
- GET/PUT/DELETE `/api/business-documents/{id}`: tenant-scoped draft operations.
- GET `/api/business-documents/{id}/download/{pdf|docx|xlsx}`: private attachment; Excel restricted to salary structures; 20 downloads per minute.

Template IDs are validated; a saved document cannot change its type. Title limit 160 characters, wording 30,000, profile fields 3,000, role names 120. Plain text is escaped for HTML/XML; user strings are never executed as templates or spreadsheet formulas. Export filenames use numeric IDs. There are no arbitrary file paths or remote template/image fetches. The optional module selector controls navigation, not server permissions.

See `changes/2026-10-22-business-documents.md` for the file-level implementation record and final validation evidence.
