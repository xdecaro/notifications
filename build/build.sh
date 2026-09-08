#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="$(tr -d '\r\n' < "$ROOT/VERSION")"
COMPONENT="$ROOT/component"
MANIFEST="$COMPONENT/xdecaronotifications.xml"
DIST="$ROOT/dist"

command -v php >/dev/null 2>&1 || { echo "PHP CLI is required." >&2; exit 1; }
command -v python3 >/dev/null 2>&1 || { echo "Python 3 is required." >&2; exit 1; }

while IFS= read -r -d '' php_file; do
    php -l "$php_file" >/dev/null
done < <(find "$COMPONENT" -type f -name '*.php' -print0)

# Joomla DatabaseQuery::bind() receives its value by reference. Inline
# assignments such as bind(':state', $state = 'pending') are invalid at
# runtime even though PHP lint accepts them.
if grep -R -nE -- '->bind\([^,]+,[[:space:]]*\$[A-Za-z_][A-Za-z0-9_]*[[:space:]]*=' "$COMPONENT" --include='*.php'; then
    echo "Inline assignment passed to DatabaseQuery::bind(); bind a declared variable instead." >&2
    exit 1
fi

php -r '
libxml_use_internal_errors(true);
$version = trim(file_get_contents($argv[1]));
$manifest = simplexml_load_file($argv[2]);
if ($manifest === false) {
    fwrite(STDERR, "Invalid component manifest.\n");
    exit(1);
}
if (trim((string) $manifest->version) !== $version) {
    fwrite(STDERR, "VERSION and manifest version differ.\n");
    exit(1);
}
if (trim((string) $manifest->namespace) !== "Xdecaro\\Component\\Notifications") {
    fwrite(STDERR, "Unexpected component namespace.\n");
    exit(1);
}
if (trim((string) $manifest->install->sql->file) !== "sql/install.mysql.utf8mb4.sql") {
    fwrite(STDERR, "Install SQL is not registered.\n");
    exit(1);
}
if (trim((string) $manifest->update->schemas->schemapath) !== "sql/updates/mysql") {
    fwrite(STDERR, "SQL update schema path is not registered.\n");
    exit(1);
}
' "$ROOT/VERSION" "$MANIFEST"

for table in \
    '#__xdecaronotifications_items' \
    '#__xdecaronotifications_preferences' \
    '#__xdecaronotifications_deliveries' \
    '#__xdecaronotifications_delivery_attempts'; do
    if ! grep -q "$table" "$COMPONENT/admin/sql/install.mysql.utf8mb4.sql"; then
        echo "Missing expected Notifications table: $table" >&2
        exit 1
    fi
done

if grep -R --line-number --fixed-strings '#__xdecaro_notifications' "$COMPONENT"; then
    echo "Obsolete Notifications table namespace detected." >&2
    exit 1
fi

if [ ! -f "$COMPONENT/admin/sql/updates/mysql/0.3.0.sql" ]; then
    echo "Missing 0.3.0 SQL update." >&2
    exit 1
fi

rm -rf "$DIST"
mkdir -p "$DIST"

ROOT="$ROOT" VERSION="$VERSION" python3 - <<'PY'
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED
import hashlib
import os

root = Path(os.environ['ROOT'])
version = os.environ['VERSION']
source = root / 'component'
dist = root / 'dist'
fixed = (2026, 1, 1, 0, 0, 0)


def add_bytes(zf: ZipFile, arcname: str, data: bytes):
    info = ZipInfo(arcname, fixed)
    info.compress_type = ZIP_DEFLATED
    info.create_system = 3
    info.external_attr = (0o644 & 0xFFFF) << 16
    zf.writestr(info, data)

zip_path = dist / f'com_xdecaronotifications_{version}.zip'
with ZipFile(zip_path, 'w') as zf:
    for path in sorted(p for p in source.rglob('*') if p.is_file()):
        add_bytes(zf, path.relative_to(source).as_posix(), path.read_bytes())

with ZipFile(zip_path) as zf:
    names = set(zf.namelist())
    required = {
        'xdecaronotifications.xml',
        'admin/services/provider.php',
        'admin/sql/install.mysql.utf8mb4.sql',
        'admin/sql/updates/mysql/0.2.0.sql',
        'admin/sql/updates/mysql/0.3.0.sql',
        'admin/src/Service/NotificationService.php',
        'admin/src/Service/PreferenceService.php',
        'admin/src/Service/DeliveryService.php',
        'admin/src/Service/DeliveryChannelInterface.php',
        'admin/src/Service/DeliveryResult.php',
        'admin/src/Service/ChannelRegistry.php',
        'admin/src/Service/InAppChannel.php',
        'admin/src/Model/NotificationsModel.php',
        'admin/src/Model/DeliveriesModel.php',
        'admin/src/Model/PreferencesModel.php',
        'admin/tmpl/notifications/default.php',
        'admin/tmpl/deliveries/default.php',
        'admin/tmpl/preferences/default.php',
    }
    missing = required - names
    if missing:
        raise SystemExit('Missing required ZIP files: ' + ', '.join(sorted(missing)))
    bad = zf.testzip()
    if bad:
        raise SystemExit('Corrupt ZIP entry: ' + bad)

sha = hashlib.sha256(zip_path.read_bytes()).hexdigest()
(dist / 'SHA256SUMS.txt').write_text(f'{sha}  {zip_path.name}\n', encoding='utf-8')
PY

printf 'Built Notifications by xdecaro %s\n' "$VERSION"
cat "$DIST/SHA256SUMS.txt"
