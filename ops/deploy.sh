#!/usr/bin/env bash
set -Eeuo pipefail
umask 027
ROOT=$(realpath "${1:?Deployment root required}")
VERSION=${2:?Release identifier required}
[[ "$ROOT" == "$HOME"/invoice-deploy* && "$VERSION" =~ ^[a-zA-Z0-9][a-zA-Z0-9._-]{0,99}$ ]] || { echo 'Invalid deployment path or version'; exit 1; }
source "$ROOT/config.sh"
[[ "$PUBLIC_PATH" == "$HOME"/domains/*/public_html/* && "$BASE_URL" == https://* ]] || exit 1
[[ -f "$ROOT/shared/.env" && -d "$ROOT/shared/storage" ]] || { echo 'Complete the initial server setup first'; exit 1; }
exec 9>"$ROOT/deploy.lock"
flock -w 120 9 || { echo 'Another deployment or cron worker is active'; exit 1; }
OLD=$(readlink "$PUBLIC_PATH" || true)
[[ -n "$OLD" && "$OLD" == "$ROOT"/releases/*/public ]] || { echo 'Public path must point to a managed release. See first-deployment instructions.'; exit 1; }
RELEASE="$ROOT/releases/$VERSION"
[[ ! -e "$RELEASE" ]] || { echo 'Release already exists; use a new workflow run'; exit 1; }
cd "$ROOT/incoming"
sha256sum -c "$VERSION.tar.gz.sha256"
mkdir -p "$RELEASE"
tar -xzf "$VERSION.tar.gz" -C "$RELEASE"
ln -s "$ROOT/shared/.env" "$RELEASE/backend/.env"
ln -s "$ROOT/shared/storage" "$RELEASE/backend/storage"
mkdir -p "$RELEASE/backend/bootstrap/cache/views"
export VIEW_COMPILED_PATH="$RELEASE/backend/bootstrap/cache/views"
ln -s "$ROOT/shared/storage/app/public" "$RELEASE/public/backend/storage"
cd "$RELEASE/backend"
"$PHP_BIN" artisan package:discover --no-ansi
"$PHP_BIN" scripts/validate-env.php
"$PHP_BIN" scripts/backup-db.php "$ROOT/backups/$VERSION.sql"
"$PHP_BIN" artisan migrate --force --no-ansi
"$PHP_BIN" artisan config:cache --no-ansi
"$PHP_BIN" artisan route:cache --no-ansi
# Compile views into a release-specific path so old requests keep their own templates.
"$PHP_BIN" artisan view:cache --no-ansi
"$PHP_BIN" artisan queue:restart --no-ansi
if [[ -d "$OLD/assets" ]]; then cp -an "$OLD/assets/." "$RELEASE/public/assets/";fi
ln -s "$RELEASE/public" "$PUBLIC_PATH.next"
mv -Tf "$PUBLIC_PATH.next" "$PUBLIC_PATH"
rollback_on_error(){ cp -an "$RELEASE/public/assets/." "$OLD/assets/"; ln -s "$OLD" "$PUBLIC_PATH.rollback"; mv -Tf "$PUBLIC_PATH.rollback" "$PUBLIC_PATH"; echo 'Health check failed. Previous application restored; database migrations were NOT reversed.' >&2; }
healthy=false
for attempt in 1 2 3 4 5; do
 if curl --fail --silent --max-time 15 "$BASE_URL/backend/up" >/dev/null && curl --fail --silent --max-time 15 "$BASE_URL/release.json" | "$PHP_BIN" -r '$j=json_decode(stream_get_contents(STDIN),true);exit(($j["version"]??null)===$argv[1]?0:1);' "$VERSION" && curl --fail --silent --max-time 15 "$BASE_URL/backend/api/platform/settings" | "$PHP_BIN" -r '$j=json_decode(stream_get_contents(STDIN),true);exit(isset($j["product_name"])?0:1);'; then healthy=true;break;fi
 sleep 3
done
if [[ "$healthy" != true ]]; then rollback_on_error;exit 1;fi
printf '%s\n' "$OLD" > "$ROOT/previous"
printf '%s\n' "$RELEASE" > "$ROOT/current"
echo "Activated $VERSION"
