# Notifications by xdecaro

Notifications is the shared notification center for the xdecaro Joomla ecosystem.

## Technical identity

- Component: `com_xdecaronotifications`
- PHP namespace: `Xdecaro\Component\Notifications`
- Reserved package identity: `pkg_xdecaronotifications`
- Database namespace: `#__xdecaronotifications_*`
- Notification table: `#__xdecaronotifications_items`
- Preference table: `#__xdecaronotifications_preferences`
- Delivery table: `#__xdecaronotifications_deliveries`
- Attempt history: `#__xdecaronotifications_delivery_attempts`

Source-domain business rules remain in the originating component. Notifications owns the resulting notification record, recipient preferences and delivery lifecycle.

## Core integration

Core remains optional. Notifications 0.3.0 supports:

- `Xdecaro\Core\Integration\EntityReference`
- `Xdecaro\Core\Integration\Capability`
- `Xdecaro\Core\Integration\IntegrationEvent`
- shared Core UI assets when available

Public capabilities:

- `notifications.publish`
- `notifications.state`
- `notifications.unread_count`
- `notifications.preferences`
- `notifications.delivery_status`
- `notifications.delivery_channels`

## Public component API

Other xdecaro products must not query Notifications tables directly. Boot the component through Joomla:

```php
use Joomla\CMS\Factory;
use Xdecaro\Component\Notifications\Administrator\Extension\NotificationsComponent;

$component = Factory::getApplication()->bootComponent('com_xdecaronotifications');

if ($component instanceof NotificationsComponent) {
    $notificationId = $component->getNotificationService()->create([
        'external_key'     => 'document-expiry:300:2026-09-30',
        'source_component' => 'com_xdecarodocuments',
        'source_entity'    => 'document',
        'source_id'        => '300',
        'recipient_type'   => 'user',
        'recipient_id'     => '42',
        'category'         => 'documents',
        'priority'         => 'high',
        'title'            => 'Document expiring',
        'message'          => 'A document requires attention.',
    ]);

    $component->getDeliveryService()->queueForNotification(
        $notificationId,
        ['in_app', 'email']
    );
}
```

`external_key` is idempotent within the source component. Delivery rows are also idempotent per notification/channel.

## Preferences

Preferences are resolved by recipient, category and channel. A category-specific rule takes precedence over the `*` wildcard rule.

```php
$preferences = $component->getPreferenceService();
$preferences->setPreference('user', '42', '*', 'email', true);
$preferences->setPreference('user', '42', 'marketing', 'email', false);
```

Removing a rule restores default behavior rather than creating a second implicit state.

## Delivery adapters

`in_app` is included natively. Email, PEC, push and future channels must be optional adapters implementing `DeliveryChannelInterface` and registered through:

```php
$component->registerDeliveryChannel($channelAdapter);
```

Adapters return `DeliveryResult` and must not write Notifications tables. `DeliveryService` owns queue state, atomic claims, attempts, retry scheduling, permanent failures and delivery status.

Missing adapters do not break Notifications; queued deliveries remain pending and are retried later.

## Administrator UI

0.3.0 provides:

- dashboard notification and delivery-health counters;
- notification center with search/filter/pagination;
- Deliveries screen with channel/state filters, attempts and last error;
- secure manual queue processing;
- Preferences screen with recipient/category/channel rules;
- mark-read and archive actions;
- Joomla ACL and CSRF checks on state-changing actions;
- responsive Core shared UI when available, with Joomla fallback when Core is absent.

## Current boundaries

Not yet implemented:

- concrete email/PEC/push adapters;
- automatic adapter discovery through Joomla plugins;
- Joomla Scheduled Tasks worker registration;
- digest notifications;
- browser push subscription management;
- scheduled expiry scanning.

Communications remains the owner of official/manual communications and PEC workflows. Notifications must not become a second Communications component.

## Compatibility

Target Joomla 4, 5 and 6 where runtime compatibility is verified. CI validates PHP 7.4 and PHP 8.3 syntax/build compatibility. Joomla itself may require a higher PHP version depending on the installed major.
