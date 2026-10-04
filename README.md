# Invoice SaaS

A working multi-tenant invoicing application for Nigerian businesses. Create a workspace, manage clients and team access, prepare branded invoices, deliver PDFs, and record payments.

The original supplied specification is preserved in SPEC.md. This README was created before scaffolding and completed after implementation.

## Stack

- Laravel **12**, PHP 8.3, Sanctum bearer tokens, dompdf, Pest
- Vue 3, Vite, TypeScript, Pinia, Vue Router, Vitest
- Docker: MySQL 8, Redis queues/cache, nginx, PHP-FPM, queue worker, scheduler, Mailpit
- Windows/Laragon: SQLite, database queue, file cache, log-only mail

Laravel 11 was requested, but Composer rejected its releases because of security advisories. Laravel 12 uses patched dependencies; the requested API contract and features are retained.

## Start on this computer

Already installed and seeded. Frontend: **http://localhost:5174**. API: **http://localhost:18080/api**. These ports avoid other projects already using 8000, 8001 and 5173.

To start again, open PowerShell:

    cd C:\laragon\www\invoice-generator\apps\invoice-saas
    .\start-local.ps1

Leave that terminal open. Ctrl+C stops the services started by the script. If this project is already running, use the existing app instead of starting another copy.

For a fresh local setup (PHP 8.3 with bcmath, sqlite, gd, mbstring and zip; Composer; Node 22.12+):

    .\setup-local.ps1
    .\start-local.ps1

Local mail uses MAIL_MAILER=log: email content and invoice links appear in backend/storage/logs/laravel.log. WhatsApp uses log-only mode. These demo modes do **not** deliver actual messages.

To catch local email in Mailpit, set MAIL_MAILER=smtp, MAIL_HOST=127.0.0.1, MAIL_PORT=1025 and run Mailpit separately. Restart the queue worker after changing environment variables.

## Demo accounts

All use password **password**:

| Account | Workspace | Role |
|---|---|---|
| owner@brightspark.test | Bright Spark Electricals | Owner |
| admin@brightspark.test | Bright Spark Electricals | Admin |
| staff@brightspark.test | Bright Spark Electricals | Staff |
| owner@kemtech.test | KemTech Solutions | Owner |

Try staff to see restricted navigation and server permissions. KemTech has its own client and invoice list. Seeding is idempotent and does not reset existing tenants.

## Docker setup

Requires Docker Engine / Docker Desktop with Compose. Docker is unavailable in this session, so its runtime has not been verified here. The local setup above is verified.

From this folder, on a fresh checkout:

    cp .env.example .env
    cp backend/.env.example backend/.env
    cp frontend/.env.example frontend/.env
    docker compose up --build

In another terminal:

    docker compose exec backend php artisan key:generate
    docker compose exec backend php artisan migrate --seed

PowerShell equivalents:

    Copy-Item .env.example .env
    Copy-Item backend/.env.example backend/.env
    Copy-Item frontend/.env.example frontend/.env

Copying backend/.env.example overwrites local configuration. Keep the current .env if you intend to continue using SQLite. Do not regenerate a key on an existing deployment: signed links depend on it.

- App: http://localhost:5173
- API: http://localhost:8000/api
- Mailpit: http://localhost:8025

These Docker ports must be available. Stop conflicting applications yourself or adjust published ports and frontend/API URLs.

Queue and scheduler wait for an app key. MySQL and Redis use persistent volumes. Invoice/logo storage is shared by API, workers, scheduler and nginx. After changing .env:

    docker compose restart queue scheduler

## Features

- Registration creates a business and owner atomically; token login/logout.
- Clients: CRUD, search, pagination, soft deletion, Nigerian phone normalization.
- Invoices: line editor, discounts/tax, draft editing, independent numbering, duplication, voiding, filters and pagination.
- Server-calculated integer-kobo totals using decimal arithmetic.
- Payments: partial/full payment, overpayment rejection, deletion and status recalculation.
- Branded PDF downloads with logo, items, bank instructions and status.
- Signed read-only public invoice pages and PDF downloads.
- Queued email/PDF attachments and WhatsApp documents, retries and delivery history.
- Owner/admin/staff permissions; removed members lose tokens and login access while audit records are retained.
- Business settings, logo upload, default tax, invoice prefix and owner-only business deletion.
- Dashboard totals, recent invoices, collection chart and invoice breakdown.
- Daily overdue/reminder command at 08:00 Africa/Lagos.
- Responsive layouts with loading, error and empty states.

Team accounts use a manager-assigned initial password which the manager shares securely. There is no invitation acceptance email flow. Owners can permanently delete their workspace after typing its exact name; admins and staff cannot.

## Business rules

Money is stored as integer kobo. UI naira amounts are converted before submission. The server recomputes all totals with BCMath and ignores supplied totals/status.

Number allocation runs inside the invoice transaction, with lockForUpdate on MySQL. SQLite acquires its database write lock before reading the counter. A unique (business_id, number) index provides an additional guard.

Only drafts with no queued delivery can be edited. A successful send issues a draft; failed delivery leaves it a draft. Payments require an issued invoice with a balance. Void invoices cannot be sent or paid; remove payments before voiding.

The tenant global scope fails closed without authentication. Every tenant-owned table, including invoice items, carries business_id. Route binding returns 404 for another tenant's IDs. Jobs, seeders and public routes deliberately bypass scopes with explicit tenant selections. Team operations explicitly filter by tenant.

Reminder keys contain invoice, due date, kind and channel. The scheduler queues each reminder once: three days before due and the first overdue day. Older invoices are marked overdue, but missed reminders are not backfilled. Jobs attempt delivery three times with backoff [30,120,600] seconds and preserve final failure errors.

Public links require both a UUID and valid signature. They expose only that invoice, its billing contact and business details. Treat a shared invoice link as access to that invoice.

## WhatsApp

Set backend/.env:

    WHATSAPP_ENABLED=true
    WHATSAPP_PHONE_NUMBER_ID=
    WHATSAPP_ACCESS_TOKEN=
    WHATSAPP_API_VERSION=v23.0
    WHATSAPP_BASE_URL=https://graph.facebook.com

Choose a supported version for your Meta app. Real delivery requires a public HTTPS APP_URL, valid credentials and applicable Meta messaging permissions/window rules. This implementation sends document messages; it does not configure Meta accounts or approved outbound templates. Missing credentials use labelled log-only delivery.

## API

Use Authorization: Bearer <token> and Accept: application/json. routes/api.php implements auth, current user, business/logo, team, clients, invoices, duplicate/void/PDF/send/messages, payments and dashboard.

Login body:

    {"email":"owner@brightspark.test","password":"password"}

Invoice body:

    {"client_id":1,"issue_date":"2026-10-02","due_date":"2026-10-16","discount_kobo":0,"tax_percent":7.5,"items":[{"description":"Installation","quantity":1,"unit_price_kobo":15000000}]}

Send body:

    {"channels":["email","whatsapp"],"message":"Please find your invoice attached."}

Payment body:

    {"amount_kobo":100000,"method":"bank_transfer","paid_on":"2026-10-02"}

Lists return Laravel pagination. Invoice detail includes client, business, items, payments, messages and public_url. Validation returns 422, missing auth 401, role failures 403 and unavailable tenant records 404.

## Tests

Local:

    cd backend
    php artisan test
    php scripts/check-concurrency.php
    cd ../frontend
    npm.cmd test
    npm.cmd run build

Docker:

    docker compose exec backend php artisan test
    docker compose exec frontend npm run test
    docker compose exec frontend npm run build

Pest uses an isolated in-memory SQLite database. The concurrency test creates a temporary database and launches four processes to create 40 invoices; it never writes to the demo database. This verifies SQLite concurrency. MySQL row locks and Docker runtime still need verification in a Docker-capable environment.

The suite covers auth, tenant boundaries, roles, clients, invoice math/numbering/editing, PDF, signed links, queues, email attachments, Meta HTTP/log-only mode, retries, payments and reminders. Frontend tests cover formatting, line editing and status rendering. Browser and HTTP checks cover login, dashboard, invoice creation and local queued delivery.

Result files are saved beside this README.

## Structure

    invoice-saas/
      README.md, SPEC.md, .env.example, docker-compose.yml
      setup-local.ps1, start-local.ps1
      docker/nginx.conf
      backend/
        app/Http/{Controllers,Middleware,Requests,Resources}
        app/Models/{Concerns,Scopes}
        app/{Enums,Policies,Services,Jobs,Mail,Console/Commands}
        database/{migrations,seeders,factories}
        resources/views/{pdf,emails,public}
        routes/{api,web,console}.php
        tests/{Feature,Unit}
        scripts/{check-concurrency,concurrency-worker}.php
        Dockerfile, .env.example, composer.json, composer.lock
      frontend/
        src/{api,stores,router,types,utils,components,views,__tests__}
        src/views/{auth,clients,invoices,team,settings}
        Dockerfile, .env.example, package.json, package-lock.json

## Deployment notes

Use a strong app key, real database/SMTP credentials, HTTPS, restricted CORS, persistent storage and backups. Turn off debug mode. Replace all demo credentials and configure queue/scheduler supervision. Demo defaults are for local use.

## What I learned

Fail-closed scopes need explicit context in jobs and public routes. Number allocation and payments require transactions, not UI validation alone. SQLite and MySQL lock differently; testing concurrent processes exposes that difference. Queuing and successful issuance are separate events, and delivery must preserve audit history.
## Platform branding editor

See [PLATFORM-EDITOR.md](PLATFORM-EDITOR.md) for runtime branding controls and the existing Hostinger installation update. Grant platform access only to your own registered account using the server command.

## GitHub CI/CD and Hostinger delivery

Publish this project folder as the repository root. Follow [the detailed CI/CD guide](docs/CI-CD.md) for versioned release packaging, production-like tests, server adoption, SSH configuration, automatic deployment and rollback. See [delivery validation](docs/DELIVERY-VALIDATION.md) for checks actually executed.

## Cloud images and favicon uploads

See [media storage setup](docs/MEDIA-STORAGE.md) for Cloudinary configuration, the logo-path repair, platform logo/favicon uploads and existing-installation update instructions.

## Platform integrations, users and payment emails

See [the cumulative admin update guide](docs/ADMIN-UPDATE.md) for the new admin interface, encrypted credentials, connection tests, users/activity reporting, payment receipts, favicon upload and AdSense preparation. It includes installation commands for your existing Hostinger deployment and the manual acceptance checklist.

## Change history

Use [the changelog](CHANGELOG.md) for release summaries, [the latest detailed change record](docs/changes/2026-10-04-platform-administration.md) for the file-by-file implementation, and [the tracking process](docs/CHANGE-TRACKING.md) for future commits.
