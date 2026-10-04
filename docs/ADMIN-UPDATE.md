# Platform administration update — 4 October 2026

This update is cumulative: it includes the prior branding editor, logo-path repair, Cloudinary storage and favicon uploads. No new Composer or npm dependencies are required. It does not deploy to Hostinger automatically until your GitHub delivery setup is configured and exercised.

## What is implemented

- `/admin`: explicit platform administrators only. Business owners and business admins do not receive platform access automatically.
- Integrations: SMTP sender/host/port/TLS/username/password, Cloudinary credentials/storage mode and WhatsApp token/phone ID/version. Settings are encrypted in MySQL with Laravel's existing APP_KEY. Passwords and API keys are never returned to the browser after saving.
- Tests: send a real SMTP test email to an entered recipient; read-only authenticated Cloudinary usage and WhatsApp phone-number checks. Provider checks confirm access, not full image upload or WhatsApp message delivery. Errors are generic and exclude credentials/provider response bodies.
- Registered-user search and pagination across businesses; totals; daily registrations and distinct signed-in users for the last 30 days, in Africa/Lagos. API supports 1–90 days. Recently active means an authenticated API request in the last five minutes, not an exact online presence count.
- Activity metadata: registration, successful sign-in and authenticated changes, including failed actions that reach tracking middleware. No request bodies, credentials, IP addresses, query strings or tokens. Existing daily sign-in history cannot be reconstructed; event collection begins after installation. Account deletion removes foreign-key associations, retaining an anonymous action record until pruning.
- Queued payment emails: owner notices and customer receipts, independently switchable. Triggered for each newly recorded partial or full payment. Includes invoice number, recorded amount, payment date and balance at recording. Owner and customer addresses are deduplicated. Default switches are on. No historical payments are emailed automatically.
- Delivery status, attempts and retry for failed payment email notifications. Deleted payments cancel unsent notifications; already delivered emails cannot be withdrawn. The existing queue cron is still required.
- `/admin` → **Branding & favicon upload** → Browser favicon: square PNG/JPG up to 1 MB, normalized to a 64 × 64 PNG. This is one platform-wide browser icon. Business logos remain separate.
- AdSense preparation: validated publisher/slot IDs, disabled by default, ads.txt response and one placement on `/about`. `/privacy` contains a starting service disclosure. Ad scripts are absent from private workspaces and signed invoice pages. Public advertising requires site approval, a verified consent integration and positive consent before loading Google.

There is no payment gateway in this version. A notice confirms a payment entered by the business team, not independently verified settlement. Mail failure does not undo the payment. SMTP acceptance is not proof of inbox delivery. Delivery guards prevent ordinary duplicate job processing, but SMTP has no exactly-once guarantee if a worker crashes after sending and before saving its status.

## Install on your current Hostinger layout

These steps are for your original backend at `/home/u491120861/invoice-backend`, not an adopted versioned release. If already using `invoice-deploy`, use the GitHub workflow section below instead.

1. Download a database backup through hPanel and back up the private backend and public frontend. Keep the current `.env`, APP_KEY, storage files and database. Stop/disable the invoice queue and reminder cron jobs during the brief update window, and run `cd ~/invoice-backend` then `/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan down --no-ansi` before replacing application files.
Before migration, privately verify the app's configured database is `u491120861_invoice`, and that its compiled-view path and cache store/prefix are not shared with your other apps. Never paste credentials to prove this. The PHP binary does not select an app: the absolute Artisan path does. Queue restart is unnecessary when the cron worker has stopped; each new invocation loads the updated code. No other-site cache purge or PHP service restart is part of this update.

2. Extract `deployment/invoice-admin-backend-update.zip` **inside** `/home/u491120861/invoice-backend`, replacing application files. It contains no `.env`, vendor, storage, uploads or database dump. No dependency installation is needed for an existing installation with the supplied locked dependencies.
3. In SSH, run:

```sh
cd ~/invoice-backend
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan config:clear --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan migrate --force --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan route:clear --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan view:clear --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan platform:admin YOUR_REGISTERED_EMAIL --no-ansi
```

Replace `YOUR_REGISTERED_EMAIL` with your own existing account email. Do not run `key:generate`, demo seeders, `migrate:fresh` or database resets. Migration adds integration settings, activity logs, last_seen_at and payment notification tables, plus earlier missing platform/media migrations.

4. Extract `deployment/invoice-admin-frontend-update.zip` **inside** `/home/u491120861/domains/nexaibyte.com/public_html/invoice`. Replace `index.html`, `.htaccess`, `release.json` and included assets. **Keep the existing `backend` directory and its gateway index.php.** Do not upload private backend code to the public folder. The archive is built with `/backend/api`.
5. Run `/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan up --no-ansi` from `~/invoice-backend`, then re-enable your existing queue/reminder cron jobs. Sign out, sign in, hard-refresh and open `https://invoice.nexaibyte.com/admin`. The sidebar says **Platform admin**. If it is absent, verify the admin grant, sign in again and ensure the new frontend index.html/assets were uploaded.
6. Open **Branding & favicon upload** and upload a square icon. Browser caching may require a hard refresh or new tab. Changing a business logo does not change the platform favicon.

### Additional retention cron

Add a third cron job in hPanel → Cron Jobs → **Custom**. Put only this command in **Command to Run**:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan platform:prune-activity --no-ansi
```

Run once daily, e.g. minute `0`, hour `2`, and `*` for day/month/weekday. This deletes only activity metadata older than 90 days. It does not delete accounts, invoices or payment notices. The panel may use UTC; check its timezone. Keep the existing every-minute queue worker and daily invoice reminders. Do not also add schedule:run for those same jobs on this shared host.

### Configure email without editing .env

At `/admin` → **Integrations**:

1. Choose `smtp`. For Hostinger email, use the exact SMTP details shown for your mailbox; commonly host `smtp.hostinger.com`, port `587`, security `tls` (STARTTLS), or port `465`, security `ssl` (implicit TLS). Use the full mailbox address as username and that mailbox password. Sender address should be a mailbox/domain the provider authorizes.
2. Set sender name/address. Leave payment owner/customer notices checked if desired. Save integrations.
3. Enter your own recipient address and click **Send test email**. Confirm receipt, including spam. Configure SPF/DKIM/DMARC through your email provider for your sending domain.
4. Existing queued messages use the current saved settings when processed. Leaving mode as `log` does not send messages; payment notices processed in that mode say **logged**. Switch to SMTP before recording payments you want emailed. Logged historical notices are not automatically resent.

Empty secret fields preserve saved values. **Clear stored secret** explicitly removes a secret, including an environment fallback. Non-secret fields are visible to platform admins. Other business users cannot read or update the settings. The supported API credentials are Cloudinary and WhatsApp; payment-gateway integrations are not included.

Initial environment values remain the fallback until interface settings are saved. Once saved, database settings take precedence even with config:cache. HTTP middleware refreshes runtime config; invoice and payment mail jobs refresh it before processing. Keep database/APP_KEY/APP_URL/queue/bootstrap deployment settings in `.env`.

Back up APP_KEY together with database backups. Changing/losing APP_KEY makes encrypted provider credentials unreadable. Do not paste production secrets in chats or commit them. Restrict platform admin access to the operator's account. Mail log mode writes email contents to private application logs.

## AdSense readiness and consent integration

This is preparation, not automatic monetization or Google approval. No Google account, ad unit or certified consent platform is created by this update. Ads remain disabled by default, and the operator must review the privacy page, content, traffic policies and provider terms before enabling them.

1. Obtain approval for the appropriate site/domain in AdSense. Create an ad unit and copy `ca-pub-…` and the numeric slot ID into **Advertising**.
2. Verify your site's ads.txt requirements in AdSense. The frontend's root `/ads.txt` redirects to `/backend/ads.txt`, which dynamically emits the configured Google publisher line. Google supports ads.txt redirects; verify the served response after upload. Requirements for a subdomain can also involve your main domain, which this package does not edit.
3. Install and verify the required consent manager. Where Google requires a certified CMP (EEA, UK, Switzerland), a homemade checkbox/banner is not sufficient. The app deliberately includes no fake consent banner.
4. The CMP integration may emit this application hook only after its actual advertising consent checks pass:

```js
window.dispatchEvent(new CustomEvent('invoice-ad-consent', {
  detail: { advertising: true }
}));
```

The public placement emits `invoice-ad-consent-ready` after loading settings; use that event to replay the CMP's verified current consent. Revocation emits the same consent event with `advertising: false`; if an ad script was loaded, the page reloads so it is removed. Google/CMP consent signals still need configuring through the CMP, not just this application hook.

5. Confirm approval and verified CMP readiness in the admin form, then enable the placement. Without the consent hook, no ad loads even when enabled. Navigation away from a page containing an ad script performs a full document navigation so third-party ad code does not remain in a private SPA workspace. Disable automatic ads that could place content outside the reserved slot in your AdSense account.

Official references: [Google ads.txt redirects](https://support.google.com/adsense/answer/9785052?hl=en), [AdSense site connection](https://support.google.com/adsense/answer/7584263?hl=en), [Google-certified CMP requirements](https://support.google.com/adsense/answer/13554116?hl=en), [Cloudinary Admin API](https://cloudinary.com/documentation/admin_api), [Laravel encryption](https://laravel.com/docs/12.x/encryption).

## GitHub / versioned deployments

Publish the contents of `apps/invoice-saas` as the repository root, or extract `deployment/invoice-saas-github-ready.zip` into that repository. Commit the update, including lockfiles, new migration, tests and docs. Suggested commit: `Add platform integrations, usage reporting and payment email notifications`.

The existing workflow automatically picks up all backend/frontend tests and packages the new files. It exercises PHP 8.3/MySQL 8.0, compiled frontend, the actual `/backend` gateway, Apache routing and production-style disabled proc_open. Local MySQL validation used 8.4.3; the exact Hostinger MySQL version is still unknown. Keep AUTO_DEPLOY false until your first full GitHub/Hostinger run passes.

For a site already adopted to `invoice-deploy`, deploy through that workflow, not the legacy manual archive path. Admin settings are stored in your shared database and therefore persist across releases. Existing APP_KEY and shared storage remain preserved. Run the grant command in the active release if needed:

```sh
CURRENT=$(cat ~/invoice-deploy/current)
cd "$CURRENT/backend"
/opt/alt/php83/usr/bin/php "$CURRENT/backend/artisan" platform:admin YOUR_REGISTERED_EMAIL --no-ansi
```

Use the updated `ops/cron.sh` from this source if adding the managed retention wrapper; copy it to `~/invoice-deploy/cron.sh` and add a daily Custom command:

```sh
bash /home/u491120861/invoice-deploy/cron.sh /home/u491120861/invoice-deploy activity
```

The wrapper follows the active release and holds the shared deployment lock. Do not enable both legacy and managed cron jobs. Code rollback does not undo the new migration or encrypted settings. See CI-CD.md for adoption and rollback.

## Acceptance checklist

- A normal business owner receives 403 from every private platform endpoint and sees no Platform admin link.
- The explicitly granted operator sees users from both businesses; secrets show only configured/not configured and empty input fields after save.
- Save and test SMTP; verify a real inbox receipt. Provider test success alone does not verify deliverability.
- Save Cloudinary/WhatsApp credentials, authenticate via tests, then exercise a real upload/message separately.
- Register/login twice the same day: daily login total counts that user once. Dates cross midnight correctly in Lagos. Metadata appears in Activity; no submitted values appear.
- Upload a favicon from Branding & favicon upload; reload a new browser tab and confirm the browser icon.
- Record partial and full payments on issued invoices; owner/customer notices appear. Run the queue cron and verify status and actual email. Repeated processing of an already-sent notice does not resend it. Test failure/retry without undoing the payment.
- About and Privacy load without a login. By default no request is made to Google ad servers. Private routes contain no ad script.
- Run retention command and verify only old activity metadata is removed.

## Validation completed locally

52 backend tests / 296 assertions against isolated MySQL; 13 frontend tests including admin form/secret handling and ad consent gating; TypeScript check and production Vite build passed. Tests use fake provider responses/mail; real SMTP, Cloudinary, WhatsApp, Google approval/CMP and hosted deployment remain live acceptance checks. The new Linux/Apache GitHub run has not been executed from this machine.

For the complete file-level record and future tracking convention, see [this release record](changes/2026-10-04-platform-administration.md) and [CHANGE-TRACKING.md](CHANGE-TRACKING.md).
