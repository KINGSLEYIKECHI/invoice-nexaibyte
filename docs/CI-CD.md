# Delivery guide: GitHub Actions → Hostinger shared hosting

This guide applies to the existing Invoice SaaS installation at `https://invoice.nexaibyte.com`. Publish the **contents of `apps/invoice-saas` as the GitHub repository root**. GitHub must see `.github/workflows/pipeline.yml` at the repository root, alongside `backend`, `frontend`, `ops`, and `scripts`.

## 1. Delivery model

Every pull request and push to `main` runs verification. A successful run produces a versioned `.tar.gz` package plus SHA-256 checksum. A manual workflow run can deploy the verified package; automatic deployment after a successful `main` push is optional. Production credentials are available only to the deploy job, never to pull-request test jobs.

```mermaid
flowchart LR
 A[Push or pull request] --> B[PHP 8.3 + MySQL tests]
 B --> C[Frontend tests + TypeScript + Vite]
 C --> D[Production Composer dependencies]
 D --> E[Versioned package + checksum]
 E --> F[Apache + restricted PHP + browser tests]
 F --> G[Verified artifact]
 G --> H[Production environment gate]
 H --> I[SSH upload + DB snapshot + migration]
 I --> J[Switch public symlink]
 J --> K[Check health, release ID and settings API]
 K --> L[Keep release or restore previous code]
```

The build uses lockfiles (`composer.lock`, `package-lock.json`) and exact GitHub Action commit pins. Dependabot proposes dependency and action updates weekly. The tested package is uploaded and deployed unchanged: the shared server does not need Node or Composer to deploy it. `VITE_API_URL=/backend/api` gives the same artifact a same-origin API, allowing the package to work on a future staging hostname too.

### What production parity means

| Layer | CI environment | Hostinger |
|---|---|---|
| PHP | 8.3, required extensions | Your PHP 8.3 installation |
| SQL | Disposable MySQL 8.0 service | Your hosted MySQL/MariaDB service |
| Web routing | Apache, real `.htaccess`, `/backend` API | Hostinger web server with compatible rewrite rules |
| Frontend | Compiled Vite assets | Same compiled assets |
| Runtime | Production env, debug off, file cache/sessions, DB queue | Same drivers |
| Restrictions | `proc_open` disabled for packaged runtime | Restriction encountered on your server |
| Credentials/data | Disposable CI DB, fake users, mail log | Private production `.env` and real customer data |
| URL/TLS | Local HTTP inside CI | Public HTTPS and Hostinger SSL |

The test suite uses testing-safe mail, cache and queue doubles for deterministic unit/feature tests. Browser tests then use the production package with production drivers. It verifies registration, authenticated dashboard, platform-access denial, invoice totals, PDF download, refresh routing and inaccessible backend source files. Deployment fixture tests also exercise activation, manual rollback, failed health checks and failed migrations on the Linux runner. Actual SMTP/WhatsApp delivery, Hostinger TLS/permissions/OPcache behaviour, exact PHP patch and hosted DB version require a hosted smoke test. CI never copies production customer records or secrets.

The concurrency regression test uses its own SQLite file; other backend integration tests run on MySQL, and a guard test explicitly checks that MySQL is being used. MySQL has been confirmed for your site; its exact version has not yet been supplied. Confirm your hosted DB version in hPanel; if it is MariaDB, change the CI service image and startup health check to match that family/version before claiming exact database parity.

## 2. Repository setup on Windows

Open PowerShell:

```powershell
cd C:\laragon\www\invoice-generator\apps\invoice-saas
git init
git branch -M main
git add .
git status --short
git commit -m "Prepare tested Hostinger delivery pipeline"
```

Create an empty **private** GitHub repository under your account/company. Do not initialize it with a README if pushing this existing project. Add its URL:

```powershell
git remote add origin https://github.com/YOUR_ORGANISATION/YOUR_REPOSITORY.git
git push -u origin main
```

Do not commit `.env`, private keys, backups, `vendor`, `node_modules`, or generated packages. `.gitignore` excludes them. Review `git status` before every commit. This project is currently supplied as a folder; no GitHub repository or remote has been created for you.

In GitHub, protect `main`: require pull requests and the `verify` check, block force pushes, and restrict who may merge. Repository owners should review changes to workflows and `ops` scripts. Keep Actions permissions at read-only by default; this workflow requests only `contents: read`.

## 3. First server adoption: existing installation

This is a **one-time migration of the deployment layout**, not a database reset. Before running it, take a database and files backup through Hostinger and record your existing cron commands. The script retains the original backend and renames the original public folder as a local recovery copy. It needs SSH, `rsync`, `flock`, `tar`, `sha256sum`, `curl`, and symlink support.

Upload/extract `invoice-ci-server-setup.zip` into `/home/u491120861/invoice-ci-setup` using hPanel. The ZIP contains the setup script and its supporting files directly at archive root.

Run these read-only prerequisites over SSH:

```sh
command -v rsync flock tar sha256sum curl
/opt/alt/php83/usr/bin/php -m
ls ~/invoice-backend/.env ~/invoice-backend/vendor/autoload.php
ls -ld ~/domains/nexaibyte.com/public_html/invoice
```

Check that PHP has BCMath, DOM, fileinfo, GD, mbstring and PDO MySQL. Adjust `PHP_BIN` in the uploaded `server-config.example.sh` if your CLI PHP path changes. The default paths match your current account and domain. **Read and adjust the script/config before using it on another server.**

After backing up, run:

```sh
bash ~/invoice-ci-setup/initialize-hostinger.sh
```

It creates:

```text
/home/u491120861/invoice-deploy/
├── config.sh                  server-only paths and URLs
├── current                    active private release path
├── previous                   previous public release path after first update
├── deploy.lock                deployments exclude cron workers
├── worker.lock                one queue worker at a time
├── shared/
│   ├── .env                   copied once; not overwritten on deployment
│   └── storage/               logos, logs, sessions and runtime files
├── releases/
│   └── legacy-TIMESTAMP/
│       ├── backend/           private Laravel code
│       └── public/            compiled frontend and backend gateway
├── incoming/                  uploaded artifact/checksum
└── backups/                   private pre-migration SQL snapshots
```

The public subdomain path becomes a symlink:

```text
~/domains/nexaibyte.com/public_html/invoice
    → ~/invoice-deploy/releases/RELEASE_ID/public
```

Private backend/vendor/configuration files stay outside the document root. Public requests still use `/backend/api`. Shared uploads remain available at `/backend/storage`. Your `APP_KEY` and database credentials are copied from the existing installation and kept unchanged.

Open the website and confirm login, invoices and logos after adoption. If the health check fails during the initial switch, the script restores the original public directory. If an earlier prerequisite fails, it exits before changing the live public path; inspect partial setup files before retrying instead of deleting them blindly.

### Stop using the original backend path after adoption

Your editable production environment is now `~/invoice-deploy/shared/.env`. Editing `~/invoice-backend/.env` will not configure managed releases. After editing shared `.env`, rebuild the active release configuration cache with `php artisan config:cache` from its backend directory. Preserve the release view directory by setting `VIEW_COMPILED_PATH="$CURRENT/backend/bootstrap/cache/views"` first for managed releases.

For application commands:

```sh
CURRENT=$(cat ~/invoice-deploy/current)
cd "$CURRENT/backend"
php artisan platform:admin YOUR_REGISTERED_EMAIL
```

Never run `key:generate`, `migrate:fresh`, or demo seeders against production during releases.

## 4. Cron jobs for shared hosting

Remove/disable your old invoice-app cron jobs before enabling these, so they do not use the abandoned backend or compete with the new workers. The setup installs wrappers at `~/invoice-deploy/cron.sh` and `rollback.sh`.

In hPanel Cron Jobs, use **Custom** commands:

```sh
bash /home/u491120861/invoice-deploy/cron.sh /home/u491120861/invoice-deploy queue
```

Run the queue every minute. It exits after draining the queue or approximately 50 seconds, except an in-flight job can continue until its timeout. The wrapper prevents overlapping workers. `DB_QUEUE_RETRY_AFTER=180` is appended during adoption; it exceeds the 90-second job timeout and reduces duplicate reservations.

For daily reminders:

```sh
bash /home/u491120861/invoice-deploy/cron.sh /home/u491120861/invoice-deploy reminders
```

Run daily at 08:00 Africa/Lagos. If hPanel schedules in UTC, use 07:00 UTC; confirm the panel timezone first. This calls the reminder command directly because Laravel's scheduler may require `proc_open`. Do not use `schedule:work` or a permanently running worker on shared hosting.

If hPanel needs a shell wrapper for log redirection, add redirection inside `cron.sh` rather than in its PHP-command field. Laravel application errors remain in shared storage logs. Configure real SMTP separately; `MAIL_MAILER=log` does not deliver mail. WhatsApp remains disabled unless you configure the integration.

## 5. SSH authentication and GitHub production environment

In GitHub repository Settings → Environments, create **production**. Restrict deployment branches to `main`. If your GitHub plan/repository visibility supports required reviewers, enable a reviewer until the first releases are proven. The workflow tests and packages first; the environment gate is the final step before SSH deployment.

Create a dedicated deployment SSH key on your computer:

```powershell
ssh-keygen -t ed25519 -C "invoice-github-deploy" -f "$env:USERPROFILE\.ssh\invoice_github_deploy"
```

For an unattended CI key, leave the passphrase empty when prompted. Store it only in GitHub's environment secret. Add the corresponding `.pub` key in Hostinger's SSH-key area, or append it to your account's `~/.ssh/authorized_keys`; do not replace existing keys. Hostinger shared-host SSH keys normally have access to the entire hosting account, so protect the GitHub repository and production environment carefully. A separate hosting account is the strongest separation if needed later.

Add these **production environment secrets**:

| Secret | Value |
|---|---|
| `HOSTINGER_HOST` | SSH hostname/IP shown by hPanel; do not guess from the shell prompt |
| `HOSTINGER_PORT` | SSH port from hPanel (shared hosting commonly uses 65002; verify yours) |
| `HOSTINGER_USER` | `u491120861` |
| `HOSTINGER_SSH_KEY` | Entire private key, including BEGIN/END lines and actual newlines |
| `HOSTINGER_KNOWN_HOSTS` | Verified OpenSSH known-host entry for the host and port |

Obtain the server key on your computer:

```powershell
ssh-keyscan -p YOUR_SSH_PORT YOUR_SSH_HOST
```

Verify the fingerprint independently against a trusted existing SSH connection or Hostinger support before saving that output. The pipeline does **not** trust a freshly scanned key at deployment time and never disables strict host checking. Keep database/SMTP/WhatsApp credentials and `APP_KEY` on the server; GitHub does not need them.

## 6. First release and automatic updates

1. Push to `main`; open GitHub Actions and inspect the `verify` job.
2. Download the `test-evidence` artifact if browser tests fail; it includes report, failure screenshots and traces. Traces may contain CI-only account tokens; they never use production credentials.
3. Once verification passes and server adoption is complete, choose Actions → **Test, package and deploy** → Run workflow → branch `main` → enable **deploy**.
4. Approve the production environment gate if configured.
5. The job uploads the previously tested package, validates its checksum and production configuration, snapshots MySQL, runs migrations, builds caches and switches the public symlink.
6. It checks `/backend/up`, `/release.json` against the expected release ID, and the settings API. If those checks fail, it switches code back and reports a failed deployment.
7. Manually verify login, a small draft invoice, PDF, logos and a real outbound message once provider credentials are configured.

After several successful releases, add repository variable `AUTO_DEPLOY=true`. Subsequent `main` pushes deploy automatically **only after verification passes** and any environment approval gate allows it. Leaving the variable unset keeps pushes as CI/package runs. Toggle it to `false` to stop automatic delivery.

Release identifiers combine commit SHA, workflow run ID and run attempt. Rerunning a workflow makes a new unique release path. Workflow and deployment locks prevent concurrent server migrations/activation. A deployment still serving old requests requires backward-compatible schema changes, discussed below.

## 7. Normal development cycle

```powershell
git switch -c feature/improve-invoice-list
# Make and locally test your changes.
git add .
git commit -m "Improve invoice filtering"
git push -u origin feature/improve-invoice-list
```

Open a pull request. CI runs without production secrets. Review and merge it after checks pass. The `main` workflow builds/tests the merged commit and optionally deploys it. Version labels or changelog entries can be added for business-facing releases; every technical artifact is already tied to its exact commit and run.

Local quick checks:

```powershell
cd backend
composer install
php artisan test --no-ansi
cd ../frontend
npm ci
npm test
$env:VITE_API_URL='/backend/api'
npm run build
```

The quick backend check uses isolated SQLite, while CI additionally exercises MySQL. To reproduce MySQL tests yourself, create a **dedicated disposable database whose name begins with `invoice_ci`**, set DB_* environment variables, set `EXPECT_MYSQL=1`, and run:

```sh
php vendor/bin/pest --configuration phpunit.mysql.xml --colors=never
```

These tests reset their database. Never point them at a production database.

## 8. Manual artifact build/deployment fallback

Use a separate production vendor folder so you do not remove local dev tools:

```powershell
mkdir .release-backend
Copy-Item backend/composer.json,backend/composer.lock .release-backend/
composer install --working-dir=.release-backend --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader
cd frontend
$env:VITE_API_URL='/backend/api'
npm ci
npm run build
cd ..
python scripts/build-release.py --version manual-20261002-01 --vendor .release-backend/vendor
```

The resulting archive/checksum are in `artifacts`. Upload both and `ops/deploy.sh` into `~/invoice-deploy/incoming`, then run:

```sh
bash ~/invoice-deploy/incoming/deploy.sh ~/invoice-deploy manual-20261002-01
```

Prefer the verified GitHub artifact for normal releases. The supplied local release package is a fallback that passed local checks; its Linux Apache/browser run still needs a GitHub runner or Docker host.

## 9. Rollback and migration discipline

Code rollback:

```sh
bash ~/invoice-deploy/rollback.sh ~/invoice-deploy
```

It switches to the previously active release under the same lock and checks health. It preserves shared configuration, uploads and all database contents. Rollback does **not** reverse migrations or restore the SQL snapshot automatically. Automatic restoration could destroy invoices created after the snapshot.

Use **expand-and-contract migrations**: first add compatible nullable columns/tables, release code that can work with old and new schema, migrate/backfill safely, then remove old columns only in a later explicitly planned release. Do not rename/drop a live column in the same release that depends on it. Code rollback is safe only when the prior code remains compatible with the migrated schema.

If data recovery is necessary, stop writes/cron, export the current database, inspect the private snapshot, and restore in a controlled maintenance window. SQL snapshots here include base-table definitions and rows, not external uploads, stored routines, database users or grants. The current application schema uses ordinary tables. Keep independent Hostinger/off-site backups of database **and shared storage** and periodically test restoration on a disposable database.

## 10. Retention, security and operations

The scripts intentionally do not delete old releases/backups. Monitor disk space. After verifying a release, archive older private SQL backups securely and remove obsolete releases manually, keeping current and previous. Old hashed JS/CSS assets are copied forward so an already-open browser can fetch chunks after a release or rollback; those files consume extra space over time. Never delete shared storage or `.env` during cleanup.

Source packages exclude real environment files, logs, database files, backups, installed dependencies and previous ZIPs. Deployment packages include production vendor dependencies but no `.env`, runtime storage, dev test dependencies or database seeders. Public `release.json` exposes the release identifier, not secrets. `index.html` uses no-cache headers and hashed JS/CSS use long-lived immutable cache headers.

SSH deployment snapshots the database before migrations and serializes cron jobs against deployment. Public web requests can still write while the snapshot is being taken; it represents a consistent earlier point, not a transaction log. For larger data volumes, use your host's managed backups and a planned maintenance strategy.

If Hostinger caches PHP OPcache aggressively, confirm that `opcache.validate_timestamps` allows new release paths to be seen. The unique directory strategy reduces stale-code risk but does not validate the host's PHP-FPM setup locally.

## 11. Troubleshooting

| Symptom | Check |
|---|---|
| No Actions workflow appears | `.github` must be at GitHub repository root, not nested under `apps/invoice-saas` |
| Composer platform failure | PHP 8.3 extensions; package uses production dependencies and checks required extensions |
| Package discover errors | Writable `bootstrap/cache`; CI and deployment create it |
| SSH denied | Correct hPanel host/port/user, installed public key, full private-key secret |
| Host-key verification fails | Independently verify new server fingerprint; update known-host secret after verification |
| Public path not managed | Run one-time adoption; deploy scripts refuse to overwrite an ordinary directory |
| Production validation fails | Edit shared `.env`; production/debug/drivers/HTTPS URL and queue retry timeout must match |
| Health check restores old code | Read failed release's shared Laravel log, confirm `.env`, schema, extension and permissions |
| API works but frontend route 404 | Symlink/document root, `.htaccess`, rewrite support |
| Mail stays in logs | Configure real SMTP; log transport is intentional until configured |
| Jobs are queued forever | hPanel queue cron, correct wrapper/current release, worker lock, application log |
| Workflow audit fails | Review security advisory and update lockfiles in a tested PR; do not bypass security blocks blindly |

## 12. Validation status of this deliverable

See `docs/DELIVERY-VALIDATION.md` for checks actually executed on the Windows workspace. Docker is unavailable on this machine, so the full Linux Apache/Chromium workflow and Hostinger activation are not represented as locally verified. The first GitHub run is required to exercise that environment end to end. No GitHub repository, secrets, server cron jobs or deployment settings have been changed remotely by this work.

## Official references

- [GitHub deployment environments](https://docs.github.com/en/actions/concepts/workflows-and-actions/deployment-environments)
- [Hostinger SSH access](https://www.hostinger.com/support/1583245-how-to-connect-to-a-hosting-plan-via-ssh-in-hostinger/)
- [Hostinger rsync](https://www.hostinger.com/support/how-to-use-rsync-to-sync-files-and-directories-at-hostinger/)
- [Hostinger transfer and symlink support](https://www.hostinger.com/support/which-file-transfer-and-server-access-options-are-supported-at-hostinger/)
