#!/usr/bin/env bash
set -euo pipefail

module="modules/admin/xdecaronotifications"
controller="component/admin/src/Controller/BellController.php"
information_model="component/admin/src/Model/InformationModel.php"
package="package/pkg_xdecaronotifications/pkg_xdecaronotifications.xml"
installer="package/pkg_xdecaronotifications/script.php"
build="build/build.sh"

fail() {
  echo "Administrator notification bell contract failed: $1" >&2
  exit 1
}

[[ -f "$module/mod_xdecaronotifications.php" ]] || fail "missing administrator module entry file"
[[ -f "$module/mod_xdecaronotifications.xml" ]] || fail "missing administrator module manifest"
[[ -f "$module/tmpl/default.php" ]] || fail "missing administrator module layout"
[[ -f "$module/media/js/admin-bell.js" ]] || fail "missing bell polling JavaScript"
[[ -f "$controller" ]] || fail "missing current-user bell poll controller"

grep -qF 'client="administrator"' "$module/mod_xdecaronotifications.xml" || fail "module must target administrator client"
grep -qF '<version>1.1.0</version>' "$module/mod_xdecaronotifications.xml" || fail "module manifest must be version 1.1.0"
grep -qF "getUnreadCount('user'" "$module/mod_xdecaronotifications.php" || fail "initial module render must use NotificationService unread-count API"
grep -qF "getForRecipient('user'" "$module/mod_xdecaronotifications.php" || fail "initial module render must use NotificationService recipient query API"
grep -qF 'bell.poll' "$module/tmpl/default.php" || fail "module layout must expose the bell.poll endpoint"
grep -qF 'dataset.pollUrl' "$module/media/js/admin-bell.js" || fail "polling script must consume the layout-provided endpoint"
grep -qF 'setInterval' "$module/media/js/admin-bell.js" || fail "polling interval is missing"
grep -qF 'document.hidden' "$module/media/js/admin-bell.js" || fail "polling must pause while the tab is hidden"
grep -qF '10000' "$module/media/js/admin-bell.js" || fail "default poll cadence must be 10 seconds"
grep -qF 'icon-bell' "$module/tmpl/default.php" || fail "bell icon markup is missing"
grep -qF 'dropdown-menu' "$module/tmpl/default.php" || fail "bell dropdown markup is missing"

grep -qF "core.login.admin" "$controller" || fail "bell endpoint must require administrator login authorization"
grep -qF "getUnreadCount('user'" "$controller" || fail "bell endpoint must use NotificationService unread-count API"
grep -qF "getForRecipient('user'" "$controller" || fail "bell endpoint must use NotificationService recipient query API"
if grep -qF '#__xdecaronotifications_' "$controller" "$module/mod_xdecaronotifications.php"; then
  fail "bell surfaces must not query Notifications tables directly"
fi

grep -qF "extensionRow('module', 'mod_xdecaronotifications'" "$information_model" || fail "Information page must list the bundled administrator bell module"
grep -qF 'mod_xdecaronotifications.zip' "$package" || fail "package manifest does not include administrator bell module"
grep -qF 'mod_xdecaronotifications' "$installer" || fail "package installer does not provision the administrator module instance"
grep -qF "'status'" "$installer" || fail "administrator module instance must use status position"
grep -qF '#__modules_menu' "$installer" || fail "administrator module must be assigned to all pages"
grep -qF 'mod_xdecaronotifications_' "$build" || fail "build does not create administrator module archive"

echo 'Administrator notification bell contract passed.'
