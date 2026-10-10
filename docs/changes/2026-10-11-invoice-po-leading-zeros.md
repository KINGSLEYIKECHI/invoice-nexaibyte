# Invoice-only PO and leading-zero numbers — 2026-10-11

PO belongs only to invoices, is optional, and is excluded from quotations and conversion copying. Existing quotation PO data is hidden, not deleted. Existing invoices are preserved. Zero-prefixed starting-counter inputs retain their text, infer minimum digit width and preview the next full number. Backend API also normalizes padded counter strings and infers width. No new schema/config/cron dependencies; cumulative packages retain earlier migrations. Guide NUMBER-FORMAT-UPDATE.md includes scoped down/up commands, backups and cron handling.

## Files

- `CHANGELOG.md` — Invoice-only PO, zero-preserving numbering input/normalization, previews or update documentation/packaging.
- `backend/app/Http/Controllers/BusinessSettingsController.php` — Invoice-only PO, zero-preserving numbering input/normalization, previews or update documentation/packaging.
- `backend/app/Http/Controllers/CommercialDocumentController.php` — Invoice-only PO, zero-preserving numbering input/normalization, previews or update documentation/packaging.
- `backend/resources/views/pdf/commercial-document.blade.php` — Invoice-only PO, zero-preserving numbering input/normalization, previews or update documentation/packaging.
- `backend/tests/Feature/BusinessSettingsTest.php` — Regression coverage for zero preservation or invoice-only PO.
- `docs/NUMBER-FORMAT-UPDATE.md` — Invoice-only PO, zero-preserving numbering input/normalization, previews or update documentation/packaging.
- `frontend/src/views/documents/DocumentDetail.vue` — Invoice-only PO, zero-preserving numbering input/normalization, previews or update documentation/packaging.
- `frontend/src/views/documents/DocumentForm.test.ts` — Regression coverage for zero preservation or invoice-only PO.
- `frontend/src/views/documents/DocumentForm.vue` — Invoice-only PO, zero-preserving numbering input/normalization, previews or update documentation/packaging.
- `frontend/src/views/settings/BusinessSettings.test.ts` — Regression coverage for zero preservation or invoice-only PO.
- `frontend/src/views/settings/BusinessSettings.vue` — Invoice-only PO, zero-preserving numbering input/normalization, previews or update documentation/packaging.
- `scripts/package-number-format-update.py` — Invoice-only PO, zero-preserving numbering input/normalization, previews or update documentation/packaging.

Validation: 62 backend tests / 387 assertions passed against isolated invoice_ci MySQL; 20 frontend tests, TypeScript and production build passed. No Hostinger deployment or push. Git whitespace check passed.
- `docs/changes/2026-10-11-invoice-po-leading-zeros.md` — File-level change record and actual verification.
- `docs/DELIVERY-VALIDATION.md` — Recorded regression evidence.
