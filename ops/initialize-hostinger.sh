#!/usr/bin/env bash
# One-time adoption of the existing installation. Keeps the original folders.
set -Eeuo pipefail
umask 027
ROOT="$HOME/invoice-deploy"
LEGACY_BACKEND="$HOME/invoice-backend"
PUBLIC_PATH="$HOME/domains/nexaibyte.com/public_html/invoice"
HERE=$(cd "$(dirname "$0")" && pwd)
[[ ! -e "$ROOT/current" && -d "$LEGACY_BACKEND/vendor" && -f "$LEGACY_BACKEND/.env" && -d "$PUBLIC_PATH" && ! -L "$PUBLIC_PATH" ]] || { echo 'Unexpected server layout or already initialized';exit 1; }
command -v flock >/dev/null;command -v rsync >/dev/null;command -v curl >/dev/null
mkdir -p "$ROOT"/{releases,shared,backups,incoming}
[[ ! -e "$ROOT/shared/.env" ]] || { echo 'Shared environment already exists; inspect before retrying';exit 1; }
cp "$LEGACY_BACKEND/.env" "$ROOT/shared/.env";chmod 600 "$ROOT/shared/.env"
printf '\nDB_QUEUE_RETRY_AFTER=180\n' >> "$ROOT/shared/.env"
cp -a "$LEGACY_BACKEND/storage" "$ROOT/shared/storage"
mkdir -p "$ROOT/shared/storage"/{app/public,framework/cache/data,framework/sessions,framework/views,logs}
cp "$HERE/server-config.example.sh" "$ROOT/config.sh"
cp "$HERE/cron.sh" "$ROOT/cron.sh";cp "$HERE/rollback.sh" "$ROOT/rollback.sh"
ID="legacy-$(date -u +%Y%m%d%H%M%S)";LEGACY="$ROOT/releases/$ID"
mkdir -p "$LEGACY/backend" "$LEGACY/public"
rsync -a --exclude=.env --exclude=storage --exclude=bootstrap/cache "$LEGACY_BACKEND/" "$LEGACY/backend/"
cp -a "$PUBLIC_PATH/." "$LEGACY/public/"
# Replace only the copied public gateway, keeping the original installation intact.
cp "$HERE/public-index.php" "$LEGACY/public/backend/index.php"
if [[ -L "$LEGACY/public/backend/storage" ]];then unlink "$LEGACY/public/backend/storage";fi
[[ ! -e "$LEGACY/public/backend/storage" ]] || { echo 'Copied public storage is a real directory; inspect it before adoption';exit 1; }
ln -s "$ROOT/shared/storage/app/public" "$LEGACY/public/backend/storage"
ln -s "$ROOT/shared/.env" "$LEGACY/backend/.env";ln -s "$ROOT/shared/storage" "$LEGACY/backend/storage"
mkdir -p "$LEGACY/backend/bootstrap/cache"
printf '{"version":"%s"}\n' "$ID" > "$LEGACY/public/release.json"
ORIGINAL="$PUBLIC_PATH.before-ci-$ID"
mv "$PUBLIC_PATH" "$ORIGINAL"
ln -s "$LEGACY/public" "$PUBLIC_PATH"
source "$ROOT/config.sh"
if ! curl --fail --silent --max-time 30 "$BASE_URL/backend/up" >/dev/null;then unlink "$PUBLIC_PATH";mv "$ORIGINAL" "$PUBLIC_PATH";echo 'Adoption health check failed; original public folder restored';exit 1;fi
printf '%s\n' "$LEGACY" > "$ROOT/current"
echo 'Existing installation adopted. Original backend and public folder retained. Update cron jobs before enabling automatic deployments.'
