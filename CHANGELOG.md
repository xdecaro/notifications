# Changelog

All notable changes to **Notifications by xdecaro** are documented here.

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
- Preferences, delivery channels, retries and scheduled scanning remain intentionally outside the advertised 0.2.0 capability set.

## 0.1.0 — 2026-09-08

### Added

- Initial Joomla component scaffold.
- Core integration placeholder and shared-UI opt-in.
