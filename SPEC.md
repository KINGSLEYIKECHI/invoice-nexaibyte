Here is the full spec for **invoice-saas**, in the same format as checkout-demo. The README comes first, before any Docker or setup commands.

## 1. `apps/invoice-saas/README.md`

````markdown
# Invoice SaaS

A multi-tenant invoicing app. Businesses sign up, add clients, create invoices, and send them as PDFs by email or WhatsApp.

## Stack
- Backend: Laravel 11 (PHP 8.3), MySQL 8, Redis (queues, cache), Sanctum (auth)
- PDF: barryvdh/laravel-dompdf
- Frontend: Vue 3 + Vite + TypeScript + Pinia + Vue Router
- Messaging: SMTP (Mailpit locally) and WhatsApp Cloud API (Meta)
- Tests: Pest (backend), Vitest (frontend)
- Infra: Docker Compose (nginx, php-fpm, queue worker, scheduler, mysql, redis, mailpit)

## Features
- Business sign-up creates a tenant and an owner account
- Roles per tenant: owner, admin, staff
- Client management (CRUD, search)
- Invoices with line items, tax, discount, auto-numbering per business
- PDF generation with business logo and brand colour
- Send by email and/or WhatsApp (queued, with delivery log and retries)
- Signed public link so clients can view and download without logging in
- Record full or partial payments; status updates automatically
- Daily scheduler marks overdue invoices and sends reminders
- Dashboard: totals billed, paid, outstanding, overdue

## Multi-tenancy model
Single database, shared schema. Every tenant-owned table has `business_id`. A global scope and a `BelongsToBusiness` trait enforce isolation, and tests prove one tenant can never read another's data.

## Run
1. Copy env files:
   cp .env.example .env
   cp backend/.env.example backend/.env
   cp frontend/.env.example frontend/.env
2. Optional: add WhatsApp Cloud API credentials to backend/.env (otherwise WhatsApp runs in log-only mode)
3. docker compose up --build
4. docker compose exec backend php artisan key:generate
5. docker compose exec backend php artisan migrate --seed
6. Open:
   - App: http://localhost:5173
   - API: http://localhost:8000/api
   - Mailpit (caught emails): http://localhost:8025

## Demo logins (after seeding)
- owner@brightspark.test / password
- staff@brightspark.test / password
- owner@kemtech.test / password

## Test
docker compose exec backend php artisan test
docker compose exec frontend npm run test

## Folder structure
(see tree in this file)

## Env vars
See .env.example in each folder.

## What I learned
(fill in after building)
````

## 2. Folder structure

```
apps/invoice-saas/
├── README.md
├── .env.example
├── docker-compose.yml
├── backend/
│   ├── Dockerfile
│   ├── .env.example
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ClientController.php
│   │   │   │   ├── InvoiceController.php
│   │   │   │   ├── InvoiceSendController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   ├── TeamController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── BusinessSettingsController.php
│   │   │   │   └── PublicInvoiceController.php
│   │   │   ├── Middleware/{EnsureRole,SetTenant}.php
│   │   │   ├── Requests/{RegisterRequest,StoreClientRequest,StoreInvoiceRequest,SendInvoiceRequest,StorePaymentRequest}.php
│   │   │   └── Resources/{ClientResource,InvoiceResource,PaymentResource,UserResource}.php
│   │   ├── Models/
│   │   │   ├── Concerns/BelongsToBusiness.php
│   │   │   ├── Scopes/BusinessScope.php
│   │   │   └── {Business,User,Client,Invoice,InvoiceItem,Payment,MessageLog}.php
│   │   ├── Enums/{Role,InvoiceStatus,Channel,MessageStatus}.php
│   │   ├── Policies/{ClientPolicy,InvoicePolicy,TeamPolicy}.php
│   │   ├── Services/
│   │   │   ├── InvoiceNumberService.php
│   │   │   ├── InvoiceTotalsService.php
│   │   │   ├── InvoicePdfService.php
│   │   │   └── WhatsAppService.php
│   │   ├── Jobs/{SendInvoiceEmail,SendInvoiceWhatsApp,SendOverdueReminder}.php
│   │   ├── Mail/InvoiceMail.php
│   │   ├── Console/Commands/MarkOverdueInvoices.php
│   │   └── Providers/AppServiceProvider.php
│   ├── config/{services.php,invoice.php}
│   ├── database/
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/{DatabaseSeeder,DemoTenantSeeder}.php
│   ├── resources/views/
│   │   ├── pdf/invoice.blade.php
│   │   └── emails/invoice.blade.php
│   ├── routes/{api.php,web.php,console.php}
│   ├── storage/app/{logos,invoices}/
│   └── tests/
│       ├── Feature/{AuthTest,TenantIsolationTest,RoleAccessTest,ClientTest,InvoiceTest,PdfTest,SendInvoiceTest,PaymentTest,PublicLinkTest,OverdueCommandTest}.php
│       └── Unit/{InvoiceTotalsTest,InvoiceNumberTest}.php
└── frontend/
    ├── Dockerfile
    ├── .env.example
    ├── package.json
    ├── vite.config.ts
    └── src/
        ├── main.ts
        ├── App.vue
        ├── router/index.ts
        ├── api/client.ts
        ├── stores/{auth,clients,invoices,dashboard}.ts
        ├── types/index.ts
        ├── views/
        │   ├── auth/{Login,Register}.vue
        │   ├── Dashboard.vue
        │   ├── clients/{ClientList,ClientForm}.vue
        │   ├── invoices/{InvoiceList,InvoiceForm,InvoiceDetail}.vue
        │   ├── team/TeamList.vue
        │   └── settings/BusinessSettings.vue
        ├── components/{Navbar,StatusBadge,LineItemsEditor,SendInvoiceModal,PaymentModal,StatCard}.vue
        ├── utils/{formatNaira,formatDate}.ts
        └── __tests__/
```

## 3. Database schema

| Table | Columns |
|---|---|
| `businesses` | id, name, slug (unique), email, phone, address, logo_path (nullable), brand_color (default `#0F766E`), currency (default `NGN`), default_tax_percent (decimal 5,2), next_invoice_number (unsigned int, default 1), invoice_prefix (default `INV`), payment_instructions (text, e.g. bank details), timestamps |
| `users` | id, business_id (FK), name, email (unique), password, role (`owner`/`admin`/`staff`), last_login_at, timestamps |
| `clients` | id, business_id (FK), name, email (nullable), phone (nullable, E.164), company, address, notes, timestamps, soft deletes |
| `invoices` | id, business_id (FK), client_id (FK), created_by (FK users), number (e.g. `INV-0001`), status (`draft`/`sent`/`partially_paid`/`paid`/`overdue`/`void`), issue_date, due_date, subtotal_kobo, discount_kobo, tax_kobo, total_kobo, amount_paid_kobo (default 0), notes, terms, sent_at (nullable), paid_at (nullable), public_token (uuid), timestamps. Unique index on (`business_id`, `number`) |
| `invoice_items` | id, invoice_id (FK), description, quantity (decimal 10,2), unit_price_kobo, line_total_kobo, position |
| `payments` | id, business_id (FK), invoice_id (FK), amount_kobo, method (`bank_transfer`/`cash`/`card`/`pos`/`other`), reference (nullable), paid_on (date), recorded_by (FK users), notes, timestamps |
| `message_logs` | id, business_id (FK), invoice_id (FK), channel (`email`/`whatsapp`), recipient, status (`queued`/`sent`/`failed`), provider_message_id (nullable), error (nullable), attempts (int), timestamps |
| `personal_access_tokens` | Sanctum default |

Rules:
- All money is stored in **kobo** as integers. Format as naira only in the UI and the PDF.
- Invoice numbers are generated inside a DB transaction with `lockForUpdate()` on the business row, so numbers never duplicate under concurrency.
- Status is derived and updated server-side from payments and due date; never trust a client-sent status.

## 4. Roles and permissions

| Action | Owner | Admin | Staff |
|---|---|---|---|
| Manage business settings and logo | yes | yes | no |
| Invite or remove team members | yes | yes (not owners) | no |
| Delete business | yes | no | no |
| Create and edit clients | yes | yes | yes |
| Delete clients | yes | yes | no |
| Create and edit draft invoices | yes | yes | yes |
| Send invoices | yes | yes | yes |
| Void invoices | yes | yes | no |
| Record payments | yes | yes | yes |
| Delete payments | yes | yes | no |
| View dashboard totals | yes | yes | yes |

## 5. API contract

All routes except register, login, and public links require `Authorization: Bearer <token>`.

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/auth/register` | Create business plus owner, return token |
| POST | `/api/auth/login` | Return token and user |
| POST | `/api/auth/logout` | Revoke current token |
| GET | `/api/me` | Current user and business |
| GET/PUT | `/api/business` | View and update settings (owner/admin) |
| POST | `/api/business/logo` | Upload logo (png/jpg, max 1 MB) |
| GET/POST | `/api/team` | List members and invite one (owner/admin) |
| PATCH/DELETE | `/api/team/{user}` | Change role and remove member |
| GET/POST | `/api/clients` | List (search, paginate) and create |
| GET/PUT/DELETE | `/api/clients/{client}` | Show, update, delete |
| GET/POST | `/api/invoices` | List (filter by status, client, date) and create |
| GET/PUT | `/api/invoices/{invoice}` | Show and update (only while `draft`) |
| POST | `/api/invoices/{invoice}/void` | Void an invoice (owner/admin) |
| POST | `/api/invoices/{invoice}/duplicate` | Copy as a new draft |
| GET | `/api/invoices/{invoice}/pdf` | Download PDF (auth) |
| POST | `/api/invoices/{invoice}/send` | Queue sending via `email`, `whatsapp`, or both |
| GET | `/api/invoices/{invoice}/messages` | Delivery log |
| POST | `/api/invoices/{invoice}/payments` | Record a payment |
| DELETE | `/api/payments/{payment}` | Remove a payment (owner/admin) |
| GET | `/api/dashboard` | Totals and recent activity |
| GET | `/i/{public_token}` | Public invoice view (no auth) |
| GET | `/i/{public_token}/pdf` | Public PDF download (no auth) |

Request body for `POST /api/auth/register`:

```json
{
  "business_name": "Bright Spark Electricals",
  "name": "Chika Eze",
  "email": "owner@brightspark.test",
  "password": "password",
  "password_confirmation": "password"
}
```

Request body for `POST /api/invoices`:

```json
{
  "client_id": 3,
  "issue_date": "2026-10-02",
  "due_date": "2026-10-16",
  "discount_kobo": 0,
  "tax_percent": 7.5,
  "notes": "Thank you for your business.",
  "items": [
    { "description": "Inverter installation (5kVA)", "quantity": 1, "unit_price_kobo": 15000000 },
    { "description": "Cabling and conduit", "quantity": 20, "unit_price_kobo": 250000 }
  ]
}
```

Request body for `POST /api/invoices/{invoice}/send`:

```json
{ "channels": ["email", "whatsapp"], "message": "Hi, please find your invoice attached." }
```

Request body for `POST /api/invoices/{invoice}/payments`:

```json
{ "amount_kobo": 10000000, "method": "bank_transfer", "reference": "TRF-883920", "paid_on": "2026-10-05" }
```

## 6. Business logic rules

- **Totals** (`InvoiceTotalsService`): subtotal = sum of `quantity × unit_price_kobo`, rounded per line; tax is computed on (subtotal − discount); total = subtotal − discount + tax. Always recalculated server-side.
- **Status transitions**: `draft → sent` on first successful send; `sent → partially_paid → paid` from payments; `sent/partially_paid → overdue` when `due_date` passes with a balance; `void` is final. Paid or void invoices cannot be edited.
- **Overpayment** is rejected: a payment cannot exceed the outstanding balance.
- **Sending**: the controller creates `message_logs` rows as `queued` and dispatches one job per channel. Jobs retry 3 times with backoff (30s, 2m, 10m) and mark the log `failed` with the error after the last attempt.
- **Email**: renders `emails/invoice.blade.php`, attaches the PDF, and includes the signed public link.
- **WhatsApp**: calls the WhatsApp Cloud API with a document message (the PDF) or a template message with the public link. If credentials are missing, log the payload and mark the message `sent` (log-only mode) so the demo works without Meta approval.
- **Phone numbers**: normalise Nigerian formats (`0801…` becomes `+234801…`) before storing.
- **Public link**: uses the unguessable `public_token`; it is read-only and shows no other tenant data.
- **Scheduler**: runs daily at 08:00 to mark overdue invoices, and sends one reminder 3 days before the due date and one on the first overdue day.
- **Tenant isolation**: `BusinessScope` adds `where business_id = auth user's business`, and `BelongsToBusiness` sets `business_id` on create. Route model binding resolves through the scope, so another tenant's ID returns 404.

## 7. Seed data

`DemoTenantSeeder` creates two tenants so isolation is visible in the demo.

```json
{
  "businesses": [
    {
      "name": "Bright Spark Electricals", "slug": "bright-spark", "email": "billing@brightspark.test",
      "phone": "+2348030000001", "address": "12 Zik Avenue, Awka, Anambra", "brand_color": "#0F766E",
      "invoice_prefix": "INV", "default_tax_percent": 7.5,
      "payment_instructions": "Bank: Demo Bank | Acct: 0123456789 | Name: Bright Spark Electricals",
      "users": [
        { "name": "Chika Eze", "email": "owner@brightspark.test", "role": "owner" },
        { "name": "Ngozi Okafor", "email": "admin@brightspark.test", "role": "admin" },
        { "name": "Tunde Bello", "email": "staff@brightspark.test", "role": "staff" }
      ],
      "clients": [
        { "name": "Ada Obi", "company": "Obi Stores", "email": "ada@example.com", "phone": "+2348012345678" },
        { "name": "Emeka Nwosu", "company": "Nwosu Pharmacy", "email": "emeka@example.com", "phone": "+2348098765432" },
        { "name": "Funke Adeyemi", "company": "Adeyemi Hotels", "email": "funke@example.com", "phone": "+2347011122233" },
        { "name": "Ifeanyi Okeke", "company": "Okeke Logistics", "email": "ify@example.com", "phone": "+2348155566677" }
      ],
      "invoices": [
        { "client": "Ada Obi", "status": "paid", "items": [["Shop wiring", 1, 8500000], ["Distribution board", 1, 2200000]] },
        { "client": "Emeka Nwosu", "status": "sent", "items": [["Solar panel install (3kW)", 1, 21000000]] },
        { "client": "Funke Adeyemi", "status": "partially_paid", "items": [["Generator changeover switch", 2, 1800000], ["Labour", 1, 1500000]] },
        { "client": "Ifeanyi Okeke", "status": "overdue", "items": [["Warehouse lighting", 12, 450000]] },
        { "client": "Ada Obi", "status": "draft", "items": [["Maintenance retainer (monthly)", 1, 3000000]] }
      ]
    },
    {
      "name": "KemTech Solutions", "slug": "kemtech", "email": "hello@kemtech.test",
      "phone": "+2348030000002", "address": "5 Upper Iweka Road, Onitsha, Anambra", "brand_color": "#1D4ED8",
      "invoice_prefix": "KT", "default_tax_percent": 7.5,
      "users": [{ "name": "Kem Ugwu", "email": "owner@kemtech.test", "role": "owner" }],
      "clients": [
        { "name": "Blessing Udo", "company": "Udo Foods", "email": "blessing@example.com", "phone": "+2348033344455" }
      ],
      "invoices": [
        { "client": "Blessing Udo", "status": "sent", "items": [["Website design", 1, 12000000], ["Hosting (1 year)", 1, 1500000]] }
      ]
    }
  ],
  "password_for_all_demo_users": "password"
}
```

## 8. Env files

`backend/.env.example` (key lines):

```
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173
DB_HOST=mysql
DB_DATABASE=invoices
DB_USERNAME=invoices
DB_PASSWORD=secret
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_HOST=redis
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS=billing@invoice-saas.test
MAIL_FROM_NAME="Invoice SaaS"
WHATSAPP_ENABLED=false
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_API_VERSION=v20.0
WHATSAPP_BASE_URL=https://graph.facebook.com
INVOICE_REMINDER_DAYS_BEFORE_DUE=3
```

`frontend/.env.example`:

```
VITE_API_URL=http://localhost:8000/api
```

## 9. docker-compose services

| Service | Purpose |
|---|---|
| `nginx` | Serves the API on port 8000 |
| `backend` | php-fpm running the Laravel app |
| `queue` | `php artisan queue:work --tries=3` |
| `scheduler` | `php artisan schedule:work` |
| `mysql` | Database with a persistent volume |
| `redis` | Queues and cache |
| `mailpit` | Catches outgoing mail (UI on 8025) |
| `frontend` | Vite dev server on 5173 |

## 10. Test checklist (what Codex must cover)

- Registering creates a business and an owner, and returns a token.
- A user from tenant A gets 404 on tenant B's clients, invoices, and payments, for show, update, and delete.
- Listing endpoints never return another tenant's rows.
- Staff cannot access settings, team management, void, or delete endpoints (403).
- Admin cannot remove or demote an owner.
- Invoice totals are computed server-side; client-sent totals are ignored.
- Tax and discount math is correct, with rounding edge cases (unit test).
- Invoice numbers are sequential per business and independent between businesses.
- Concurrent invoice creation never produces duplicate numbers.
- Only draft invoices can be edited.
- PDF endpoint returns `application/pdf` with a non-empty body that contains the invoice number.
- Send endpoint queues one job per channel (`Queue::fake`) and creates `queued` message logs.
- Email job sends mail with the PDF attached (`Mail::fake`).
- WhatsApp job calls the Cloud API (`Http::fake`) and, with credentials missing, runs in log-only mode.
- A failed send retries and ends as `failed` with the error stored.
- Recording a payment moves the invoice to `partially_paid`, then `paid`; overpayment is rejected with 422.
- Deleting a payment recalculates the invoice status.
- Public link returns the invoice and PDF without auth, and a wrong token returns 404.
- Overdue command marks only past-due, unpaid invoices and sends reminders once.
- Phone normalisation converts `08012345678` to `+2348012345678`.

## 11. Codex prompt

```
Read AGENTS.md. Work only inside apps/invoice-saas.

1. Write apps/invoice-saas/README.md first, using the spec I provide below.
2. Scaffold backend (Laravel 11 with Sanctum and dompdf) and frontend (Vue 3 + Vite + TS + Pinia) in the exact folder structure given.
3. Implement the migrations, models, BelongsToBusiness trait with BusinessScope, policies, role middleware, services, jobs, and API endpoints per the schema, roles table, and API contract.
4. Store all money as integer kobo. Always recompute totals server-side.
5. Generate invoice numbers inside a transaction with a row lock.
6. Build the PDF template (resources/views/pdf/invoice.blade.php) with logo, brand colour, line items, totals, payment instructions, and status stamp.
7. Implement queued email and WhatsApp sending with retries, backoff, and message_logs. WhatsApp must support log-only mode.
8. Implement the daily overdue and reminder scheduled command.
9. Build the Vue views listed in the folder structure, with role-aware navigation.
10. Write every test in the checklist.
11. Finish with docker-compose.yml (nginx, php-fpm, queue worker, scheduler, mysql, redis, mailpit, frontend) so the README run steps work unchanged.

[paste sections 2 to 10 of this spec here]
```

## 12. Setup commands (after the README is in place)

```bash
cd apps/invoice-saas
cp .env.example .env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
docker compose up --build
docker compose exec backend php artisan key:generate
docker compose exec backend php artisan migrate --seed
```

Two suggestions for the portfolio: record a short GIF of sending an invoice and watching it land in Mailpit, and add a screenshot of the tenant-isolation test passing. Both show the multi-tenant and queue work quickly.

If you want, I can do the same spec for the next project, such as `rag-docs-qa` or `hotspot-portal`.