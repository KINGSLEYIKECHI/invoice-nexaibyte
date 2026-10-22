# 2026-10-22 Business documents

## Scope and behaviour

Adds the requested dedicated business-document area and fourteen original editable templates for the categories supplied by the owner. This builds on the workspace module/report baseline in commit 6d2a80a. It does not implement additional Paypod accounting features. Original user-supplied Word/Excel files remain unchanged and are not committed or redistributed; third-party covers, commercial boilerplate and sample identities were deliberately excluded. These templates provide editable starting wording, not verified legal instruments or replicas of the supplied designs.

Owners/admins can save a separate company definition, prepare and edit private drafts, save/reopen/delete them and download Word/PDF. Salary planning adds a configurable growth rate, currency-aware live totals and Excel formulas. Advanced navigation includes the feature; Simple/Custom users can enable it in My modules. Server role/tenant checks remain independent of navigation preferences. No document is emailed, signed or made public.

## File-level record

| File | Change |
| --- | --- |
| backend/database/migrations/2026_10_22_000009_create_business_documents.php | Add tenant-owned profile and document tables with cascading business foreign keys and encrypted payload storage columns. |
| backend/app/Models/BusinessDocument.php | Tenant-scoped draft model with encrypted content cast. |
| backend/app/Models/BusinessDocumentProfile.php | Tenant-scoped reusable profile with encrypted definition cast. |
| backend/app/Services/BusinessDocumentService.php | Catalog, profile defaults, bounded input validation, company snapshots, integer minor-unit salary forecasts and rounding. |
| backend/app/Services/BusinessDocumentExport.php | Native lightweight DOCX/XLSX ZIP exports, escaped XML, cached Excel formulas, private temporary cleanup and Dompdf integration. |
| backend/app/Http/Controllers/BusinessDocumentController.php | Authenticated profile, paginated drafts, CRUD and attachment downloads. |
| backend/app/Http/Middleware/PrivateBusinessDocuments.php | Private/no-store and nosniff response headers. |
| backend/resources/data/business-documents.json | Fourteen original template definitions, fields, categories and plain-text wording. |
| backend/resources/views/pdf/business-document.blade.php | Light escaped text PDF layout and bounded-width salary tables with totals. |
| backend/routes/api.php | Manager-only tenant document endpoints and download throttling. |
| backend/app/Services/WorkspaceModules.php | Register manager-only business_documents module in Advanced and Custom options. |
| frontend/src/views/business-documents/BusinessDocuments.vue | Company profile, catalog, inputs, editable drafts, saved list, private downloads and BigInt salary preview. |
| frontend/src/components/Navbar.vue | Dedicated Business documents navigation link. |
| frontend/src/router/index.ts | Manager/module-gated business-document route. |
| frontend/src/utils/workspace.ts | Mirror manager-only module visibility. |
| frontend/src/utils/workspace.test.ts | Verify Advanced/Simple/Custom visibility without staff privilege escalation. |
| backend/tests/Feature/BusinessDocumentsTest.php | Catalog, privacy/encryption, snapshots, CRUD, role/tenant isolation, all text exports, salary formulas/rounding and invalid inputs. |
| frontend/e2e/business-documents.spec.ts | Compiled UI flow with synthetic API fixtures, profile save, draft editing/download gating, live totals and mobile layout/sign out. |
| scripts/package-business-documents-update.py | Cumulative scoped backend/frontend ZIPs, exclusion assertions and SHA-256 sidecars. |
| docs/BUSINESS-DOCUMENTS-UPDATE.md | Current feature/API guide, original and managed Hostinger instructions, migration effects and explicit maintenance down/up commands. |
| docs/changes/2026-10-22-business-documents.md | This implementation and evidence record. |
| docs/DELIVERY-VALIDATION.md | Append current validation boundary and evidence. |
| README.md | Point to the current cumulative update guide. |
| CHANGELOG.md | Dated release entry. |

## Database and deployment impact

Migration creates business_document_profiles (unique business_id, encrypted definition, timestamps) and business_documents (business_id, template, encrypted content, timestamps and tenant/date index). Parent business deletion cascades both. Existing financial, user and product records are not rewritten. No Composer lockfile, environment variable or cron change is required. PHP zip is needed for Word/Excel; existing BCMath/Dompdf dependencies remain. Preserve APP_KEY and encrypted backups. No public generated files or new cloud account.

The update guide directly contains the absolute `/home/u491120861/invoice-backend/artisan down` and `up` commands with PHP 8.3, app-only cron pause/resume, database backup, scoped migration, failure recovery and managed-release boundaries. Code rollback can leave these additive tables; dropping them destroys saved drafts. No maintenance, migration or deployment command was run against Hostinger.

## Verification on 22 October 2026

- Dedicated local MySQL database invoice_ci_local_20261002, PHP 8.3.30: full Pest suite **80 tests / 632 assertions passed**. Command: `vendor/bin/pest --configuration phpunit.mysql.xml --colors=never`, with explicit DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_PORT=3306, DB_DATABASE=invoice_ci_local_20261002, DB_USERNAME=root, empty local test password and EXPECT_MYSQL=1. Production was not contacted.
- After final salary PDF/XLSX layout and control-character validation refinements, focused BusinessDocuments suite: **6 tests / 166 assertions passed**. A final Word totals pagination refinement was rechecked with the same focused suite; formatting did not change behaviour.
- Frontend `npm test -- --maxWorkers=1`: **23 tests passed**. Sandbox temp-file rename failures prevented an earlier run from loading suites; the successful run used the approved local process boundary.
- `VITE_API_URL=/backend/api npm run build`: TypeScript and production Vite build passed.
- Compiled preview at local port 8196; `E2E_URL=http://127.0.0.1:8196 E2E_BROWSER_CHANNEL=msedge npx playwright test e2e/business-documents.spec.ts e2e/modules.spec.ts --reporter=line`: **2 tests passed**. Browser APIs are mocked synthetic fixtures. Tested profile/draft flows, save-before-download, salary totals, module persistence, 390px width and sign-out visibility. This does not prove production API/browser integration.
- Synthetic salary workbook imported/recalculated with the bundled spreadsheet engine. Changing annual increase from 20% to 10% changed Engineer year 2 from 300000 to 275000 and year 3 from 360000 to 302500; totals recalculated. Original 20% restored before rendering. Excel desktop interaction is not claimed.
- Synthetic samples produced for all fourteen document types; Word OOXML validity, PDF magic bytes and formula safety tested. All fourteen synthetic Word templates rendered with LibreOffice/Poppler (15 pages in total) and reviewed visually, alongside the offer/salary PDFs, salary sheet and desktop/mobile workspace. A totals pagination issue in salary Word was corrected and re-rendered. These are fictional QA data, not live customer documents.

## Reproduce packages

From the repository root after building the frontend:

```sh
python scripts/package-business-documents-update.py
python scripts/package-source.py
```

Outputs under deployment: invoice-business-documents-backend-update.zip, invoice-business-documents-frontend-update.zip, their .sha256 sidecars, invoice-saas-github-ready.zip and invoice-ci-server-setup.zip. ZIPs are generated local artifacts excluded from Git. The packager intentionally excludes environment files, vendor/node_modules, runtime data, customer exports and the source reference attachments. Backend ZIP is cumulative, not a destructive synchronization command.

## Package review

Cumulative backend/frontend packages generated successfully with the current guide and SHA-256 sidecars. Source/setup ZIPs regenerated. Required feature files, guide, SPA entry point, API base and exclusions checked against the ZIP contents. The file-level record was reconciled with all 24 changed files before commit.

## Remaining live acceptance and limits

No Hostinger upload/migration, real-company document, live download, native Microsoft Word/Excel round-trip or Linux GitHub workflow was verified here. Deployment and production PHP zip must be checked through the guide. No new revision history, public sharing, staff access, uploaded custom Word template import, company logo embedding, rich-text layout, e-signatures, payroll calculation or legal enforceability assessment is included. Users can edit each draft's wording; it is not a company-wide system-template editor. Existing documents intentionally retain snapshots. The Excel export focuses on the salary schedule and company name; custom prose is in Word/PDF.
