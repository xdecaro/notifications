#!/usr/bin/env bash
set -euo pipefail

config="component/admin/config.xml"
manifest="component/xdecaronotifications.xml"
script="component/media/js/live-refresh.js"
dashboard="component/admin/tmpl/dashboard/default.php"
notifications="component/admin/tmpl/notifications/default.php"
deliveries="component/admin/tmpl/deliveries/default.php"
info_model="component/admin/src/Model/InformationModel.php"

fail() {
  echo "Live refresh contract failed: $1" >&2
  exit 1
}

grep -qF 'name="live_refresh_seconds"' "$config" || fail "missing configurable live refresh interval"
grep -qF 'default="10"' "$config" || fail "live refresh interval must default to 10 seconds"
grep -qF '<media destination="com_xdecaronotifications" folder="media">' "$manifest" || fail "component manifest must install media assets"
[[ -f "$script" ]] || fail "missing live-refresh.js"
grep -qF 'document.hidden' "$script" || fail "live polling must pause when the browser tab is hidden"
grep -qF 'setInterval' "$script" || fail "live polling interval is missing"
grep -qF "searchParams.set('tmpl', 'component')" "$script" || fail "live polling must fetch component-only markup"
grep -qF "querySelector('[data-xdecaro-live-content]')" "$script" || fail "live polling must replace only the dynamic result region"
for template in "$dashboard" "$notifications" "$deliveries"; do
  grep -qF 'data-xdecaro-live-refresh' "$template" || fail "$template is not live-refresh enabled"
  grep -qF 'data-xdecaro-live-content' "$template" || fail "$template has no isolated live content region"
done

grep -qF "class_exists('xdecaro\\\\Core\\\\Integration\\\\Capability')" "$info_model" || fail "Core diagnostic must use the canonical lowercase namespace"
if grep -qF "Text::_('JACTIONS')" "$notifications"; then
  fail "notification table must not render JACTIONS because the Italian Joomla string contains a %s placeholder"
fi
grep -qF "COM_XDECARONOTIFICATIONS_ACTIONS" "$notifications" || fail "notification table needs a component-specific Actions label"

echo 'Live refresh contract passed.'
