# Logo storage and favicon uploads

## What changed

The supplied logo URL returned HTTP 404 on 2026-10-04. Previously logos depended on a storage symlink in the public backend gateway. Business logo URLs now use a Laravel media route when the asset is local; existing records require no filename migration and no public symlink. This fixes inaccessible local logos if the original file is present in storage/app/public.

Business managers upload their own invoice logo in Business settings. Platform administrators upload the product logo and global browser favicon in Platform branding. Registered business owners cannot change the global favicon or platform logo. PNG and JPG are accepted up to 1 MB; images are decoded/re-encoded to PNG and dimensions are bounded. SVG/ICO uploads are not accepted. For favicons, upload a square PNG/JPG; it becomes a 64x64 PNG. Browsers refresh their icon after loading the saved platform settings; the new URL changes on replacement.

## Cloudinary free-plan setup

Cloudinary currently advertises a free API plan with 25 monthly credits and no credit card requirement. Credits cover storage, delivery and transformations; this is not unlimited storage. See https://cloudinary.com/pricing and monitor your account dashboard. No Cloudinary account or credentials have been created/configured by this implementation.

1. Create your Cloudinary account yourself and select the free Image and Video API plan.
2. Copy the cloud name, API key and API secret from the Cloudinary console API Keys page.
3. Set these on the server, never in frontend variables or GitHub source:

```dotenv
MEDIA_DRIVER=cloudinary
CLOUDINARY_CLOUD_NAME=YOUR_CLOUD_NAME
CLOUDINARY_API_KEY=YOUR_API_KEY
CLOUDINARY_API_SECRET="YOUR_API_SECRET"
CLOUDINARY_FOLDER=invoice-saas
```

For the original deployment, edit ~/invoice-backend/.env and run:

```sh
cd ~/invoice-backend
php artisan config:clear
php artisan queue:restart
```

For the managed CI/CD installation, edit ~/invoice-deploy/shared/.env. Then:

```sh
CURRENT=$(cat ~/invoice-deploy/current)
cd "$CURRENT/backend"
VIEW_COMPILED_PATH="$CURRENT/backend/bootstrap/cache/views" php artisan config:cache
php artisan queue:restart
```

Uploads use server-side Basic authentication to Cloudinary's official Upload API. No unsigned upload preset is needed. No API secret is returned by application APIs. A failed upload leaves the current logo intact. Replaced managed assets are deleted on a best-effort basis; if provider deletion fails, a generic warning is logged and the unused image must be removed manually from the provider console.

Only temporary upload/normalization files exist on Hostinger while cloud uploads run. Permanent files are stored on Cloudinary and public URLs are saved in your database. Existing local logos remain readable when switching drivers; re-upload them once after enabling Cloudinary to move them to cloud storage and remove the old local copies. Existing product logos entered previously by URL remain visible; re-upload through the new controls to make them managed assets.

Brand logos/favicon images are public assets. Do not upload private documents or customer financial records through these controls. Invoice PDFs remain private/authenticated or signed; they are not uploaded to Cloudinary. PDF generation downloads only a trusted Cloudinary logo URL from the configured account, enforces timeout/size limits and embeds the image bytes. If the CDN is unavailable, the invoice can render without its logo. No permanent local PDF logo cache is created.

## Install the update on the existing Hostinger layout

1. Back up your database and backend files.
2. Extract deployment/invoice-media-backend-update.zip into /home/u491120861/invoice-backend, replacing included files. It includes no .env/vendor/customer storage.
3. Run:

```sh
cd ~/invoice-backend
php artisan migrate --force --no-ansi
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan queue:restart
```

4. Extract deployment/invoice-media-frontend-update.zip into /home/u491120861/domains/nexaibyte.com/public_html/invoice. Keep the backend directory.
5. If you have not enabled your platform administrator account yet, run `php artisan platform:admin YOUR_REGISTERED_EMAIL` over SSH from the backend directory, using your own registered email. Sign out and sign in again.
6. Refresh/sign in again. Open Business settings and verify the current logo. Open Platform branding as the platform administrator to upload logo/favicon.
7. Configure Cloudinary credentials if you want cloud uploads. Without configuration, MEDIA_DRIVER=local works via the new app media URLs.

If using CI/CD, commit/push the source changes and deploy via the pipeline instead of manually replacing a managed release. The same migration/configuration steps are included in release deployment; add Cloudinary credentials to the shared server environment.

## Immediate repair before installing this update

For the original layout, first check the file and public link over SSH:

```sh
ls -l ~/invoice-backend/storage/app/public/logos/CRt4aEgMpzKD6YnTcZFRIihs4NNs4HRAGNcGRXuD.jpg
ls -ld ~/domains/nexaibyte.com/public_html/invoice/backend/storage
```

If the file exists and the second command says the public storage path does not exist, create the link:

```sh
ln -s /home/u491120861/invoice-backend/storage/app/public /home/u491120861/domains/nexaibyte.com/public_html/invoice/backend/storage
```

Do not overwrite or delete an existing storage directory. If the image file is missing, re-upload the logo. The new app media route avoids this link dependency entirely.

## Verification

Backend tests cover public local delivery, replacement cleanup, platform authorization, square favicon validation, malicious SVG rejection, image metadata survival during text edits, private metadata exclusion, authenticated cloud requests, no permanent local cloud files, PDF logo embedding, provider failure preservation and unsafe-URL refusal. Provider calls use HTTP fakes; actual Cloudinary upload/delete needs one hosted test with your private credentials. Frontend tests cover favicon URL updates and duplicate-link prevention. The source and CI delivery packages include these changes.

Reference: https://cloudinary.com/documentation/image_upload_api_reference
