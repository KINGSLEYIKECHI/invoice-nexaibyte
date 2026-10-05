# 2026-10-05 — Commercial documents, catalogue and currencies

## Behavior and deployment

See [COMMERCIAL-DOCUMENTS.md](../COMMERCIAL-DOCUMENTS.md) for workflows, permissions, currency API compatibility, file limits, exact Hostinger paths and rollback precautions. New schema is additive; old amounts remain NGN. No new environment secrets, Composer dependencies or cron entries. XLSX needs optional PHP zip; CI mirrors it. No production deployment or push was performed.

## Validation

59 backend tests / 356 assertions passed against isolated MySQL. 16 frontend tests passed; TypeScript and production Vite build passed with /backend/api. Backend tests cover lifecycle/PDF, tenant authorization, repeat conversion, separate currency aggregation, import previews/atomicity/SKU updates and XLSX formula rejection. Real Hostinger and GitHub Linux container execution remain unverified.

## File-level changes

- `.github/workflows/pipeline.yml` — Enable zip for XLSX in CI PHP runtime.
- `CHANGELOG.md` — Usage, deployment, validation and traceable change documentation.
- `README.md` — Usage, deployment, validation and traceable change documentation.
- `backend/app/Http/Controllers/BusinessSettingsController.php` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `backend/app/Http/Controllers/CommercialDocumentController.php` — Quotation and delivery-note lifecycle, interface, PDF and tenant-scoped persistence.
- `backend/app/Http/Controllers/DashboardController.php` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `backend/app/Http/Controllers/InvoiceController.php` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `backend/app/Http/Controllers/ProductController.php` — Catalogue, templates and validated preview/atomic import workflow.
- `backend/app/Http/Requests/StoreInvoiceRequest.php` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `backend/app/Jobs/SendPaymentEmail.php` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `backend/app/Models/Business.php` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `backend/app/Models/CommercialDocument.php` — Quotation and delivery-note lifecycle, interface, PDF and tenant-scoped persistence.
- `backend/app/Models/CommercialDocumentItem.php` — Quotation and delivery-note lifecycle, interface, PDF and tenant-scoped persistence.
- `backend/app/Models/Product.php` — Catalogue, templates and validated preview/atomic import workflow.
- `backend/app/Services/CurrencyService.php` — Supported currencies, exact minor-unit conversion and display precision.
- `backend/app/Services/InvoiceTotalsService.php` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `backend/app/Services/PaymentEmails.php` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `backend/app/Services/ProductImportService.php` — Catalogue, templates and validated preview/atomic import workflow.
- `backend/database/migrations/2026_10_05_000005_create_commercial_documents_and_products.php` — Add catalogue/document tables, currency snapshots, line units and numbering counters.
- `backend/resources/data/currencies.json` — ISO currency snapshot with countries and minor-unit precision.
- `backend/resources/views/emails/invoice.blade.php` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `backend/resources/views/pdf/commercial-document.blade.php` — Quotation and delivery-note lifecycle, interface, PDF and tenant-scoped persistence.
- `backend/resources/views/pdf/invoice.blade.php` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `backend/routes/api.php` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `backend/tests/Feature/CommercialFeaturesTest.php` — Quotation and delivery-note lifecycle, interface, PDF and tenant-scoped persistence.
- `docs/COMMERCIAL-DOCUMENTS.md` — Usage, deployment, validation and traceable change documentation.
- `docs/DELIVERY-VALIDATION.md` — Usage, deployment, validation and traceable change documentation.
- `frontend/src/__tests__/LineItemsEditor.test.ts` — Regression coverage and production PHP zip support.
- `frontend/src/api/client.ts` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `frontend/src/components/CurrencyPicker.vue` — Supported currencies, exact minor-unit conversion and display precision.
- `frontend/src/components/LineItemsEditor.vue` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `frontend/src/components/Navbar.vue` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `frontend/src/components/PaymentModal.vue` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `frontend/src/components/StatCard.vue` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `frontend/src/data/currencies.json` — ISO currency snapshot with countries and minor-unit precision.
- `frontend/src/router/index.ts` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `frontend/src/types/index.ts` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `frontend/src/utils/money.test.ts` — Supported currencies, exact minor-unit conversion and display precision.
- `frontend/src/utils/money.ts` — Supported currencies, exact minor-unit conversion and display precision.
- `frontend/src/views/Dashboard.vue` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `frontend/src/views/documents/DocumentDetail.vue` — Quotation and delivery-note lifecycle, interface, PDF and tenant-scoped persistence.
- `frontend/src/views/documents/DocumentForm.vue` — Quotation and delivery-note lifecycle, interface, PDF and tenant-scoped persistence.
- `frontend/src/views/documents/DocumentList.vue` — Quotation and delivery-note lifecycle, interface, PDF and tenant-scoped persistence.
- `frontend/src/views/invoices/InvoiceDetail.vue` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `frontend/src/views/invoices/InvoiceForm.vue` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `frontend/src/views/invoices/InvoiceList.vue` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `frontend/src/views/products/ProductList.vue` — Catalogue, templates and validated preview/atomic import workflow.
- `frontend/src/views/settings/BusinessSettings.vue` — Integrate document currencies, units and catalogue workflows into existing invoicing/navigation/API behavior.
- `ops/production-test.Dockerfile` — Regression coverage and production PHP zip support.
- `scripts/package-commercial-update.py` — Reproducible cumulative update ZIPs excluding secrets and runtime data.
- `docs/changes/2026-10-05-commercial-documents.md` — This file-level release record.
- `frontend/e2e/production.spec.ts` — Extend the compiled production-mirror smoke scenario with templates, import preview, quotation conversion, PDF, delivery notes and navigation; prepared for CI, not executed locally this update.
