# Changelog

Each release records behavior, database effects, validation and deployment limitations. Detailed records live in `docs/changes/`. A Git commit records the exact source change; generated ZIPs are excluded from Git and can be reproduced using the documented packagers.

## Unreleased

No additional work recorded after the 2026-10-04 platform administration update.

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
