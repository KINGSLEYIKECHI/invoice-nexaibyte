# Settings and sign-out update — 2026-10-10

## Changes

Business settings now uses the existing searchable global currency picker. Currency is independent of phone country codes: enter international numbers with + and the calling code. Existing Nigerian local-number normalization remains for compatibility; selecting USD/JPY does not infer a country or rewrite the phone.

Owners and business admins can enter up to ten receiving bank accounts manually: bank name, holder, account number/IBAN, optional routing/SWIFT and additional instructions. Separate checkboxes control display on invoice and quotation PDFs. Delivery notes never display them. Numbers are text so leading zeros survive. Existing free-text payment instructions remain. These are payment instructions, not bank verification or an automatic payment integration. Do not enter passwords/PINs. Accounts shown on invoices are also visible to recipients of their public invoice link/PDF.

Account details are business-level current settings: updating them changes subsequently rendered PDFs, including existing documents. Historical account snapshots and per-document account selection are not included.

Desktop navigation scrolls independently; profile and a labelled Sign out button stay at the bottom. The promotional tip is hidden on short screens. Mobile uses its existing flowing layout. Logout clears local authentication and redirects even when the server is unavailable; remote token revocation then cannot be confirmed.

## Hostinger upgrade

Use the existing private backend `/home/u491120861/invoice-backend` and public frontend `/home/u491120861/domains/nexaibyte.com/public_html/invoice`. Follow COMMERCIAL-DOCUMENTS.md for backups, maintenance mode and scoped cache clearing. Preserve .env, APP_KEY, storage and the public backend gateway. The settings ZIPs are cumulative and include earlier commercial/admin migrations if pending.

Extract invoice-settings-backend-update.zip into the private backend, then run:

```sh
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan migrate --force --no-ansi
/opt/alt/php83/usr/bin/php /home/u491120861/invoice-backend/artisan view:clear --no-ansi
```

Extract invoice-settings-frontend-update.zip into the invoice subdomain directory, preserving backend. Existing cron schedules and environment variables need no changes. The new migration adds nullable businesses.bank_accounts JSON only. It does not modify existing currency/phone values. Rolling this migration back deletes saved bank accounts; use a backup for a planned rollback.

If using managed CI releases, deploy through that mechanism instead of overwriting releases. Exact shared-host isolation checks and first production CI run are still pending; automatic deployment has not been enabled.

## Acceptance

Save USD or JPY, refresh and confirm it remains selected. Save a phone with + and a country calling code. Add an account beginning with 0, select invoice only, save, download invoice and quotation PDFs and check visibility. Check a delivery note contains no accounts. Try sign-out at 1366x600 without zooming. Verify staff cannot edit business settings and another business cannot see your accounts.

## Validation

See docs/changes/2026-10-10-settings-and-sign-out.md for recorded checks. No Hostinger deployment or real bank integration was performed.
