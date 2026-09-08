# Changelog

All notable changes to **Notifications by xdecaro** are documented here.

## 0.3.0 — 2026-09-09

### Added

- Recipient/category/channel preferences with wildcard category defaults.
- Public `PreferenceService` API with set, remove, resolve and list operations.
- Delivery queue in `#__xdecaronotifications_deliveries`.
- Immutable delivery-attempt history in `#__xdecaronotifications_delivery_attempts`.
- `DeliveryChannelInterface`, `DeliveryResult` and `ChannelRegistry` extension contracts.
- Native `in_app` delivery adapter.
- Public delivery API exposed through the Joomla component service.
- Retry scheduling, maximum-attempt protection and atomic delivery claims to avoid duplicate sends from overlapping workers.
- Administrator Deliveries screen with state/channel/search filters, attempts and last-error visibility.
- ACL- and CSRF-protected manual queue processing.
- Administrator Preferences screen with rule creation/update and restore-default action.
- Dashboard delivery-health counters.
- Core capabilities for preferences, delivery status and delivery channels.

### Architecture

- Notification content remains separate from channel delivery state.
- Email, PEC and push are not implemented inside the component; optional integrations register delivery adapters instead.
- Missing adapters leave deliveries safely pending for a later retry.
- No cross-component foreign keys or direct reads of private product tables were introduced.
- The 0.3.0 database update only adds tables and preserves existing notification data.

## 0.2.0 — 2026-09-08

### Added

- Persistent notification storage in `#__xdecaronotifications_items`.
- Idempotent public `NotificationService::create()` API using source-scoped `external_key` values.
- Stable source and recipient references without cross-component foreign keys.
- Read, archive and unread-count operations.
- Administrator dashboard counters and recent notifications.
- Searchable/filterable/paginated notification center.
- CSRF- and ACL-protected state actions.
- Core 1.2.0 `Capability`, `EntityReference` and `IntegrationEvent` adapter support.
- Deterministic installable component ZIP build and CI validation on PHP 7.4 and 8.3.

### Architecture

- Core remains optional.
- Notifications does not inspect private tables belonging to other xdecaro products.

## 0.1.0 — 2026-09-08

### Added

- Initial Joomla component scaffold.
- Core integration placeholder and shared-UI opt-in.
