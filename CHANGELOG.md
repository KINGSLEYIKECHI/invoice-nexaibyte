# Changelog

Each release records behavior, database effects, validation and deployment limitations. Detailed records live in `docs/changes/`. A Git commit records the exact source change; generated ZIPs are excluded from Git and can be reproduced using the documented packagers.

## 2026-10-10 — Converted invoice payment details

Converted invoices return business payment details immediately; invoice screen now displays invoice-enabled bank accounts and instructions, matching PDF visibility. No additional schema change. Current cumulative installation guide remains NUMBERING-PO-UPDATE.md. Targeted conversion regression, 19 frontend tests and production build verified locally.

## 2026-10-10 — Numbering, PO and totals

Added continuation counters with prefix/separator/padding for invoices and quotations; backward counters rejected and existing numbers skipped. Added optional PO references and quotation conversion carry-through, optional bank fields, line-table subtotals and real-time quotation totals. Additive migration preserves old documents. See [current installation guide](docs/NUMBERING-PO-UPDATE.md). 61 backend tests/377 assertions and 18 frontend tests plus production build passed locally; not deployed.

## 2026-10-10 — Settings and sign-out fixes

Fixed NGN-only settings selector, added manual bank accounts with invoice/quotation PDF visibility, and kept desktop sign-out reachable in short viewports. Added nullable business bank_accounts JSON; currency and phone remain independent. See [upgrade guide](docs/SETTINGS-UPDATE.md) and [change record](docs/changes/2026-10-10-settings-and-sign-out.md).

## Unreleased

## 2026-10-05 — Commercial documents and currencies

- Added tenant-isolated quotations with PDF, acceptance/decline and idempotent conversion to invoices.
- Added quantity-only delivery notes, printable PDFs and recipient recording, including creation from issued invoices.
- Added product catalogue, CSV/XLSX templates and atomic preview/confirm imports with explicit SKU update controls.
- Added 165 searchable ISO currencies, saved document precision and separate dashboard currency balances; no FX conversion.
- Added an additive migration for catalogue/document tables, invoice currency, line units and numbering counters. Existing invoice amounts remain NGN.
- Verified 59 backend tests / 356 assertions, 16 frontend tests and production build locally. Hosted rollout and Linux CI remain unverified.
- Guide: [Commercial documents](docs/COMMERCIAL-DOCUMENTS.md); detailed record: [2026-10-05 change record](docs/changes/2026-10-05-commercial-documents.md).

## 2026-10-04 — Platform administration update

Detailed record: [2026-10-04 platform administration](docs/changes/2026-10-04-platform-administration.md).

### Added

- Platform-only `/admin` interface; registered-user search, daily distinct login counts in Lagos time and recent activity estimates.
- Encrypted database configuration for SMTP, Cloudinary and WhatsApp; masked secret responses and explicit clear/replace controls.
- SMTP test email and read-only provider authentication checks.
- Activity metadata, retention command and optional daily Hostinger cron.
- Queued owner/customer payment emails with delivery status and failed-notice retries.
- Disabled-by-default AdSense settings, consent-gated About-page placement, dynamic ads.txt and public About/Privacy pages.
- Reproducible cumulative backend/frontend update ZIPs and installation/acceptance documentation.

### Changed

- Platform sidebar now links to administration; Branding & favicon upload remains available through the new admin page.
- Registration/login/logout update activity timestamps and log relevant actions.
- Invoice mail jobs refresh saved runtime settings and store generic provider failure messages.
- Public advertising navigation reloads the document before entering another route, preventing loaded ad scripts from persisting in private workspaces.
- CI production smoke includes platform access denial, public information and ads.txt checks.

### Database

Migration `2026_10_04_000004_create_platform_operations`: creates `integration_settings`, `activity_logs`, `payment_notifications` and adds nullable indexed `users.last_seen_at`. No existing invoice/account data is reset. Preserve APP_KEY and back up the configured invoice database before installation.

### Validation and limits

Recorded implementation validation: 52 MySQL backend tests / 296 assertions, 13 frontend tests, TypeScript and production build passed. Provider responses/mail were mocked. Hosted installation, real provider delivery, Google approval/CMP and the new GitHub Linux run remain acceptance work.

## Baseline — commit 320c02c

This existing commit contains the initial invoicing application and the earlier branding/media/CI work. Those changes were already committed before the platform-administration update; they are not presented as newly implemented in this release.

- Laravel/Vue business workspaces, business roles and tenant isolation; clients, integer-kobo invoices, PDFs, recorded payments, delivery logs/reminders and team management.
- Explicit platform-admin permission, runtime branding/content editor and platform logo/favicon controls.
- Local logo delivery without a storage symlink, optional authenticated Cloudinary storage and bounded PDF logo loading.
- GitHub PHP/MySQL and frontend verification, production packaging, restricted Apache browser tests and optional Hostinger delivery.
- Versioned releases, checksums, shared environment/storage, private database snapshots, health checks and code rollback.

See [README](README.md), [media guide](docs/MEDIA-STORAGE.md), [CI/CD guide](docs/CI-CD.md) and [validation record](docs/DELIVERY-VALIDATION.md) for the baseline's implementation and verification boundaries. The cumulative admin archives include earlier branding/media files so an older installation can be updated without separately applying obsolete ZIPs.
