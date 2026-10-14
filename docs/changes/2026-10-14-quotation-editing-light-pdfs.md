# Editable quotations and light PDFs — 2026-10-14

Issued/accepted quotations retain editing until conversion; status preserved. Converted lock checked under row lock. Delivery-note draft-only behavior remains, including invoice-linked notes. Light Dompdf-safe layout adapts supplied structure without executing attached instructions. No schema/dependency/environment/cron changes. Current cumulative guide contains scoped down/up, backup, worker and failure instructions.

## Files

- `CHANGELOG.md` — Quotation edit rules/UI, shared light PDF rendering, payment layout or deployment documentation/packaging.
- `backend/app/Http/Controllers/CommercialDocumentController.php` — Quotation edit rules/UI, shared light PDF rendering, payment layout or deployment documentation/packaging.
- `backend/resources/views/pdf/bank-accounts.blade.php` — Quotation edit rules/UI, shared light PDF rendering, payment layout or deployment documentation/packaging.
- `backend/resources/views/pdf/commercial-document.blade.php` — Quotation edit rules/UI, shared light PDF rendering, payment layout or deployment documentation/packaging.
- `backend/resources/views/pdf/document-header.blade.php` — Quotation edit rules/UI, shared light PDF rendering, payment layout or deployment documentation/packaging.
- `backend/resources/views/pdf/document-styles.blade.php` — Quotation edit rules/UI, shared light PDF rendering, payment layout or deployment documentation/packaging.
- `backend/resources/views/pdf/invoice.blade.php` — Quotation edit rules/UI, shared light PDF rendering, payment layout or deployment documentation/packaging.
- `backend/tests/Feature/CommercialFeaturesTest.php` — Regression verification and optional synthetic PDF preview exports.
- `backend/tests/Feature/PdfTest.php` — Regression verification and optional synthetic PDF preview exports.
- `docs/QUOTATION-PDF-UPDATE.md` — Quotation edit rules/UI, shared light PDF rendering, payment layout or deployment documentation/packaging.
- `frontend/src/views/documents/DocumentDetail.vue` — Quotation edit rules/UI, shared light PDF rendering, payment layout or deployment documentation/packaging.
- `scripts/package-quotation-pdf-update.py` — Quotation edit rules/UI, shared light PDF rendering, payment layout or deployment documentation/packaging.

## Validation

63 backend tests / 399 assertions passed against isolated invoice_ci MySQL. All 20 frontend tests passed with --maxWorkers=1 after the initial parallel run hit local worker startup timeouts; TypeScript and production Vite build passed. Synthetic invoice/quotation PDFs and a 45-item four-page invoice were rendered using Poppler and every page visually inspected: repeated headings, clean amount alignment, readable light styling, actual page numbers and no overlapping content. PDF generation uses existing embedded fonts, approximately 0.88 MB for these samples; no new external renderer/dependency. SMTP/Hostinger/GitHub execution is not proven by local checks. No remote server deployment performed.

- `docs/DELIVERY-VALIDATION.md` — Updated actual evidence and target-environment limits.
- `docs/changes/2026-10-14-quotation-editing-light-pdfs.md` — This file-level change record.
