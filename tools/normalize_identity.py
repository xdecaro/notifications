from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
REPLACEMENTS = (
    ("decaronotifications", "xdecaronotifications"),
    ("DECARONOTIFICATIONS", "XDECARONOTIFICATIONS"),
    ("Decaronotifications", "Notifications"),
)

TEXT_SUFFIXES = {".php", ".xml", ".ini", ".md", ".json", ".yml", ".yaml", ".txt"}
TEXT_NAMES = {"VERSION"}


def is_ignored(path: Path) -> bool:
    return ".git" in path.parts or ".github" in path.parts or path == Path(__file__)


for path in sorted(ROOT.rglob("*")):
    if not path.is_file() or is_ignored(path):
        continue
    if path.suffix.lower() not in TEXT_SUFFIXES and path.name not in TEXT_NAMES:
        continue
    text = path.read_text(encoding="utf-8")
    new = text
    for old, replacement in REPLACEMENTS:
        new = new.replace(old, replacement)
    if new != text:
        path.write_text(new, encoding="utf-8")

# Rename files/directories bottom-up so manifests, language files and PHP classes match the new identity.
paths = (p for p in ROOT.rglob("*") if not is_ignored(p))
for path in sorted(paths, key=lambda p: len(p.parts), reverse=True):
    name = path.name
    new_name = name
    for old, replacement in REPLACEMENTS:
        new_name = new_name.replace(old, replacement)
    if new_name != name:
        path.rename(path.with_name(new_name))
