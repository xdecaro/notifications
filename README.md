# Notifications by xdecaro

Notifications is the shared notification center for the xdecaro Joomla ecosystem.

It owns notification persistence, recipients, preferences, read/unread state, channels, delivery attempts, priorities and history. It may consume normalized cross-product events, but source-domain business rules remain in the originating component.

Initial Core integration targets:

- `Xdecaro\Core\Integration\EntityReference`
- `Xdecaro\Core\Integration\Capability`
- `Xdecaro\Core\Integration\IntegrationEvent`
- shared Core UI assets when available

Initial capabilities:

- `notifications.publish`
- `notifications.preferences`
- `notifications.delivery_status`

Target Joomla 4, 5 and 6 where technically possible.
