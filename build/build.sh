#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="$(tr -d '\r\n' < "$ROOT/VERSION")"
COMPONENT="$ROOT/component"
TASK_PLUGIN="$ROOT/plugins/task/xdecaronotifications"
EMAIL_PLUGIN="$ROOT/plugins/xdecaronotifications/email"
PACKAGE="$ROOT/package/pkg_xdecaronotifications"
DIST="$ROOT/dist"

command -v php >/dev/null 2>&1 || { echo "PHP CLI is required." >&2; exit 1; }
command -v python3 >/dev/null 2>&1 || { echo "Python 3 is required." >&2; exit 1; }

for dir in "$COMPONENT" "$TASK_PLUGIN" "$EMAIL_PLUGIN" "$PACKAGE" "$ROOT/build"; do
  while IFS= read -r -d '' php_file; do
    php -l "$php_file" >/dev/null
  done < <(find "$dir" -type f -name '*.php' -print0)
done

if grep -R -nE --include='*.php' -- '->bind\([^,]+,[[:space:]]*\$[A-Za-z_][A-Za-z0-9_]*[[:space:]]*=' "$COMPONENT" "$TASK_PLUGIN" "$EMAIL_PLUGIN" "$PACKAGE"; then
  echo "Inline assignment passed to DatabaseQuery::bind(); bind a declared variable instead." >&2
  exit 1
fi

ROOT="$ROOT" VERSION="$VERSION" php -r '
$root = getenv("ROOT");
$version = getenv("VERSION");
$files = [
  $root . "/component/xdecaronotifications.xml" => "component",
  $root . "/plugins/task/xdecaronotifications/xdecaronotifications.xml" => "task plugin",
  $root . "/plugins/xdecaronotifications/email/email.xml" => "email plugin",
  $root . "/package/pkg_xdecaronotifications/pkg_xdecaronotifications.xml" => "package",
  $root . "/updates/pkg_xdecaronotifications.xml" => "update feed",
  $root . "/updates/changelog.xml" => "changelog",
  $root . "/plugins/task/xdecaronotifications/forms/queue.xml" => "queue form",
  $root . "/plugins/task/xdecaronotifications/forms/maintenance.xml" => "maintenance form",
];
libxml_use_internal_errors(true);
foreach ($files as $file => $label) {
  if (!is_file($file) || simplexml_load_file($file) === false) {
    fwrite(STDERR, "Invalid or missing XML: {$label}\n");
    exit(1);
  }
}
foreach ([
  $root . "/component/xdecaronotifications.xml",
  $root . "/plugins/task/xdecaronotifications/xdecaronotifications.xml",
  $root . "/plugins/xdecaronotifications/email/email.xml",
  $root . "/package/pkg_xdecaronotifications/pkg_xdecaronotifications.xml",
] as $manifestFile) {
  $manifest = simplexml_load_file($manifestFile);
  if (trim((string) $manifest->version) !== $version) {
    fwrite(STDERR, "VERSION mismatch in {$manifestFile}\n");
    exit(1);
  }
}
$feed = simplexml_load_file($root . "/updates/pkg_xdecaronotifications.xml");
if (trim((string) $feed->update->version) !== $version) {
  fwrite(STDERR, "VERSION mismatch in update feed.\n");
  exit(1);
}
' 

for table in \
  '#__xdecaronotifications_items' \
  '#__xdecaronotifications_preferences' \
  '#__xdecaronotifications_deliveries' \
  '#__xdecaronotifications_delivery_attempts'; do
  grep -q "$table" "$COMPONENT/admin/sql/install.mysql.utf8mb4.sql" || { echo "Missing expected table: $table" >&2; exit 1; }
done

if grep -R --line-number --fixed-strings '#__xdecaro_notifications' "$COMPONENT" "$TASK_PLUGIN" "$EMAIL_PLUGIN" "$PACKAGE"; then
  echo "Obsolete Notifications table namespace detected." >&2
  exit 1
fi

for update in 0.2.0 0.3.0 1.0.0; do
  test -f "$COMPONENT/admin/sql/updates/mysql/${update}.sql" || { echo "Missing SQL update ${update}." >&2; exit 1; }
done

rm -rf "$DIST"
mkdir -p "$DIST"

ROOT="$ROOT" VERSION="$VERSION" python3 - <<'PY'
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED
import hashlib
import os

root = Path(os.environ['ROOT'])
version = os.environ['VERSION']
dist = root / 'dist'
fixed = (2026, 1, 1, 0, 0, 0)


def add_bytes(zf, arcname, data, mode=0o644):
    info = ZipInfo(arcname, fixed)
    info.compress_type = ZIP_DEFLATED
    info.create_system = 3
    info.external_attr = (mode & 0xFFFF) << 16
    zf.writestr(info, data)


def build_dir_zip(source, output):
    with ZipFile(output, 'w') as zf:
        for path in sorted(p for p in source.rglob('*') if p.is_file()):
            mode = 0o755 if path.name.endswith('.sh') else 0o644
            add_bytes(zf, path.relative_to(source).as_posix(), path.read_bytes(), mode)
    with ZipFile(output) as zf:
        bad = zf.testzip()
        if bad:
            raise SystemExit(f'Corrupt ZIP entry in {output.name}: {bad}')

component_zip = dist / f'com_xdecaronotifications_{version}.zip'
task_zip = dist / f'plg_task_xdecaronotifications_{version}.zip'
email_zip = dist / f'plg_xdecaronotifications_email_{version}.zip'
package_zip = dist / f'pkg_xdecaronotifications_{version}.zip'

build_dir_zip(root / 'component', component_zip)
build_dir_zip(root / 'plugins/task/xdecaronotifications', task_zip)
build_dir_zip(root / 'plugins/xdecaronotifications/email', email_zip)

package_source = root / 'package/pkg_xdecaronotifications'
with ZipFile(package_zip, 'w') as zf:
    for path in sorted(p for p in package_source.rglob('*') if p.is_file()):
        add_bytes(zf, path.relative_to(package_source).as_posix(), path.read_bytes())
    add_bytes(zf, 'com_xdecaronotifications.zip', component_zip.read_bytes())
    add_bytes(zf, 'plg_task_xdecaronotifications.zip', task_zip.read_bytes())
    add_bytes(zf, 'plg_xdecaronotifications_email.zip', email_zip.read_bytes())

required = {
    component_zip: {
        'xdecaronotifications.xml',
        'admin/services/provider.php',
        'admin/sql/install.mysql.utf8mb4.sql',
        'admin/sql/updates/mysql/1.0.0.sql',
        'admin/src/Event/RegisterChannelsEvent.php',
        'admin/src/Service/ChannelDiscoveryService.php',
        'admin/src/Service/MaintenanceService.php',
        'admin/src/Model/InformationModel.php',
        'admin/tmpl/information/default.php',
    },
    task_zip: {
        'xdecaronotifications.xml',
        'services/provider.php',
        'src/Extension/Notifications.php',
        'forms/queue.xml',
        'forms/maintenance.xml',
    },
    email_zip: {
        'email.xml',
        'services/provider.php',
        'src/Extension/Email.php',
    },
    package_zip: {
        'pkg_xdecaronotifications.xml',
        'script.php',
        'com_xdecaronotifications.zip',
        'plg_task_xdecaronotifications.zip',
        'plg_xdecaronotifications_email.zip',
    },
}

for archive, expected in required.items():
    with ZipFile(archive) as zf:
        names = set(zf.namelist())
        missing = expected - names
        if missing:
            raise SystemExit(f'Missing required files in {archive.name}: ' + ', '.join(sorted(missing)))
        bad = zf.testzip()
        if bad:
            raise SystemExit(f'Corrupt ZIP entry in {archive.name}: {bad}')

assets = [component_zip, task_zip, email_zip, package_zip]
lines = []
for asset in assets:
    lines.append(f'{hashlib.sha256(asset.read_bytes()).hexdigest()}  {asset.name}')
(dist / 'SHA256SUMS.txt').write_text('\n'.join(lines) + '\n', encoding='utf-8')
PY

printf 'Built Notifications by xdecaro %s\n' "$VERSION"
cat "$DIST/SHA256SUMS.txt"
