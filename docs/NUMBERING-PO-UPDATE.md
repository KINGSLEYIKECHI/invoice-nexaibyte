# Numbering, PO and live totals update — 2026-10-10

This is the current update guide. Packages invoice-numbering-backend-update.zip and invoice-numbering-frontend-update.zip are cumulative, including preceding settings/commercial/admin updates.

## Usage

Business settings includes document numbering. Set invoice and quotation prefix, separator (hyphen, slash or none), and minimum digit width (1–10). For INV/000125 use prefix INV, separator /, width 6 and next counter 125. To continue after 124, enable Set starting counters, choose Last number already used and enter 124; the next counter is 125. Enter numeric counters separately from their prefix. Invoice and quotation sequences remain independent; delivery notes keep DN format.

Existing document numbers never change. Counters cannot move backwards; refresh settings if another teammate created a document while settings was open. Already-existing formatted numbers are skipped under a business lock. Format supports prefix + separator + padded integer, not arbitrary date tokens or embedded suffixes. Default INV-0001/QUO-0001 behavior remains. Counter values must remain within the supported signed integer range.

Invoice and quotation editors have an optional customer PO reference, saved and printed on PDFs. Quotation conversion carries the PO into the draft invoice. This is a reference field, not a purchase-order management module.

Bank account text fields are optional, and entirely empty accounts are omitted from PDFs. Existing per-document-type visibility controls remain. They are business-level current settings, as described in SETTINGS-UPDATE.md.

Line tables show their subtotal below the Amount column; quantity-only delivery notes show combined quantity (useful only if units are comparable). Quotations now show subtotal, discount, tax and final Total below the editor. Invoice and quotation summaries recalculate on input without saving. Tax applies after discount; amounts round to document minor units per line and for tax. Backend recalculation remains authoritative; invalid inputs/discount above subtotal cannot be saved. Currency selection is independent of phone calling codes.

## Converted-invoice payment details follow-up

After quotation conversion, the invoice API returns its business payment configuration and the on-screen invoice displays invoice-enabled bank accounts plus free-text payment instructions. PDFs already include the same invoice-enabled details. Quotation-only accounts stay hidden; enable Show on invoices in business settings for receiving accounts that should appear after conversion. These remain current business settings rather than quotation snapshots. No additional migration is introduced by this follow-up; the cumulative package still requires the migrations above if pending.

Follow-up validation: 19 frontend tests and production build passed; targeted backend settings/conversion regression passed. No hosted deployment or push performed.

## Hostinger installation

For the existing installation, back up the invoice database u491120861_invoice, private backend, frontend, .env and uploaded storage. Preserve APP_KEY. Pause only invoice cron entries and wait for its running worker to finish. Verify this exact private backend and database before proceeding. Do not run these commands against another application's Artisan path.

Enter maintenance mode:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan down --no-ansi
```

Extract invoice-numbering-backend-update.zip into /home/u491120861/invoice-backend, preserving .env, vendor and storage. Apply this app's changes:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan config:clear --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan migrate --force --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan view:clear --no-ansi
```

Extract invoice-numbering-frontend-update.zip into /home/u491120861/domains/nexaibyte.com/public_html/invoice, preserving the backend gateway. No environment/Composer/cron additions or shared PHP service restart is needed. New migration 2026_10_10_000007 adds business prefix/separator/padding fields and nullable po_number to invoices/commercial_documents. Pending earlier migrations also run. Existing invoice amounts and numbers remain unchanged.

After successful installation exit maintenance mode:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan up --no-ansi
```

Check https://invoice.nexaibyte.com/backend/up, then the editors/settings in your browser; resume only paused invoice cron entries. If a step fails, keep maintenance mode enabled while diagnosing or restoring your known-good application. Do not use migrate:fresh or blindly rollback schema: reversal removes saved PO references and format configuration. Database backup is required for a full planned rollback. Managed CI installations must use their release workflow and verified current release paths rather than overwriting a release.

## Acceptance and validation

Use a disposable workspace to try next=125 and last=124, confirm prefixes/padding, create invoice and quotation, attempt moving backwards, and test a second workspace. Enter a PO, save/edit/PDF and convert a quotation. Leave all account fields blank. Change quantity, price, discount and tax and confirm totals immediately. Do not use production invoice counters for trial documents.

61 backend tests / 377 assertions and 18 frontend tests passed; TypeScript and production build passed. Tests cover custom numbering, backward prevention, PO conversion, optional bank details and live quotation adjustments. Hostinger deployment and GitHub CI execution remain unverified for this update.

Reproduce packages after building frontend with VITE_API_URL=/backend/api: python scripts/package-numbering-update.py; python scripts/package-source.py. ZIPs exclude secrets, vendor/customer storage, and include SHA-256 sidecars.
