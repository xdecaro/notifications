#!/usr/bin/env bash
set -euo pipefail

controller="component/admin/src/Controller/InformationController.php"
template="component/admin/tmpl/information/default.php"

fail() {
  echo "Diagnostic test-notification contract failed: $1" >&2
  exit 1
}

[[ -f "$controller" ]] || fail "missing InformationController.php"
grep -qF 'public function sendTestNotification()' "$controller" || fail "missing sendTestNotification controller action"
grep -qF 'Session::checkToken()' "$controller" || fail "controller action must validate the Joomla CSRF token"
grep -qF "authorise('core.manage', 'com_xdecaronotifications')" "$controller" || fail "controller action must require core.manage"
grep -qF "getNotificationService()->create" "$controller" || fail "controller action must create through NotificationService"
grep -qF "getDeliveryService()->queueForNotification" "$controller" || fail "controller action must queue through DeliveryService"
grep -qF "['in_app']" "$controller" || fail "diagnostic test must queue only the in_app channel"

grep -qF 'task=information.sendTestNotification' "$template" || fail "Information diagnostics must post to information.sendTestNotification"
grep -qF "HTMLHelper::_('form.token')" "$template" || fail "Information diagnostics form must include a Joomla form token"

echo 'Diagnostic test-notification contract passed.'
