# Converted-invoice payment details — 2026-10-10

Conversion now returns business payment configuration immediately. Invoice detail displays invoice-enabled nonempty bank accounts and free-text instructions. Quotation-only account flags are respected. PDFs already render these business-level current settings; no snapshots or schema introduced.

Files: CommercialDocumentController.php (conversion eager-load business), InvoicePaymentDetails.vue and its test (display/filter), InvoiceDetail.vue (integration), BusinessSettingsTest.php (conversion account/instructions assertions), CHANGELOG.md, NUMBERING-PO-UPDATE.md and this record (documentation). Settings/numbering packages and source ZIP regenerated using package-numbering-update.py and package-source.py. Existing guide directly includes maintenance down/up, backup/cron and failure recovery.

Validation: 19 frontend tests and production TypeScript/Vite build passed; targeted backend BusinessSettingsTest passed. Earlier complete suite had 61 tests/377 assertions; no full suite repeated for this follow-up. Not pushed or deployed. No environment/dependency/cron changes.
