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
grep -qF "'state' => 'unread'" "$module/mod_xdecaronotifications.php" || fail "initial bell render must query only unread notifications"
if grep -qF "'state' => ['unread', 'read']" "$module/mod_xdecaronotifications.php"; then
  fail "initial bell render must not include read notifications"
fi
grep -qF 'bell.poll' "$module/tmpl/default.php" || fail "module layout must expose the bell.poll endpoint"
grep -qF 'bell.markRead' "$module/tmpl/default.php" || fail "module layout must expose the current-user single-read endpoint"
grep -qF 'bell.markAllRead' "$module/tmpl/default.php" || fail "module layout must expose the current-user mark-all-read endpoint"
grep -qF 'data-read-url=' "$module/tmpl/default.php" || fail "module layout must expose the single-read URL"
grep -qF 'data-read-all-url=' "$module/tmpl/default.php" || fail "module layout must expose the mark-all-read URL"
grep -qF 'mod_xdecaronotifications/admin-bell.css' "$module/tmpl/default.php" || fail "module layout must load the bell stylesheet"
grep -qF 'dataset.pollUrl' "$module/media/js/admin-bell.js" || fail "polling script must consume the layout-provided endpoint"
grep -qF 'dataset.readUrl' "$module/media/js/admin-bell.js" || fail "bell script must consume the single-read endpoint"
grep -qF 'dataset.readAllUrl' "$module/media/js/admin-bell.js" || fail "bell script must consume the mark-all-read endpoint"
grep -qF 'setInterval' "$module/media/js/admin-bell.js" || fail "polling interval is missing"
grep -qF 'document.hidden' "$module/media/js/admin-bell.js" || fail "polling must pause while the tab is hidden"
grep -qF '10000' "$module/media/js/admin-bell.js" || fail "default poll cadence must be 10 seconds"
grep -qF 'icon-bell' "$module/tmpl/default.php" || fail "bell icon markup is missing"
grep -qF '<span class="position-relative">' "$module/tmpl/default.php" || fail "bell icon must have one relative positioning wrapper"
grep -qF 'position-absolute top-0 start-100 translate-middle badge rounded-pill' "$module/tmpl/default.php" || fail "unread count must be a compact badge over the bell"
grep -qF 'dropdown-menu' "$module/tmpl/default.php" || fail "bell dropdown markup is missing"
grep -qF 'class="dropdown-menu dropdown-menu-end xdecaro-notifications-menu"' "$module/tmpl/default.php" || fail "bell dropdown must expose its dedicated layout class"
grep -qF 'style="width: 500px; max-width: calc(100vw - 24px);"' "$module/tmpl/default.php" || fail "bell dropdown must be 500px wide on desktop and remain viewport-safe"
grep -qF 'xdecaro-notification-entry' "$module/tmpl/default.php" || fail "initial bell items must expose an entry wrapper for the row action"
grep -qF 'xdecaro-notification-entry' "$module/media/js/admin-bell.js" || fail "live bell items must preserve the entry wrapper"
grep -qF 'xdecaro-notification-row' "$module/tmpl/default.php" || fail "initial bell rows must use the two-column layout class"
grep -qF 'xdecaro-notification-row' "$module/media/js/admin-bell.js" || fail "live bell rows must preserve the two-column layout class"
grep -qF 'xdecaro-notification-content' "$module/tmpl/default.php" || fail "initial bell rows must group message and metadata in the content column"
grep -qF 'xdecaro-notification-content' "$module/media/js/admin-bell.js" || fail "live bell rows must group message and metadata in the content column"
grep -qF 'grid-template-columns: 120px minmax(0, 1fr);' "$module/media/css/admin-bell.css" || fail "bell rows must use a 120px title column plus one flexible content column"
grep -qF '.xdecaro-notification-entry {' "$module/media/css/admin-bell.css" || fail "bell entries must have their own layout wrapper"
grep -qF 'border-bottom: 1px solid' "$module/media/css/admin-bell.css" || fail "bell entries must have a visible row separator"
grep -qF 'xdecaro-notification-read' "$module/media/css/admin-bell.css" || fail "per-row mark-read action styling is missing"
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
grep -qF 'MOD_XDECARONOTIFICATIONS_MARK_READ' "$module/tmpl/default.php" || fail "unread bell rows must include the mark-read action"
grep -qF 'MOD_XDECARONOTIFICATIONS_MARK_ALL_READ' "$module/tmpl/default.php" || fail "bell footer must include the mark-all-read action"
grep -qF 'data-xdecaro-bell-read' "$module/tmpl/default.php" || fail "per-row mark-read button must expose a JavaScript hook"
grep -qF 'data-xdecaro-bell-read-all' "$module/tmpl/default.php" || fail "mark-all-read button must expose a JavaScript hook"
if grep -qF 'MOD_XDECARONOTIFICATIONS_CLEAR' "$module/tmpl/default.php"; then
  fail "bell footer must no longer expose destructive clear/archive wording"
fi
if grep -qF 'data-xdecaro-bell-clear' "$module/tmpl/default.php"; then
  fail "bell footer must no longer expose the archive-all clear action"
fi
grep -qF "method: 'POST'" "$module/media/js/admin-bell.js" || fail "read-state mutations must use POST"

grep -qF "core.login.admin" "$controller" || fail "bell endpoint must require administrator login authorization"
grep -qF "getUnreadCount('user'" "$controller" || fail "bell endpoint must use NotificationService unread-count API"
grep -qF "getForRecipient('user'" "$controller" || fail "bell endpoint must use NotificationService recipient query API"
grep -qF 'public function markRead(): void' "$controller" || fail "bell controller must expose a single-notification markRead endpoint"
grep -qF 'public function markAllRead(): void' "$controller" || fail "bell controller must expose a markAllRead endpoint"
grep -qF "Session::checkToken('post')" "$controller" || fail "read-state endpoints must enforce Joomla POST CSRF validation"
grep -qF "markReadForRecipient(\$notificationId, 'user', \$recipientId)" "$controller" || fail "single-read endpoint must scope the mutation to the authenticated Joomla user"
grep -qF "'state' => 'unread'" "$controller" || fail "mark-all-read must iterate only unread notifications"
bell_data_section="$(sed -n '/private function buildBellData/,/private function getNotificationsComponent/p' "$controller")"
grep -qF "'state' => 'unread'" <<<"$bell_data_section" || fail "bell poll/read responses must return only unread notifications"
if grep -qF "'state' => ['unread', 'read']" <<<"$bell_data_section"; then
  fail "bell poll/read responses must not include read notifications"
fi
if grep -qF 'DELETE FROM' "$controller"; then
  fail "bell read-state actions must never physically delete notification rows"
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
