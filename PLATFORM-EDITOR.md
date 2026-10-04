# Platform branding editor

Manage branding at https://invoice.nexaibyte.com/platform after enabling your account.
Controls: name, public HTTPS logo URL, company attribution/link, support email, colours, login/registration/welcome text, announcement. Text is escaped; arbitrary HTML/scripts are not supported. Logos use an image URL rather than a file upload. Business invoice branding is independent. Existing visitors see saved changes after refresh. This is a branding/content panel, not a page layout builder or account moderation system.

## Existing Hostinger installation update
1. Back up your backend files and database through Hostinger.
2. Extract deployment/invoice-platform-backend-update.zip into /home/u491120861/invoice-backend, replacing the included files. It contains no .env, vendor, storage or customer data.
3. Run over SSH:

```sh
cd ~/invoice-backend
php artisan migrate --force --no-ansi
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan platform:admin YOUR_REGISTERED_EMAIL
```

Replace YOUR_REGISTERED_EMAIL with the email of YOUR existing registered account. Registration never grants platform access. To revoke it: php artisan platform:admin YOUR_REGISTERED_EMAIL --revoke

4. Extract deployment/invoice-platform-frontend-update.zip directly into /home/u491120861/domains/nexaibyte.com/public_html/invoice, replacing index.html, .htaccess and matching assets. Keep the backend directory. The archive uses https://invoice.nexaibyte.com/backend/api.
5. Sign out and sign in again, then open /platform. Choose a name and colours, preview and save. Keep the page open if a save error occurs; it preserves your changes.

No Composer install or frontend build is required for these two updates. No APP_KEY regeneration, database reset or seeding is needed.

## Validation
37 backend tests, 178 assertions; 6 frontend tests; TypeScript and production Vite build pass. Access tests cover guest/owner denial, explicit grant/revoke, unsafe URLs/colour input rejection and registration/team privilege injection.
