# Notifications by xdecaro

Notifications is the shared automatic notification center for the xdecaro Joomla ecosystem.

## Stable 1.0 scope

Notifications owns notification persistence, recipients, read/unread state, priorities, expiry, recipient preferences, delivery queue, delivery attempts, retry state and automatic channel dispatch. Source-domain rules remain in the originating product.

Technical identity:

- package: `pkg_xdecaronotifications`
- component: `com_xdecaronotifications`
- Scheduled Tasks plugin: `plg_task_xdecaronotifications`
- email channel plugin: `plg_xdecaronotifications_email`
- administrator bell module: `mod_xdecaronotifications`
- PHP namespace: `Xdecaro\Component\Notifications`
- database namespace: `#__xdecaronotifications_*`

Install `pkg_xdecaronotifications_<version>.zip` for the complete supported product. The package enables its included task and email plugins only on first install/discovery and provisions the administrator bell module in the `status` position; later updates preserve administrator choices. The package deliberately does not create Scheduler schedules on the administrator's behalf.

## Public capabilities

Core remains optional. When Xdecaro Core is available Notifications advertises:

- `notifications.publish`
- `notifications.query`
- `notifications.state`
- `notifications.unread_count`
- `notifications.preferences`
- `notifications.delivery_status`
- `notifications.delivery_channels`

Other products must boot `com_xdecaronotifications` and use its public services. They must never query Notifications tables directly.

The supported public surface is notification create/publish, recipient-scoped query and state operations, unread count, authorized preference operations, enqueue and authorized delivery-status query. Queue processing, claims/recovery, attempt persistence and maintenance are operational internals used by Notifications and its Scheduler plugin.

Notifications does not yet define a generic caller identity or authorization context. Recipient references remain integration identifiers, not proof of authorization. A cross-product caller-authorization contract must be agreed with xdecaro Core before it is introduced.

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

`external_key` is idempotent for the combination of source component, external event key and recipient (`recipient_type` + `recipient_id`). The same source event may therefore create one notification for recipient A and another notification for recipient B, while repeated publication for the same recipient resolves to the existing notification. A notification/channel delivery pair remains idempotent.

The recipient-scoped external-key index migration is additive and data-preserving: the older source/external-key unique constraint was stricter than the recipient-scoped constraint, so valid legacy rows do not need to be rewritten or deduplicated.

## Recipient query and state API

Consumers can read and update notifications without querying private tables:

```php
$notifications = $component->getNotificationService();

$items = $notifications->getForRecipient('user', '42', [
    'state' => ['unread', 'read'],
    'limit' => 25,
]);

$notifications->markReadForRecipient($notificationId, 'user', '42');
$notifications->archiveForRecipient($notificationId, 'user', '42');
```

Queries exclude expired notifications by default and support validated state, category and priority filters, pagination, and optional inclusion of expired records. Recipient-scoped state methods do not mutate a record belonging to another recipient.

**Authorization remains the caller's responsibility until the shared Core caller-authorization contract is consolidated.** A `recipient_type` / `recipient_id` match is an integration reference, not proof that the current Joomla user is authorized to act for that person, organization or other identity.

## Preferences

Rules are resolved by recipient, category and channel. A category-specific rule wins over the `*` recipient-wide default.

```php
$preferences = $component->getPreferenceService();
$preferences->setPreference('user', '42', '*', 'email', true);
$preferences->setPreference('user', '42', 'marketing', 'email', false);
```

Removing a rule restores default behavior. Components must still authorize access to the relevant recipient before reading or changing preferences.

## Delivery semantics

Notifications separates request acceptance from provider submission and confirmed delivery.

Public semantic states are:

- `request_accepted`: the delivery is persisted and is pending, processing or waiting for a safe retry;
- `submitted`: the transport/provider accepted the operation, but end-recipient delivery is not independently confirmed;
- `delivered`: the channel can attest to actual delivery;
- `failed`: the operation is known not to have been delivered and no further retry is scheduled;
- `outcome_unknown`: the provider may have performed the operation, but Notifications cannot determine the outcome safely.

The raw queue states `pending`, `processing` and `retry` remain stored for operational compatibility and map to `request_accepted`. `getStatuses()` exposes both the raw `state` and the semantic `delivery_status`.

A certain pre-delivery failure may request a normal retry. An unknown outcome is never blindly retried. A channel may opt into safe unknown-outcome retries only by implementing `IdempotentDeliveryChannelInterface` and honoring the stable `context['idempotency_key']` supplied by DeliveryService. Caller-provided idempotency keys are not trusted or forwarded to non-idempotent channels.

If a worker is interrupted while a delivery is `processing`, recovery records an `outcome_unknown` attempt. The claim returns to `retry` only for an idempotent channel and only while the maximum-attempt limit still permits a safe retry; otherwise the delivery remains terminal `outcome_unknown`.

## Delivery channels

`in_app` is native. Optional plugins in the `xdecaronotifications` group can implement `DeliveryChannelInterface` and register themselves through the `onXdecaroNotificationsRegisterChannels` event. A failing optional plugin is isolated by channel discovery and does not make the component unavailable.

`in_app` also implements `IdempotentDeliveryChannelInterface` because notification persistence is the delivery and retrying the adapter performs no additional external side effect.

The included `email` channel uses Joomla's configured mail transport. Recipient resolution is explicit:

1. for `recipient_type=user`, the canonical email of the Joomla user identified by `recipient_id` is authoritative;
2. `context['email']` for a Joomla user is accepted only when it matches that canonical address and therefore does not redirect delivery;
3. for a non-user recipient, a valid explicit `context['email']` remains supported;
4. otherwise the delivery is a permanent `recipient_email_missing` failure.

A differing Joomla-user `context['email']` is rejected as `recipient_email_override_forbidden`. This closes arbitrary destination redirection without duplicating People and without introducing the still-unapproved generic caller authorization contract.

Joomla mail transport acceptance is recorded as `submitted`, not `delivered`. A thrown or indeterminate send result is `outcome_unknown`, and the bundled email channel deliberately does not claim provider idempotency, so Notifications does not perform a blind retry after that uncertain outcome. Errors known to happen before the send operation remain ordinary retryable failures when appropriate.

Notifications email is for automatic alerts only. Official/manual email, templates requiring business workflow, PEC and protocolled communications belong to **Communications**.

## Scheduled Tasks

The included task plugin advertises two Joomla Scheduler routines:

- `Notifications: process delivery queue` (`xdecaronotifications.queue`)
- `Notifications: maintenance` (`xdecaronotifications.maintenance`)

Create schedules from **System → Scheduled Tasks** according to the site's operational needs. The queue routine uses the component batch and retry settings unless overridden in the task. Maintenance can archive Notifications records whose own `expires_at` has passed and purge old delivery-attempt history.

Notifications does not scan Documents, Membership, Finance or other private product tables for expiries. Each source component owns its business rule and publishes the resulting notification.

## Administrator UI

The component provides:

- a global administrator bell in Joomla's status bar with unread count and the five latest active notifications;
- Dashboard with notification and delivery-health counters;
- Notifications center with search, filters, pagination, mark-read and archive;
- Deliveries with queue state, attempts, channel and last error, including `submitted` and `outcome_unknown`;
- Preferences with recipient/category/channel rules;
- Information with Product + Environment, Included extensions + Updates, Connected components and Diagnostics;
- component settings for queue limits and maintenance retention.

The bell and notification administration screens use near-real-time polling and pause unnecessary polling while the browser tab is hidden. UI uses Xdecaro Core shared assets when available and safe Joomla fallback otherwise.

## Boundaries

Stable scope intentionally does not duplicate other products:

- PEC, official/manual communications and communication templates: **Communications**;
- source-domain expiry scanning: the source component;
- People/Organizations recipient identity and authorization: their owning providers/application layer;
- generic caller identity/authorization: pending shared Core decision, not reimplemented in Notifications;
- browser push subscriptions: optional future channel/plugin, not hardcoded into the notification core;
- digest aggregation: optional future policy/service, not required by the delivery core.

## Compatibility and release

Joomla 6.1.3 only. PHP 8.3.0 or newer is required. Joomla 4 and Joomla 5 are intentionally outside the supported target and are not considered when implementing or testing new Notifications changes.

CI validates the current code on PHP 8.3 and PHP 8.4 and performs a clean installation of the complete package on Joomla 6.1.3. Releases are deterministic and publish component, task plugin, email plugin, administrator module and package ZIPs plus SHA-256 checksums. The Joomla update channel is `updates/pkg_xdecaronotifications.xml`.
