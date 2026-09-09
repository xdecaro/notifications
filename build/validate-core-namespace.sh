#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MATCHES="$(grep -RInF 'Xdecaro\Core' "$ROOT/component" "$ROOT/plugins" "$ROOT/package" --include='*.php' || true)"
if [[ -n "$MATCHES" ]]; then
  printf '%s\n' "$MATCHES"
  echo 'Deprecated Xdecaro\Core usage remains in Notifications runtime PHP.' >&2
  exit 1
fi
