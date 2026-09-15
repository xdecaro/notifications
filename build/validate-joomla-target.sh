#!/usr/bin/env bash
set -euo pipefail

fail() {
  echo "Joomla target contract failed: $1" >&2
  exit 1
}

[[ "$(tr -d '\r\n' < VERSION)" == "1.1.1" ]] || fail "VERSION must be 1.1.1"

grep -qF 'Target Joomla 6.1.3 exclusively.' AGENTS.md || fail "AGENTS.md must declare Joomla 6.1.3 as the exclusive target"
grep -qF 'Joomla 6.1.3 only' README.md || fail "README must declare Joomla 6.1.3-only compatibility"

if grep -qE "joomla: '(4|5)\." .github/workflows/ci.yml; then
  fail "CI must not install Joomla 4 or Joomla 5"
fi

grep -qF "joomla: '6.1.3'" .github/workflows/ci.yml || fail "CI must install Joomla 6.1.3"
if grep -qF "php: ['7.4'" .github/workflows/ci.yml || grep -qF "'7.4'" .github/workflows/ci.yml; then
  fail "CI must not target PHP 7.4"
fi

grep -qF '<version>1.1.1</version>' component/xdecaronotifications.xml || fail "component manifest must be 1.1.1"
grep -qF '<version>1.1.1</version>' plugins/task/xdecaronotifications/xdecaronotifications.xml || fail "task plugin manifest must be 1.1.1"
grep -qF '<version>1.1.1</version>' plugins/xdecaronotifications/email/email.xml || fail "email plugin manifest must be 1.1.1"
grep -qF '<version>1.1.1</version>' modules/admin/xdecaronotifications/mod_xdecaronotifications.xml || fail "administrator module manifest must be 1.1.1"
grep -qF '<version>1.1.1</version>' package/pkg_xdecaronotifications/pkg_xdecaronotifications.xml || fail "package manifest must be 1.1.1"
grep -qF '<version>1.1.1</version>' updates/pkg_xdecaronotifications.xml || fail "update feed must be 1.1.1"
grep -qF '<targetplatform name="joomla" version="6\.1\.3" />' updates/pkg_xdecaronotifications.xml || fail "update feed must target Joomla 6.1.3 only"
grep -qF '<php_minimum>8.3.0</php_minimum>' updates/pkg_xdecaronotifications.xml || fail "update feed must require PHP 8.3.0 or newer"

test -f component/admin/sql/updates/mysql/1.1.1.sql || fail "missing 1.1.1 schema marker"

echo 'Joomla 6.1.3-only target contract passed.'
