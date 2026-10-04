#!/usr/bin/env bash
# Linux-only fixture tests for activation, rollback and migration failure.
set -Eeuo pipefail
HERE=$(cd "$(dirname "$0")" && pwd)
ROOT="$HOME/invoice-deploy-ci-$$"
PUBLIC_PARENT="$HOME/domains/invoice-ci-$$.invalid/public_html"
PUBLIC_PATH="$PUBLIC_PARENT/invoice"
[[ ! -e "$ROOT" && ! -e "$PUBLIC_PARENT" ]] || exit 1
mkdir -p "$ROOT"/{incoming,releases/old/backend,releases/old/public/assets,shared/storage,backups,bin} "$PUBLIC_PARENT"
cleanup(){
 [[ "$ROOT" == "$HOME"/invoice-deploy-ci-* && "$PUBLIC_PARENT" == "$HOME"/domains/invoice-ci-*.invalid/public_html ]] || exit 1
 rm -rf -- "$ROOT" "$PUBLIC_PARENT"
}
trap cleanup EXIT
printf 'fixture' > "$ROOT/shared/.env"
printf '<?php' > "$ROOT/releases/old/public/backend-index.php"
mkdir -p "$ROOT/releases/old/public/backend"
printf '<?php' > "$ROOT/releases/old/public/backend/index.php"
ln -s "$ROOT/releases/old/public" "$PUBLIC_PATH"
printf '%s\n' "$ROOT/releases/old" > "$ROOT/current"
cat > "$ROOT/bin/php-fixture" <<'PHP'
#!/usr/bin/env bash
if [[ "${1:-}" == '-r' ]];then cat >/dev/null;[[ "${HEALTH_FAIL:-0}" != 1 ]];exit;fi
if [[ "${1:-}" == scripts/backup-db.php ]];then printf 'snapshot' > "$2";fi
if [[ "${2:-}" == migrate && "${MIGRATION_FAIL:-0}" == 1 ]];then exit 1;fi
exit 0
PHP
cat > "$ROOT/bin/curl" <<'CURL'
#!/usr/bin/env bash
if [[ "${HEALTH_FAIL:-0}" == 1 ]];then exit 22;fi
printf '{"product_name":"Fixture","version":"fixture"}'
CURL
cat > "$ROOT/bin/sleep" <<'SLEEP'
#!/usr/bin/env bash
exit 0
SLEEP
chmod +x "$ROOT/bin/"*
export PATH="$ROOT/bin:$PATH"
printf 'PHP_BIN="%s"\nPUBLIC_PATH="%s"\nBASE_URL=https://ci.invalid\n' "$ROOT/bin/php-fixture" "$PUBLIC_PATH" > "$ROOT/config.sh"
make_package(){
 local id=$1
 mkdir -p "$ROOT/fixture/backend/bootstrap" "$ROOT/fixture/public/assets" "$ROOT/fixture/public/backend"
 printf 'fixture' > "$ROOT/fixture/backend/artisan"
 printf '<?php' > "$ROOT/fixture/public/backend/index.php"
 tar -czf "$ROOT/incoming/$id.tar.gz" -C "$ROOT/fixture" .
 (cd "$ROOT/incoming";sha256sum "$id.tar.gz" > "$id.tar.gz.sha256")
}
make_package fixture-good
bash "$HERE/deploy.sh" "$ROOT" fixture-good
[[ $(readlink "$PUBLIC_PATH") == "$ROOT/releases/fixture-good/public" ]]
bash "$HERE/rollback.sh" "$ROOT"
[[ $(readlink "$PUBLIC_PATH") == "$ROOT/releases/old/public" ]]
make_package fixture-bad
if HEALTH_FAIL=1 bash "$HERE/deploy.sh" "$ROOT" fixture-bad;then echo 'Expected health failure';exit 1;fi
[[ $(readlink "$PUBLIC_PATH") == "$ROOT/releases/old/public" ]]
make_package fixture-migration
if MIGRATION_FAIL=1 bash "$HERE/deploy.sh" "$ROOT" fixture-migration;then echo 'Expected migration failure';exit 1;fi
[[ $(readlink "$PUBLIC_PATH") == "$ROOT/releases/old/public" ]]
[[ -f "$ROOT/shared/.env" && -d "$ROOT/shared/storage" ]]
echo 'Deployment fixture checks passed: activation, rollback, health failure, migration failure and shared data preservation.'
