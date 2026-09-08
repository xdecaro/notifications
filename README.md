# Notifications by xdecaro

Notifications is the shared automatic notification center for the xdecaro Joomla ecosystem.

## Stable 1.0 scope

Notifications owns notification persistence, recipients, read/unread state, priorities, expiry, recipient preferences, delivery queue, delivery attempts, retry state and automatic channel dispatch. Source-domain rules remain in the originating product.

Technical identity:

- package: `pkg_xdecaronotifications`
- component: `com_xdecaronotifications`
- Scheduled Tasks plugin: `plg_task_xdecaronotifications`
- email channel plugin: `plg_xdecaronotifications_email`
- PHP namespace: `Xdecaro\Component\Notifications`
- database namespace: `#__xdecaronotifications_*`

Install `pkg_xdecaronotifications_<version>.zip` for the complete supported product. The package enables its included task and email plugins but deliberately does not create schedules on the administrator's behalf.

## Public capabilities

Core remains optional. When Xdecaro Core is available Notifications advertises:

- `notifications.publish`
- `notifications.state`
- `notifications.unread_count`
- `notifications.preferences`
- `notifications.delivery_status`
- `notifications.delivery_channels`

Other products must boot `com_xdecaronotifications` and use its public services. They must never query Notifications tables directly.

## Publish and queue

```php
use Joomla\CMS\Factory;
use Xdecaro\Component\Notifications\Administrator\Extension\NotificationsComponent;

$component = Factory::getApplication()->bootComponent('com_xdecaronotifications');

if ($component instanceof NotificationsComponent) {
    $id = $component->getNotificationService()->create([
        'external_key' => 'document-expiry:300:2026-09-30',
        'source_component' => 'com_xdecarodocuments',
        'source_entity' => 'document',
        'source_id' => '300',
        'recipient_type' => 'user',
        'recipient_id' => '42',
        'category' => 'documents',
        'priority' => 'high',
        'title' => 'Document expiring',
        'message' => 'A document requires attention.',
        'expires_at' => '2026-10-01 00:00:00',
    ]);

    $component->getDeliveryService()->queueForNotification($id, ['in_app', 'email']);
}
```

`external_key` is idempotent inside the source component. A notification/channel delivery pair is also idempotent.

## Preferences

Rules are resolved by recipient, category and channel. A category-specific rule wins over the `*` recipient-wide default.

```php
$preferences = $component->getPreferenceService();
$preferences->setPreference('user', '42', '*', 'email', true);
$preferences->setPreference('user', '42', 'marketing', 'email', false);
```

Removing a rule restores default behavior.

## Delivery channels

`in_app` is native. Optional plugins in the `xdecaronotifications` group can implement `DeliveryChannelInterface` and register themselves through the `onXdecaroNotificationsRegisterChannels` event. A failing optional plugin is isolated by channel discovery and does not make the component unavailable.

The included `email` channel uses Joomla's configured mail transport. Recipient resolution is explicit:

1. `context['email']` when it is a valid address;
2. for `recipient_type=user`, the email of the Joomla user identified by `recipient_id`;
3. otherwise the delivery is a permanent `recipient_email_missing` failure.

Transport errors are retryable. Notifications email is for automatic alerts only. Official/manual email, templates requiring business workflow, PEC and protocolled communications belong to **Communications**.

## Scheduled Tasks

The included task plugin advertises two Joomla Scheduler routines:

- `Notifications: process delivery queue` (`xdecaronotifications.queue`)
- `Notifications: maintenance` (`xdecaronotifications.maintenance`)

Create schedules from **System → Scheduled Tasks** according to the site's operational needs. The queue routine uses the component batch and retry settings unless overridden in the task. Maintenance can archive Notifications records whose own `expires_at` has passed and purge old delivery-attempt history.

Notifications does not scan Documents, Membership, Finance or other private product tables for expiries. Each source component owns its business rule and publishes the resulting notification.

## Administrator UI

The component provides:

- Dashboard with notification and delivery-health counters;
- Notifications center with search, filters, pagination, mark-read and archive;
- Deliveries with queue state, attempts, channel and last error;
- Preferences with recipient/category/channel rules;
- Information with Product + Environment, Included extensions + Updates, Connected components and Diagnostics;
- component settings for queue limits and maintenance retention.

UI uses Xdecaro Core shared assets when available and safe Joomla fallback otherwise.

## Boundaries

Stable 1.0 intentionally does not duplicate other products:

- PEC, official/manual communications and communication templates: **Communications**;
- source-domain expiry scanning: the source component;
- People/Organizations recipient identity: their owning providers;
- browser push subscriptions: optional future channel/plugin, not hardcoded into the notification core;
- digest aggregation: optional future policy/service, not required by the delivery core.

## Compatibility and release

Target Joomla 4, 5 and 6 where the installed Joomla/PHP combination supports them. CI validates PHP 7.4 and PHP 8.3 syntax/build compatibility. Releases are deterministic and publish component, plugin and package ZIPs plus SHA-256 checksums. The Joomla update channel is `updates/pkg_xdecaronotifications.xml`.
