# Administrator Notification Bell Design

## Goal

Add a global Notifications bell to the Joomla administrator status bar so the signed-in administrator can see unread xdecaro notifications from every backend page without opening `com_xdecaronotifications` first.

## Architecture

Notifications remains the sole owner of notification persistence and read/query semantics. A new administrator module, `mod_xdecaronotifications`, renders the bell and the initial five current-user notifications by calling the component's existing `NotificationService` public methods. The module never queries Notifications tables directly.

A small read-only administrator controller endpoint (`bell.poll`) returns the signed-in user's unread count and up to five unread/read notifications as JSON. The module's JavaScript polls that endpoint every 10 seconds, pauses while the browser tab is hidden, and updates only the bell badge and dropdown rows. No WebSocket/SSE service is introduced.

## Joomla integration

The module is installed as part of `pkg_xdecaronotifications` and is automatically instantiated once in administrator position `status`, assigned to all administrator pages. Installation/update is idempotent: if the module instance already exists, package updates do not republish it or overwrite administrator choices.

The module uses Joomla status-bar markup and Bootstrap dropdown behavior, follows Atum light/dark styling, and hides when `hidemainmenu` is active. It must remain compatible with Joomla 4.4, 5.4 and 6.1, so the module uses the long-supported module entry-file pattern rather than requiring Joomla 5.1+ module dispatchers.

## Security

The polling endpoint is administrator-only and requires an authenticated identity with `core.login.admin`. It derives `recipient_id` from the current Joomla identity; callers cannot choose another user. It is read-only, so CSRF tokens are not required. The module's link to the full notification center is shown only when the current user has `core.manage` for `com_xdecaronotifications`.

## Data returned

The endpoint returns: unread count, and up to five active notifications for `recipient_type=user` and the current Joomla user ID. Each item includes only `id`, `title`, `message`, `priority`, `state`, `created`, and `action_url`. Expired and archived notifications are excluded by the existing service query defaults.

## Packaging and updates

Version becomes 1.1.0. The package contains component, task plugin, email channel plugin, and administrator bell module. Build/checksum/update-feed/release workflows continue to publish one canonical `pkg_xdecaronotifications_1.1.0.zip`.

## Verification

CI contract checks verify the module, endpoint, package manifest, automatic status-position instance, polling behavior, and build inclusion. Clean-install CI on Joomla 4.4.14, 5.4.8 and 6.1.3 verifies the module extension and one published administrator module instance assigned to all pages in addition to the existing component/plugins/tables.
