# Commercial documents, products and currencies

Release: 2026-10-05. This cumulative update includes the earlier platform administration and media fixes. No remote deployment has been performed.

## Using the features

- Products: owners and business admins can create/edit/archive catalogue entries. All workspace members can select products in document line items. Selection copies the description, unit and price; later catalogue edits never change saved documents.
- Quotations: create a draft, select a client and currency, enter quantities, prices, tax, discount, validity date and terms. Issue the quotation and download its PDF. Owners/admins can record acceptance or decline. Accepted quotations convert into draft invoices; repeating conversion returns the same invoice. Issued documents cannot be edited.
- Delivery notes: create a standalone note or use Create delivery note on an issued invoice. Draft quantities can be adjusted for a partial delivery. Notes contain quantities and units, never prices. Issue, print/download, then record the recipient when delivered. This records a team-entered acknowledgement, not an electronic customer signature.
- Business settings: choose the default currency. Invoice and quotation forms also allow a currency per document. Changing a draft's currency clears its prices and discount for explicit repricing.

Quotation acceptance is recorded by your team; customer acceptance links and automatic quotation emails are not included. Delivery notes do not adjust stock or enforce cumulative delivery quantities. Multiple notes can be created from an invoice. No new cron job is required.

## Product imports and templates

Download a CSV or XLSX template from Products. Keep these seven headers in exactly this order:

```csv
sku,name,description,unit,unit_price,currency,is_active
PROD-001,Example product,Optional description,pcs,1250.00,NGN,1
```

Use UTF-8 comma-separated CSV or an XLSX workbook's first sheet. Prices are major currency units with the correct decimal precision. SKU is normalized to uppercase and must be unique within your business. Units are text such as pcs, hours or kg; active is 1 or 0. Files are limited to 2 MB and 500 data rows. XLSX contents are bounded to 20 MB uncompressed; formulas, macros, external links and XML entities are rejected. Legacy XLS is unsupported.

Preview first: it saves nothing and reports row errors and create/update counts. Confirmation validates the file again and commits all rows together. Invalid files save nothing. Existing SKUs fail unless you explicitly enable Update existing products; enabled updates replace the catalogue fields for those SKUs. Other businesses' products remain inaccessible.

XLSX requires PHP ZipArchive (zip extension); CSV works without it. Templates use your business's default currency. Remove the example row or replace it before import.

## Currency behavior and API compatibility

The bundled list has 165 ISO currency/fund codes with defined decimal precision and country search. Source: [SIX, the ISO 4217 maintenance agency](https://www.six-group.com/en/products-services/financial-information/market-reference-data/data-standards.html), current-list snapshot published 2026-09-17. Codes with undefined minor units (such as precious metals and testing codes) are excluded. The list is local and does not depend on a live service.

There is no foreign-exchange conversion. Default changes affect new documents; existing invoices keep their currency and precision. Migration marks all pre-existing invoices NGN with two decimal places, preserving every stored amount. If historical data was actually entered in another currency, review it separately before rollout.

Legacy API names ending in `_kobo` now mean integer minor units of the document currency. NGN remains kobo; JPY 100 is stored as 100, KWD 1.234 as 1234. Clients must use `currency` and `currency_minor_units` rather than assuming division by 100. Products use `unit_price_minor`. Payments inherit invoice currency. Dashboard headline totals use the business default and show separate currency balances; they never sum incompatible currencies.

Currency snapshots are `backend/resources/data/currencies.json` and `frontend/src/data/currencies.json`. Refresh both together from the official list, review effective-date amendments and run currency/document tests before release. A document's stored precision remains authoritative after a list update.

## Existing Hostinger installation

Use these instructions for the existing private backend at `/home/u491120861/invoice-backend` and public frontend at `/home/u491120861/domains/nexaibyte.com/public_html/invoice`. If you adopted the versioned CI deployment layout, use its release/deploy workflow instead of overwriting a release directory.

1. Back up **only the invoice database** (`u491120861_invoice`), private backend, public frontend and private `.env`. Preserve APP_KEY, uploaded storage and integration settings. Pause only this application's queue/reminder cron entries and allow its current worker to finish.
2. Confirm the application target and configuration:

```sh
ls /home/u491120861/invoice-backend/artisan
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan about --only=environment --no-ansi
/opt/alt/php83/usr/bin/php -r "echo extension_loaded('zip') ? 'ZIP enabled' : 'ZIP unavailable: use CSV';"
```

Verify the private `.env` has `DB_DATABASE=u491120861_invoice` and dedicated invoice credentials. Do not paste secrets into logs or support messages.

3. Put this application in maintenance mode:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan down --no-ansi
```

4. Extract `invoice-commercial-backend-update.zip` into the private backend. It contains application code and migrations, excluding `.env`, vendor and customer storage. No Composer package was added, so the existing vendor folder is reused.
5. Run the targeted update:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan config:clear --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan migrate --force --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan route:clear --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan view:clear --no-ansi
```

These commands use this application's absolute Artisan path. Confirm its configured cache/view paths remain dedicated to invoice-backend, as described in ADMIN-UPDATE.md. No PHP service restart, global cache flush, database reset or shared-host queue restart is needed.

6. Extract `invoice-commercial-frontend-update.zip` into the subdomain public directory. Keep the existing `backend` gateway directory and storage arrangement. The frontend uses `/backend/api`.
7. Bring this app back and resume its existing cron entries:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan up --no-ansi
```

The new migration creates products, commercial_documents and commercial_document_items; adds invoice currency/precision, line units and separate business numbering counters. Earlier pending migrations in the cumulative release also run: review ADMIN-UPDATE.md if that update was not installed. Existing data is preserved. Do not run migrate:fresh or seed demo users on production. Migration rollback drops the new document/catalogue data; use backups for a planned full rollback and do not automatically reverse schema after users begin using it.

## Acceptance and validation

After rollout: verify `/backend/up`; create/import a product using preview and confirmation; create a quotation, issue it, record acceptance, convert twice and confirm only one invoice; download both PDFs; create and deliver a quantity-only note; create a USD and JPY invoice; check an existing NGN invoice and separate dashboard balances. Test a staff account and a second business for access isolation. Check XLSX and CSV independently.

Local validation: 59 MySQL backend tests / 356 assertions, 16 frontend tests, TypeScript and production Vite build passed. Backend checks cover import atomicity, tenant isolation, currency precision, document lifecycle, PDFs and idempotent conversion. CI now installs zip in both PHP test and production-mirror images. GitHub/Linux-container execution and actual Hostinger deployment remain unverified. SMTP/provider delivery was not exercised by this update.

## Reproducing packages

```sh
python scripts/package-commercial-update.py
python scripts/package-source.py
```

Build frontend with `VITE_API_URL=/backend/api` before packaging. The commercial packager emits backend/frontend ZIPs and SHA-256 sidecars; the source packager emits the GitHub-ready source ZIP. Secrets, installed dependencies and customer data are excluded. Commit source and documentation together; generated deployment files are ignored by Git.
