#!/usr/bin/env bash
set -euo pipefail

mapfile -t offenders < <(
  grep -RIl --include='*Model.php' 'extends ListModel' component/admin/src/Model \
    | while IFS= read -r file; do
        if grep -nF '$this->getApplication()' "$file" >/dev/null; then
          printf '%s\n' "$file"
        fi
      done
)

if ((${#offenders[@]} > 0)); then
  echo 'Joomla ListModel compatibility violation: getApplication() is not a ListModel method.' >&2
  echo 'Use Joomla\\CMS\\Factory::getApplication() (or an explicitly injected application) instead.' >&2
  printf ' - %s\n' "${offenders[@]}" >&2
  exit 1
fi

echo 'ListModel application access check passed.'
