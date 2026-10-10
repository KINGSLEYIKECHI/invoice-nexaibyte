# 2026-10-10 — Numbering, PO and totals

See ../NUMBERING-PO-UPDATE.md for behavior, format limits, scope, schema, backups, maintenance entry/exit and failure recovery. No new credentials/cron/dependencies. 61 MySQL backend tests/377 assertions, 18 frontend tests, TypeScript and production build passed. No Hostinger deployment or push performed. Backend still recalculates totals; frontend preview is for immediate feedback.

## Files

- `CHANGELOG.md` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `backend/app/Http/Controllers/BusinessSettingsController.php` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `backend/app/Http/Controllers/CommercialDocumentController.php` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `backend/app/Http/Requests/StoreInvoiceRequest.php` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `backend/app/Services/InvoiceNumberService.php` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `backend/database/migrations/2026_10_10_000007_add_number_formats_and_po.php` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `backend/resources/views/pdf/bank-accounts.blade.php` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `backend/resources/views/pdf/commercial-document.blade.php` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `backend/resources/views/pdf/invoice.blade.php` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `backend/tests/Feature/BusinessSettingsTest.php` — Regression coverage for numbering/PO/optional accounts or live calculations.
- `docs/NUMBERING-PO-UPDATE.md` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `frontend/src/components/LineItemsEditor.vue` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `frontend/src/types/index.ts` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `frontend/src/views/documents/DocumentDetail.vue` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `frontend/src/views/documents/DocumentForm.test.ts` — Regression coverage for numbering/PO/optional accounts or live calculations.
- `frontend/src/views/documents/DocumentForm.vue` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `frontend/src/views/invoices/InvoiceForm.vue` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `frontend/src/views/settings/BusinessSettings.vue` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `scripts/package-numbering-update.py` — Number formatting, PO persistence/rendering, optional account validation, live totals or release documentation/packaging.
- `frontend/src/views/invoices/InvoiceDetail.vue` — Show the saved PO reference on the invoice detail sheet.
- `docs/DELIVERY-VALIDATION.md` — Record actual verification and production limits.
- `docs/changes/2026-10-10-numbering-po-totals.md` — File-level release record.
