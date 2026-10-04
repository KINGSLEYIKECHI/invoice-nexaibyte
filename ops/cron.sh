#!/usr/bin/env bash
set -Eeuo pipefail
ROOT=$(realpath "${1:?Deployment root required}")
TASK=${2:?queue or reminders required}
[[ "$ROOT" == "$HOME"/invoice-deploy* ]] || exit 1
source "$ROOT/config.sh"
# Shared deploy lock permits normal jobs but blocks migration/activation races.
exec 9>"$ROOT/deploy.lock";flock -s -n 9 || exit 0
CURRENT=$(cat "$ROOT/current")
[[ "$CURRENT" == "$ROOT"/releases/* && -f "$CURRENT/backend/artisan" ]] || exit 1
cd "$CURRENT/backend"
case "$TASK" in
 queue) exec 8>"$ROOT/worker.lock";flock -n 8 || exit 0;"$PHP_BIN" artisan queue:work database --stop-when-empty --max-time=50 --timeout=90 --tries=3 --no-ansi ;;
 reminders) "$PHP_BIN" artisan invoices:overdue --no-ansi ;;
 *) exit 1 ;;
esac
