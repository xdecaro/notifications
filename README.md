# Notifications by xdecaro

Notifications is the shared notification center for the xdecaro Joomla ecosystem.

## Technical identity

- Component: `com_xdecaronotifications`
- PHP namespace: `Xdecaro\Component\Notifications`
- Package: `pkg_xdecaronotifications`
- Database namespace: `#__xdecaronotifications_*`

Version `0.2.0` implements these previously reserved package/database identities and provides a working internal notification channel.

## Product boundary

Notifications answers **what a recipient needs to know**. The source product remains responsible for deciding **why and when** an alert exists.

Examples:
- Documents decides that a document is approaching expiry; Notifications persists and displays the alert.
- Finance decides that a payment is overdue; Notifications persists and displays the alert.
- Competitions decides that a result requires confirmation; Notifications persists and displays the alert.

Notifications is not Communications. Human-authored email/PEC, rich correspondence, official communications and protocolled delivery remain outside this component.

## Core integration

Core is optional at runtime. Core 1.2.0+ enables:

- `Xdecaro\Core\Integration\EntityReference`;
- `Xdecaro\Core\Integration\Capability`;
- `Xdecaro\Core\Integration\IntegrationEvent`;
- shared Core UI assets.

Capabilities:

- `notifications.publish`;
- `notifications.preferences`;
- `notifications.delivery_status`.

Notifications remains operational when Core is absent; Core-specific helpers simply stay unavailable.

## Publishing from another product

Source products must enforce their own ACL and business rules before calling Notifications. `external_key` is strongly recommended for automated events so retries remain idempotent.

```php
$notifications = \Joomla\CMS\Factory::getApplication()->bootComponent('com_xdecaronotifications');

if (method_exists($notifications, 'getNotificationService')) {
    $notifications->getNotificationService()->publish([
        'source_component' => 'com_example',
        'source_entity' => 'record',
        'source_entity_id' => '42',
        'external_key' => 'record-42-expiry-2026-09-30',
        'category' => 'expiry',
        'priority' => 'high',
        'title' => 'Documento in scadenza',
        'message' => 'Il documento scade tra 7 giorni.',
        'action_url' => 'index.php?option=com_example&view=record&id=42',
    ], [
        ['type' => 'user', 'id' => 123],
    ]);
}
```

## Recipients

0.2.0 supports:

- Joomla users, e.g. `['type' => 'user', 'id' => 123]`;
- generic entity references, e.g. `['type' => 'entity', 'component' => 'com_xdecaropeople', 'entity' => 'person', 'id' => 125]`.

Generic entities are stored without requiring the referenced extension. They remain queued for internal delivery until a real resolver maps them to a supported recipient/channel.

## Active channel

The only active delivery channel in 0.2.0 is `internal`, shown in the logged-in Joomla user's notification center. Email, PEC, browser push and other transports are not presented as active functionality.

## Compatibility and validation

The code and update metadata target Joomla 4, 5 and 6 where technically possible. CI validates PHP 7.4 and PHP 8.3, XML, SQL safety boundaries and deterministic ZIPs. A real runtime install/update test on each Joomla major is still required before declaring the release stable.
