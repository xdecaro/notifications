#!/usr/bin/env bash
set -euo pipefail

module="modules/admin/xdecaronotifications"
controller="component/admin/src/Controller/BellController.php"
information_model="component/admin/src/Model/InformationModel.php"
package="package/pkg_xdecaronotifications/pkg_xdecaronotifications.xml"
installer="package/pkg_xdecaronotifications/script.php"
build="build/build.sh"
version="$(tr -d '\r\n' < VERSION)"

fail() {
  echo "Administrator notification bell contract failed: $1" >&2
  exit 1
}

[[ -f "$module/mod_xdecaronotifications.php" ]] || fail "missing administrator module entry file"
[[ -f "$module/mod_xdecaronotifications.xml" ]] || fail "missing administrator module manifest"
[[ -f "$module/tmpl/default.php" ]] || fail "missing administrator module layout"
[[ -f "$module/media/js/admin-bell.js" ]] || fail "missing bell polling JavaScript"
[[ -f "$module/media/css/admin-bell.css" ]] || fail "missing bell layout stylesheet"
[[ -f "$controller" ]] || fail "missing current-user bell controller"

grep -qF 'client="administrator"' "$module/mod_xdecaronotifications.xml" || fail "module must target administrator client"
grep -qF "<version>${version}</version>" "$module/mod_xdecaronotifications.xml" || fail "module manifest must match VERSION (${version})"
grep -qF '<folder>css</folder>' "$module/mod_xdecaronotifications.xml" || fail "module manifest must install bell CSS assets"
grep -qF "getUnreadCount('user'" "$module/mod_xdecaronotifications.php" || fail "initial module render must use NotificationService unread-count API"
grep -qF "getForRecipient('user'" "$module/mod_xdecaronotifications.php" || fail "initial module render must use NotificationService recipient query API"
grep -qF 'bell.poll' "$module/tmpl/default.php" || fail "module layout must expose the bell.poll endpoint"
grep -qF 'bell.archiveAll' "$module/tmpl/default.php" || fail "module layout must expose the current-user clear endpoint"
grep -qF 'mod_xdecaronotifications/admin-bell.css' "$module/tmpl/default.php" || fail "module layout must load the bell stylesheet"
grep -qF 'dataset.pollUrl' "$module/media/js/admin-bell.js" || fail "polling script must consume the layout-provided endpoint"
grep -qF 'dataset.archiveUrl' "$module/media/js/admin-bell.js" || fail "bell script must consume the layout-provided clear endpoint"
grep -qF 'setInterval' "$module/media/js/admin-bell.js" || fail "polling interval is missing"
grep -qF 'document.hidden' "$module/media/js/admin-bell.js" || fail "polling must pause while the tab is hidden"
grep -qF '10000' "$module/media/js/admin-bell.js" || fail "default poll cadence must be 10 seconds"
grep -qF 'icon-bell' "$module/tmpl/default.php" || fail "bell icon markup is missing"
grep -qF '<span class="position-relative">' "$module/tmpl/default.php" || fail "bell icon must have one relative positioning wrapper"
grep -qF 'position-absolute top-0 start-100 translate-middle badge rounded-pill' "$module/tmpl/default.php" || fail "unread count must be a compact badge over the bell"
grep -qF 'dropdown-menu' "$module/tmpl/default.php" || fail "bell dropdown markup is missing"
grep -qF 'class="dropdown-menu dropdown-menu-end xdecaro-notifications-menu"' "$module/tmpl/default.php" || fail "bell dropdown must expose its dedicated layout class"
grep -qF 'style="width: 500px; max-width: calc(100vw - 24px);"' "$module/tmpl/default.php" || fail "bell dropdown must be 500px wide on desktop and remain viewport-safe"
grep -qF 'xdecaro-notification-row' "$module/tmpl/default.php" || fail "initial bell rows must use the two-column layout class"
grep -qF 'xdecaro-notification-row' "$module/media/js/admin-bell.js" || fail "live bell rows must preserve the two-column layout class"
grep -qF 'xdecaro-notification-content' "$module/tmpl/default.php" || fail "initial bell rows must group message and metadata in the content column"
grep -qF 'xdecaro-notification-content' "$module/media/js/admin-bell.js" || fail "live bell rows must group message and metadata in the content column"
grep -qF 'grid-template-columns: 120px minmax(0, 1fr);' "$module/media/css/admin-bell.css" || fail "bell rows must use a 120px title column plus one flexible content column"
if grep -qF 'grid-template-columns: 120px minmax(0, 1fr) auto;' "$module/media/css/admin-bell.css"; then
  fail "bell metadata must not occupy a third desktop column"
fi
if grep -qF 'white-space: nowrap;' "$module/media/css/admin-bell.css"; then
  fail "bell metadata must wrap inside the content column instead of forcing horizontal overflow"
fi
grep -qF '@media (max-width: 575.98px)' "$module/media/css/admin-bell.css" || fail "bell row layout must include a narrow-screen fallback"
grep -qF 'grid-template-columns: 1fr;' "$module/media/css/admin-bell.css" || fail "bell rows must stack on narrow screens"
if grep -qF 'text-body-secondary' "$module/tmpl/default.php"; then
  fail "bell dropdown secondary text must not inherit the light body color inside Atum's dark header dropdown"
fi
grep -qF 'text-white opacity-75' "$module/tmpl/default.php" || fail "bell dropdown secondary text must keep readable contrast in both light and dark administrator modes"
if grep -qF 'text-body-secondary' "$module/media/js/admin-bell.js"; then
  fail "live bell refresh must not restore unreadable dark secondary text inside Atum's header dropdown"
fi
grep -qF 'text-white opacity-75' "$module/media/js/admin-bell.js" || fail "live bell refresh must preserve readable secondary text contrast"

grep -qF 'xdecaro-notifications-footer' "$module/tmpl/default.php" || fail "bell dropdown must expose a dedicated footer"
grep -qF 'background-color: var(--body-bg);' "$module/media/css/admin-bell.css" || fail "footer background must follow Joomla's active light/dark body theme"
grep -qF 'color: var(--body-color);' "$module/media/css/admin-bell.css" || fail "footer actions must use Joomla's active light/dark text color"
grep -qF 'border-top: 1px solid color-mix(in srgb, var(--body-color) 24%, transparent);' "$module/media/css/admin-bell.css" || fail "footer separator must derive from Joomla's active theme text color"
grep -qF '.xdecaro-notifications-footer .xdecaro-notifications-footer-action {' "$module/media/css/admin-bell.css" || fail "footer action selector must outrank Atum header link color rules"
if grep -qF 'color: inherit;' "$module/media/css/admin-bell.css"; then
  fail "footer actions must not inherit the header dropdown's light text color in Joomla light mode"
fi
grep -qF 'MOD_XDECARONOTIFICATIONS_CLEAR' "$module/tmpl/default.php" || fail "bell footer must include the clear-notifications action"
grep -qF 'data-xdecaro-bell-clear' "$module/tmpl/default.php" || fail "bell footer clear button must expose a JavaScript hook"
grep -qF 'window.confirm' "$module/media/js/admin-bell.js" || fail "clear-all action must require explicit confirmation"
grep -qF "method: 'POST'" "$module/media/js/admin-bell.js" || fail "clear-all action must use POST"

grep -qF "core.login.admin" "$controller" || fail "bell endpoint must require administrator login authorization"
grep -qF "getUnreadCount('user'" "$controller" || fail "bell endpoint must use NotificationService unread-count API"
grep -qF "getForRecipient('user'" "$controller" || fail "bell endpoint must use NotificationService recipient query API"
grep -qF "Session::checkToken('post')" "$controller" || fail "clear-all endpoint must enforce Joomla POST CSRF validation"
grep -qF "archiveForRecipient(\$notificationId, 'user', \$recipientId)" "$controller" || fail "clear-all endpoint must archive only notifications belonging to the authenticated Joomla user"
if grep -qF 'DELETE FROM' "$controller"; then
  fail "clear-notifications must archive rather than physically delete notification rows"
fi
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
