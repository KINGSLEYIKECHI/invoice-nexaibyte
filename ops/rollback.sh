#!/usr/bin/env bash
set -Eeuo pipefail
ROOT=$(realpath "${1:?Deployment root required}")
[[ "$ROOT" == "$HOME"/invoice-deploy* ]] || exit 1
source "$ROOT/config.sh"
exec 9>"$ROOT/deploy.lock";flock -w 120 9
TARGET=$(cat "$ROOT/previous")
[[ "$TARGET" == "$ROOT"/releases/*/public && -f "$TARGET/backend/index.php" && -L "$PUBLIC_PATH" ]] || { echo 'No valid previous release';exit 1; }
OLD=$(readlink "$PUBLIC_PATH")
cp -an "$OLD/assets/." "$TARGET/assets/"
ln -s "$TARGET" "$PUBLIC_PATH.rollback";mv -Tf "$PUBLIC_PATH.rollback" "$PUBLIC_PATH"
if ! curl --fail --silent --max-time 20 "$BASE_URL/backend/up" >/dev/null; then ln -s "$OLD" "$PUBLIC_PATH.next";mv -Tf "$PUBLIC_PATH.next" "$PUBLIC_PATH";exit 1;fi
printf '%s\n' "$OLD" > "$ROOT/previous"
printf '%s\n' "${TARGET%/public}" > "$ROOT/current"
echo 'Previous code release activated. Database was not modified.'
