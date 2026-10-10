# Invoice-only PO and leading-zero numbering — 2026-10-11

This is the current update guide. Packages invoice-number-format-backend-update.zip and invoice-number-format-frontend-update.zip are cumulative, including preceding settings/commercial/admin updates.

## Usage

Business settings includes document numbering. Set invoice and quotation prefix, separator (hyphen, slash or none), and minimum digit width (1–10). For INV/000125 use prefix INV, separator /, width 6 and next counter 125. To continue after 124, enable Set starting counters, choose Last number already used and enter 124; the next counter is 125. Enter numeric counters separately from their prefix. Inputs preserve leading zeros: 000132 automatically selects six digits; 000112 stays visible in the input and generates 000112. Last-used 000132 generates 000133. Digit width is a minimum numeric length: 112 at six digits is 000112, at five is 00112. 20002 at six digits is 020002; 2000 at five digits is 02000. Numbers longer than the width are never truncated. The live preview shows prefix/separator/number before saving. Invoice and quotation sequences remain independent; delivery notes keep DN format.

Existing document numbers never change. Counters cannot move backwards; refresh settings if another teammate created a document while settings was open. Already-existing formatted numbers are skipped under a business lock. Format supports prefix + separator + padded integer, not arbitrary date tokens or embedded suffixes. Default INV-0001/QUO-0001 behavior remains. Counter values must remain within the supported signed integer range.

Only invoice editors have an optional customer purchase order (PO) reference, saved and printed on invoice PDFs. Quotations do not accept or display PO references. Converting a quotation creates an invoice with a blank PO; you can enter it while editing the resulting draft invoice. Legacy quotation PO values are left in the database but are hidden and no longer copied. This is a reference field, not a purchase-order management module.

Bank account text fields are optional, and entirely empty accounts are omitted from PDFs. Existing per-document-type visibility controls remain. They are business-level current settings, as described in SETTINGS-UPDATE.md.

Line tables show their subtotal below the Amount column; quantity-only delivery notes show combined quantity (useful only if units are comparable). Quotations now show subtotal, discount, tax and final Total below the editor. Invoice and quotation summaries recalculate on input without saving. Tax applies after discount; amounts round to document minor units per line and for tax. Backend recalculation remains authoritative; invalid inputs/discount above subtotal cannot be saved. Currency selection is independent of phone calling codes.

## Converted-invoice payment details follow-up

After quotation conversion, the invoice API returns its business payment configuration and the on-screen invoice displays invoice-enabled bank accounts plus free-text payment instructions. PDFs already include the same invoice-enabled details. Quotation-only accounts stay hidden; enable Show on invoices in business settings for receiving accounts that should appear after conversion. These remain current business settings rather than quotation snapshots. No additional migration is introduced by this follow-up; the cumulative package still requires the migrations above if pending.

Follow-up validation: Previous follow-up tests verified invoice-enabled bank detail visibility; the current regression results are in the dated change record. No hosted deployment or push performed.

## Hostinger installation

For the existing installation, back up the invoice database u491120861_invoice, private backend, frontend, .env and uploaded storage. Preserve APP_KEY. Pause only invoice cron entries and wait for its running worker to finish. Verify this exact private backend and database before proceeding. Do not run these commands against another application's Artisan path.

Enter maintenance mode:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan down --no-ansi
```

Extract invoice-number-format-backend-update.zip into /home/u491120861/invoice-backend, preserving .env, vendor and storage. Apply this app's changes:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan config:clear --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan migrate --force --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan view:clear --no-ansi
```

Extract invoice-number-format-frontend-update.zip into /home/u491120861/domains/nexaibyte.com/public_html/invoice, preserving the backend gateway. No environment/Composer/cron additions or shared PHP service restart is needed. This correction introduces no new migration. The cumulative package retains migration 2026_10_10_000007, which adds business prefix/separator/padding fields and nullable po_number to invoices/commercial_documents. Pending earlier migrations also run. Existing invoice amounts and numbers remain unchanged.

After successful installation exit maintenance mode:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan up --no-ansi
```

Check https://invoice.nexaibyte.com/backend/up, then the editors/settings in your browser; resume only paused invoice cron entries. If a step fails, keep maintenance mode enabled while diagnosing or restoring your known-good application. Do not use migrate:fresh or blindly rollback schema: reversal removes saved PO references and format configuration. Database backup is required for a full planned rollback. Managed CI installations must use their release workflow and verified current release paths rather than overwriting a release.

## Acceptance and validation

Use a disposable workspace to try next=125 and last=124, confirm prefixes/padding, create invoice and quotation, attempt moving backwards, and test a second workspace. Confirm quotations have no PO field; convert one, add an optional PO to the resulting invoice, then save/edit/download its PDF. Leave all account fields blank. Change quantity, price, discount and tax and confirm totals immediately. Do not use production invoice counters for trial documents.

Validation results are recorded in docs/changes/2026-10-11-invoice-po-leading-zeros.md. Tests cover zero-prefixed inputs and numbering, invoice-only PO, optional bank details and live totals. Hostinger deployment and GitHub CI execution remain unverified for this update.

Reproduce packages after building frontend with VITE_API_URL=/backend/api: python scripts/package-number-format-update.py; python scripts/package-source.py. ZIPs exclude secrets, vendor/customer storage, and include SHA-256 sidecars.
