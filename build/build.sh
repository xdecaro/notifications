#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="$(tr -d '\r\n' < "$ROOT/VERSION")"
DIST="$ROOT/dist"
rm -rf "$DIST"
mkdir -p "$DIST"

ROOT="$ROOT" VERSION="$VERSION" python3 - <<'PY'
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED
import hashlib, os

root = Path(os.environ['ROOT'])
version = os.environ['VERSION']
dist = root / 'dist'
fixed = (2026, 1, 1, 0, 0, 0)

def add_bytes(zf, arcname, data, executable=False):
    info = ZipInfo(arcname, fixed)
    info.compress_type = ZIP_DEFLATED
    info.create_system = 3
    info.external_attr = ((0o755 if executable else 0o644) & 0xFFFF) << 16
    zf.writestr(info, data)

def add_tree(zf, source):
    for path in sorted(p for p in source.rglob('*') if p.is_file()):
        add_bytes(zf, path.relative_to(source).as_posix(), path.read_bytes(), os.access(path, os.X_OK))

component_zip = dist / f'com_decaronotifications_{version}.zip'
with ZipFile(component_zip, 'w') as zf:
    add_tree(zf, root / 'component')

package_zip = dist / f'pkg_decaronotifications_{version}.zip'
with ZipFile(package_zip, 'w') as zf:
    add_bytes(zf, 'pkg_decaronotifications.xml', (root / 'package/pkg_decaronotifications.xml').read_bytes())
    add_bytes(zf, 'com_decaronotifications.zip', component_zip.read_bytes())

lines = []
for path in (component_zip, package_zip):
    lines.append(f"{hashlib.sha256(path.read_bytes()).hexdigest()}  {path.name}\n")
(dist / 'SHA256SUMS.txt').write_text(''.join(lines), encoding='utf-8')
PY

echo "Built Notifications by xdecaro $VERSION"
