# Notifications by xdecaro

Notifications is the shared notification center for the xdecaro Joomla ecosystem.

## Technical identity

- Component: `com_xdecaronotifications`
- PHP namespace: `Xdecaro\Component\Notifications`
- Reserved package identity: `pkg_xdecaronotifications`
- Database namespace: `#__xdecaronotifications_*`
- Current primary table: `#__xdecaronotifications_items`

Notifications owns notification persistence, recipients, read/unread state, priorities, expiry and notification history. Source-domain business rules remain in the originating component.

## Core integration

Core remains optional. Notifications 0.2.0 can use:

- `Xdecaro\Core\Integration\EntityReference`
- `Xdecaro\Core\Integration\Capability`
- `Xdecaro\Core\Integration\IntegrationEvent`
- shared Core UI assets when available

Implemented capabilities in 0.2.0:

- `notifications.publish`
- `notifications.state`
- `notifications.unread_count`

Do not declare preferences or delivery-channel capabilities until those features are actually implemented.

## Public component API

Other xdecaro products must not query Notifications tables directly. Boot the component through Joomla and use its public service:

```php
use Joomla\CMS\Factory;
use Xdecaro\Component\Notifications\Administrator\Extension\NotificationsComponent;

$component = Factory::getApplication()->bootComponent('com_xdecaronotifications');

if ($component instanceof NotificationsComponent) {
    $notificationId = $component->getNotificationService()->create([
        'external_key'    => 'document-expiry:300:2026-09-30',
        'source_component'=> 'com_xdecarodocuments',
        'source_entity'   => 'document',
        'source_id'       => '300',
        'recipient_type'  => 'user',
        'recipient_id'    => '42',
        'category'        => 'documents',
        'priority'        => 'high',
        'title'           => 'Document expiring',
        'message'         => 'A document requires attention.',
    ]);
}
```

`external_key` is idempotent within the source component, so safe retries do not create duplicate notifications.

When Core 1.2.0+ is installed, `CoreIntegrationService::createFromEvent()` can adapt a Core `IntegrationEvent` into the same persistence API without moving source-domain rules into Notifications.

## Administrator UI

0.2.0 adds:

- dashboard counters;
- recent notifications;
- notification center list;
- search;
- state, priority and category filters;
- pagination;
- mark-as-read action;
- archive action;
- Joomla CSRF and ACL checks on state-changing actions;
- Core shared UI assets when available, with Joomla fallback when Core is absent.

## Current boundaries

Not yet implemented and therefore not advertised as public capability:

- user notification preferences;
- email / PEC / push delivery channels;
- delivery attempts and retry queue;
- digest notifications;
- browser push;
- automatic global event listeners;
- scheduled expiry scanning.

Those should be added incrementally after the persistence and public-service boundary is proven.

## Compatibility

Target Joomla 4, 5 and 6 where runtime compatibility is verified. CI validates PHP 7.4 and PHP 8.3 syntax/build compatibility. Joomla itself may require a higher PHP version depending on the installed major.
