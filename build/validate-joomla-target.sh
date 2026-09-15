#!/usr/bin/env bash
set -euo pipefail

version="$(tr -d '\r\n' < VERSION)"

fail() {
  echo "Joomla target contract failed: $1" >&2
  exit 1
}

[[ -n "$version" ]] || fail "VERSION must not be empty"

grep -qF 'Target Joomla 6.1.3 exclusively.' AGENTS.md || fail "AGENTS.md must declare Joomla 6.1.3 as the exclusive target"
grep -qF 'Joomla 6.1.3 only' README.md || fail "README must declare Joomla 6.1.3-only compatibility"

if grep -qE "joomla: '(4|5)\." .github/workflows/ci.yml; then
  fail "CI must not install Joomla 4 or Joomla 5"
fi

grep -qF "joomla: '6.1.3'" .github/workflows/ci.yml || fail "CI must install Joomla 6.1.3"
if grep -qF "'7.4'" .github/workflows/ci.yml; then
  fail "CI must not target PHP 7.4"
fi

grep -qF "<version>${version}</version>" component/xdecaronotifications.xml || fail "component manifest must match VERSION (${version})"
grep -qF "<version>${version}</version>" plugins/task/xdecaronotifications/xdecaronotifications.xml || fail "task plugin manifest must match VERSION (${version})"
grep -qF "<version>${version}</version>" plugins/xdecaronotifications/email/email.xml || fail "email plugin manifest must match VERSION (${version})"
grep -qF "<version>${version}</version>" modules/admin/xdecaronotifications/mod_xdecaronotifications.xml || fail "administrator module manifest must match VERSION (${version})"
grep -qF "<version>${version}</version>" package/pkg_xdecaronotifications/pkg_xdecaronotifications.xml || fail "package manifest must match VERSION (${version})"
grep -qF "<version>${version}</version>" updates/pkg_xdecaronotifications.xml || fail "update feed must match VERSION (${version})"
grep -qF '<targetplatform name="joomla" version="6\.1\.3" />' updates/pkg_xdecaronotifications.xml || fail "update feed must target Joomla 6.1.3 only"
grep -qF '<php_minimum>8.3.0</php_minimum>' updates/pkg_xdecaronotifications.xml || fail "update feed must require PHP 8.3.0 or newer"

test -f "component/admin/sql/updates/mysql/${version}.sql" || fail "missing ${version} schema marker"

echo 'Joomla 6.1.3-only target contract passed.'
