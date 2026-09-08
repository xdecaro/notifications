# Notifications by xdecaro

Notifications is the shared notification center for the xdecaro Joomla ecosystem.

## Technical identity

- Component: `com_xdecaronotifications`
- PHP namespace: `Xdecaro\Component\Notifications`
- Reserved package identity: `pkg_xdecaronotifications`
- Reserved database namespace: `#__xdecaronotifications_*`

The package and database identifiers are reserved for future implementation; they must not be treated as shipped until their manifests/schema actually exist.

Notifications owns notification persistence, recipients, preferences, read/unread state, channels, delivery attempts, priorities and history. It may consume normalized cross-product events, but source-domain business rules remain in the originating component.

Initial Core integration targets:

- `Xdecaro\Core\Integration\EntityReference`
- `Xdecaro\Core\Integration\Capability`
- `Xdecaro\Core\Integration\IntegrationEvent`
- shared Core UI assets when available

Initial capabilities:

- `notifications.publish`
- `notifications.preferences`
- `notifications.delivery_status`

Target Joomla 4, 5 and 6 only where runtime compatibility is actually verified.
