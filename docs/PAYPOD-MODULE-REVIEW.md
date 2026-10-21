# Paypod feature review and modular scope — 2026-10-21

Reviewed the owner-authorized account's dashboard, Sales, Purchases, Accounting and Reports navigation and the empty purchase-order form through the browser. Read-only: no document, company preference, payment, subscription, account privilege or credential was changed. Login initially failed; the owner then signed in directly. No credentials, session tokens, account contact data or company identifier are stored in source or packages.

Public reference: [Paypod features](https://paypod.co.za/). This is workflow research for our own implementation; no integration, affiliation, data migration or source-code copy is implied.

## Current working module catalogue

| Module | Availability in this project |
| --- | --- |
| Invoices, clients, overview | Core: always available, existing behavior retained |
| Quotations | Selectable; editable issued/accepted quotes, conversion lock, PDF |
| Delivery notes | Selectable; quantity-only PDF and receipt tracking |
| Products & services | Selectable; catalogue, CSV/XLSX templates/import |
| Team | Selectable for owners/admins; original role permissions still apply |
| Client statements | New selectable module; dated opening/activity/closing per currency and CSV |
| Due & overdue | New selectable module; as-of outstanding invoice ageing and CSV |
| Settings / platform administration | Original role-restricted access, independent of layout selection |

The owner clarified that each user should choose modules. Simple and Advanced are presets; individual checkboxes produce Custom mode. Preferences are per user, not a global switch that removes tools from teammates. No subscription or paid entitlement is introduced.

## Reviewed features not implemented in this update

Paypod also exposes proforma invoices, recurring invoices/statements, credit notes, supplier purchase orders, bills/supplier credit notes, suppliers, accounting transactions/chart of accounts/year-end, income statement, balance sheet, cash-flow statement, trial balance/general ledger and VAT reports. Its public feature list also mentions bank import/reconciliation and online signatures. These are NOT working modules in this release and are not offered as enabled switches or placeholder screens. This update is not complete Paypod feature parity.

## Extension design and prerequisites

1. Purchasing: tenant-owned supplier records, supplier-specific addresses/terms, independently numbered purchase orders, bills and supplier credits. Keep supplier documents separate from sales quotation conversion and from optional invoice PO references.
2. Credit notes: introduce a bounded, auditable credit allocation against an invoice; update invoice balances/payment validation, statement events, tax effects and report definitions together. A label-only negative document is insufficient.
3. Recurrence: bounded database schedule records, per-occurrence idempotency and time-zone/due-date policy; cron generation separate from explicit email dispatch. Hiding the module must not cancel schedules silently.
4. Accounting: implement a balanced, immutable journal and posting adapters with per-currency accounts, reversals, opening balances and closed-period policy before claiming balance-sheet/VAT/general-ledger reports. Existing operational reports are not a substitute.
5. Bank imports: supported bank file templates, preview/atomic confirmation, duplicate detection, bank-account mapping and explicit match approval. No bank credentials should be collected.
6. Signatures: public document-specific access, explicit acceptance text, version-bound consent and audit evidence. Team-entered recipient names are not customer signatures.
7. Document library/design options can reuse the existing validated image/PDF service without importing Paypod assets.

For each actual module: add it to WorkspaceModules.php and the frontend workspace helper/route metadata; implement tenant-scoped APIs and role checks; register its navigation and tests; only then expose its catalogue checkbox. Core authentication, authorization, maintenance and platform management never depend on a user preference. Future availability/entitlement flags must be a separate server-side policy from presentation mode.

Financial/reporting semantics, recurrence delivery behavior and signatures require their own implementation and verification. No claim is made that these future workflows have been built, tested or deployed.
