# Platform branding editor

The platform operator can manage shared branding at `/platform`, reached through `/admin` → **Branding & favicon upload**. Controls include product/company name, support link, colours, welcome/login/registration text, announcement, product logo upload and browser favicon upload. Text is escaped and arbitrary scripts/HTML are unsupported. Business invoice branding remains separate.

The favicon accepts square PNG/JPG up to 1 MB and produces a 64 × 64 PNG. Storage can be local or Cloudinary; see [media storage](docs/MEDIA-STORAGE.md).

Grant access only to your own existing account with `php artisan platform:admin YOUR_REGISTERED_EMAIL --no-ansi`, then sign out and in. Registration and business-owner roles never grant platform access.

Use the latest [cumulative admin update](docs/ADMIN-UPDATE.md) and invoice-admin update archives for Hostinger installation. It includes the earlier branding/media changes. Do not install older invoice-platform archives over this update.
