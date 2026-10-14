# Editable quotations and light PDF layout — 2026-10-14

This is the current installation guide. Use invoice-quotation-pdf-backend-update.zip and invoice-quotation-pdf-frontend-update.zip. Packages are cumulative and contain the earlier numbering, optional invoice PO, bank details, currencies and administration updates.

## Behavior

Draft, issued and accepted quotations can be edited by the same workspace members who could edit drafts previously. Changes recalculate totals and replace line items under a database lock. Their current status is retained, including accepted: editing does not automatically obtain fresh customer acceptance. Communicate revised terms to your customer before conversion. Declined quotations remain locked under the existing workflow. After conversion, editing is rejected by the server and the Edit button disappears; the linked invoice remains independent. A quotation with an invoice link is locked even if its status is unexpectedly changed. Delivery notes still permit draft edits only, including draft notes linked to an invoice. PO stays optional on invoices and absent from quotations.

Invoice, quotation and delivery-note PDFs use the supplied reference's structure: business/document header, dates, from/to details, item table, totals, payment details, notes and page footer. The result uses white paper, pale grey bands and thin brand-colour rules for light printing. No external fonts, browser engine, JS, new Composer packages or remote stylesheets. DejaVu Sans remains embedded for readable international text. Tables are compatible with Dompdf; item headings repeat on page breaks and the footer uses actual page numbers. No fixed Page 1 of 1 claim. Layout retains currency precision, PO on invoices, bank visibility flags, partial-payment balances, and quantity-only delivery notes. It does not invent tax IDs or project fields absent from your data.

The reference HTML/CSS/PDF is design input only. Production templates display your own tenant/client values, escaped by Blade. Remote image loading remains disabled; existing validated logo embedding is reused. Rich markup in notes is not executed.

## Hostinger update

Verify `/home/u491120861/invoice-backend/artisan` and dedicated invoice database `u491120861_invoice`. Back up that database, backend, frontend, private .env and uploads; preserve APP_KEY. Pause only invoice cron entries and allow its worker to finish. Enter maintenance mode:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan down --no-ansi
```

Extract the backend ZIP into `/home/u491120861/invoice-backend`, preserving vendor, .env and storage. This update adds no new migration or dependency; pending migrations from the cumulative earlier releases still require application:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan migrate --force --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan view:clear --no-ansi
```

Extract the frontend ZIP into `/home/u491120861/domains/nexaibyte.com/public_html/invoice`, preserving its backend gateway. After success exit maintenance mode:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan up --no-ansi
```

Check https://invoice.nexaibyte.com/backend/up, download invoice/quotation PDFs and resume only this app's paused cron entries. If installation fails, keep maintenance enabled while diagnosing/restoring known-good files. Do not blindly reverse schema or run migrate:fresh. No shared PHP service restart, environment change or new cron schedule. Managed CI release installations must use their release mechanism and verified current Artisan path rather than overwriting a managed release.

## Acceptance and packaging

In a test workspace create and issue a quotation, edit price/notes, accept it and edit again, then convert and confirm subsequent editing fails. Confirm converted invoice amounts reflect the latest quotation. Check draft delivery notes linked to invoices remain editable. Review a short PDF and a long item list, account visibility, optional invoice PO, separate totals/balance due and currencies.

Build frontend with VITE_API_URL=/backend/api, then run python scripts/package-quotation-pdf-update.py and python scripts/package-source.py. ZIPs exclude .env, dependencies and customer storage and provide SHA-256 sidecars. Source ZIP is ready for GitHub. See docs/changes/2026-10-14-quotation-editing-light-pdfs.md for verification results and limitations. Real Hostinger deployment is not performed by this change.
