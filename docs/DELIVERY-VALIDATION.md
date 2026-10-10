# Delivery validation â€” 2026-10-02

## Executed locally

- PHP 8.3.30, MySQL 8.4.3 in a dedicated disposable database: 38 backend tests / 180 assertions passed.
- Earlier SQLite regression run: 37 backend tests / 178 assertions passed (before the CI-only MySQL guard test was added).
- Six frontend Vitest tests passed; TypeScript and production Vite build passed with `/backend/api`.
- Playwright on headless Microsoft Edge passed the compiled-package test against a local PHP server with `proc_open` disabled and MySQL. It exercised browser registration/dashboard, platform-access denial, client/invoice creation, 107500-kobo total, PDF bytes, route refresh, hidden source paths, health and release metadata.
- Production-only Composer vendor folder built from the lockfile; Composer validation and dependency audit passed.
- Release package SHA-256, required files, API base and sensitive/runtime/dev-file exclusion checks passed.
- Packaged Laravel discovery, migrations, configuration/route/view caches completed with `proc_open` disabled.
- SQL snapshot created without `proc_open` and restored to a separate disposable MySQL database (17 tables).
- actionlint validated the workflow; Bash syntax and embedded Python syntax checks passed.

The browser test initially caught a missing physical `backend/public` directory required by the PDF library. Packaging now includes that directory; the PDF flow passes in the packaged environment. Frontend unit tests explicitly exclude Playwright specs.

## Prepared for GitHub, not executed locally

- Linux Apache container with real `.htaccess`, Chromium and MySQL 8.0 CI service.
- Linux deployment fixture tests for activation, rollback, failed health and failed migrations.
- GitHub artifact upload/download, environment gate and SSH delivery.
- Hostinger adoption, cron replacement, symlink activation and health-gated rollback.

Docker is not installed here. The first successful GitHub workflow run is required before claiming the Linux pipeline is exercised end to end. Your hosted database is confirmed as MySQL; the exact version remains unknown. SMTP/WhatsApp delivery and hosted SSL/OPcache/permissions remain provider-side checks.

## Packages

- `deployment/invoice-saas-github-ready.zip`: source repository contents, no installed dependencies or secrets.
- `deployment/invoice-ci-server-setup.zip`: one-time adoption scripts for the existing account paths.
- `artifacts/local-20261002-02.tar.gz` and `.sha256`: local production package; GitHub produces uniquely identified packages per commit/run/attempt.

Local test databases are deliberately separate from project/customer databases and have names beginning with `invoice_ci`. No remote deployment or repository publication was performed.

## Media update â€” 2026-10-04

The reported public logo URL returned HTTP 404. Added symlink-independent local logo delivery, authenticated Cloudinary image storage, platform logo/favicon uploads and bounded PDF logo retrieval. MySQL regression suite: 42 tests / 208 assertions passed. Frontend: seven tests and production TypeScript/Vite build passed. Real Cloudinary upload/delete remains unverified without account credentials. See MEDIA-STORAGE.md and the invoice-media update archives.

## Platform operations update — 2026-10-04

52 backend tests / 296 assertions passed against isolated MySQL 8.4.3. All 13 frontend tests, TypeScript and the production build passed. Added encrypted/masked credential handling, read-only provider checks, authenticated activity metadata, SQL daily login aggregates, user search, queued payment owner/customer emails with retry status, explicit favicon navigation and consent-gated public advertising preparation. Provider failure and workspace-deletion regression checks passed. SMTP/provider tests are mocked; hosted rollout and real deliveries remain unverified. See ADMIN-UPDATE.md for acceptance and limitations.

## Commercial documents — 2026-10-05

59 MySQL tests / 356 assertions, 16 frontend tests, TypeScript and production build passed. Covers document lifecycle/PDF, tenant isolation, currency preservation, idempotent conversion and CSV/XLSX atomic imports/formula rejection. CI enables zip; Hostinger rollout and Linux CI remain unverified. See COMMERCIAL-DOCUMENTS.md.

## Settings and sign-out — 2026-10-10

60 backend tests / 364 assertions, 17 frontend tests, TypeScript and production build passed. Headless Edge isolated sidebar CSS checks confirmed visible logout at 1366x600, 1024x768 and 800x600. Bank visibility, leading zeros, global currency and independent international phone persistence covered. Hosted deployment remains unverified.
