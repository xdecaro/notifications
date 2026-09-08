# Notifications by xdecaro

Notifications is the shared notification center for the xdecaro Joomla ecosystem.

Version `0.2.0` provides a working internal notification channel with persistent notifications, recipients, preferences, read/unread state, archive state, scheduling/expiry visibility and delivery-status history.

## Product boundary

Notifications answers **what a recipient needs to know**. It does not own the business rule that caused the alert.

Examples:
- Documents decides that a document is approaching expiry; Notifications stores and displays the alert.
- Finance decides that a payment is overdue; Notifications stores and displays the alert.
- Competitions decides that a result requires confirmation; Notifications stores and displays the alert.

Notifications is not Communications. Human-authored email/PEC/official communications, rich message templates and protocolled correspondence stay outside this component.

## Core integration

Core is optional at runtime. When Core 1.2.0+ is installed, Notifications uses:

- `Xdecaro\Core\Integration\EntityReference`;
- `Xdecaro\Core\Integration\Capability`;
- `Xdecaro\Core\Integration\IntegrationEvent`;
- shared Core UI assets.

Capabilities:

- `notifications.publish`;
- `notifications.preferences`;
- `notifications.delivery_status`.

Notifications still works when Core is absent; Core-specific helpers simply remain unavailable.

## Publishing from another component

The source component may boot Notifications only when it is installed and expose a normalized event or direct notification payload. Always enforce the source component's ACL/business rules before publishing.

```php
$component = \Joomla\CMS\Factory::getApplication()->bootComponent('com_decaronotifications');

if (method_exists($component, 'getNotificationService')) {
    $id = $component->getNotificationService()->publish([
        'source_component' => 'com_example',
        'source_entity' => 'record',
        'source_entity_id' => '42',
        'external_key' => 'record-42-expiry-2026-09-30',
        'category' => 'expiry',
        'priority' => 'high',
        'title' => 'Documento in scadenza',
        'message' => 'Il documento scade tra 7 giorni.',
        'action_url' => 'index.php?option=com_example&view=record&id=42',
        'published' => 1,
    ], [
        ['type' => 'user', 'id' => 123],
    ]);
}
```

`external_key` is strongly recommended for automated sources. Retries with the same source component and external key reuse the existing notification instead of creating duplicates.

## Recipients

0.2.0 supports:

- Joomla users: `['type' => 'user', 'id' => 123]`;
- generic external entities: `['type' => 'entity', 'component' => 'com_decaropeople', 'entity' => 'person', 'id' => 125]`.

Entity recipients do not require People or Organizations to be installed. They are stable references; actual channel resolution can be added through optional adapters later.

## Delivery channels

The only active delivery channel in 0.2.0 is `internal`, displayed in the Joomla notification center. The database already separates delivery attempts from notification content so email/browser adapters can be added later without mixing those responsibilities into the source products.

## Compatibility

Target Joomla 4, 5 and 6 where technically possible. PHP 7.4+ is required by the current code baseline.
