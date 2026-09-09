# Changelog

All notable changes to **Notifications by xdecaro** are documented here.

## 1.0.2 — 2026-09-09

### Integration

- Corrected the five administrator views that still imported `Xdecaro\Core\Asset\AssetService`; all runtime Core consumption now uses canonical `xdecaro\Core`.
- Added a local CI/release guard against future runtime use of the deprecated Core compatibility namespace.
- Added a non-destructive 1.0.2 schema marker; no database structure or stored notification data changes.
- Preserved notification publishing, preferences, delivery queue, scheduled workers and channel behavior.

## 1.0.1 — 2026-09-09

### Integration

- Migrated new Core references to the canonical lowercase `xdecaro\Core` namespace.
- Added optional Core 1.4 `CapabilityRegistry` registration for all public Notifications capabilities.
- Exposed `CoreIntegrationService` from the booted Notifications component so other xdecaro products can discover capabilities without reading private tables.
- Kept Core optional: Notifications continues to work when Core or the 1.4 registry is unavailable.
- Added a non-destructive 1.0.1 schema marker; no database structure or stored notification data changes.

## 1.0.0 — 2026-09-09

### Stable release

- Complete notification persistence and public publishing API with source-scoped idempotency.
- Recipient-scoped query API with validated filtering/pagination and scoped read/archive mutations.
- Read/unread/archive state, priorities, categories, actions and notification expiry.
- Recipient/category/channel preferences and public preference API.
- Concurrency-safe delivery queue with atomic claims, retry scheduling, maximum attempts, stale-claim recovery and immutable attempt history.
- Native `in_app` delivery and public delivery-channel contract.
- Automatic discovery of optional channel plugins through a Joomla event.
- Included automatic email channel using Joomla mail configuration and Joomla user resolution.
- Included Joomla Scheduled Tasks routines for delivery processing and maintenance.
- Maintenance for expired Notifications records and configurable attempt-history retention.
- Administrator Dashboard, Notifications, Deliveries, Preferences and standard Information/Diagnostics screens.
- Optional Xdecaro Core capability/event/entity-reference integration with safe fallback, including `notifications.query`.
- Installable package containing component, task plugin and email plugin; plugin enablement choices are preserved on later package updates.
- Joomla update server, changelog feed, deterministic ZIP builds, release SHA-256 verification and GitHub release automation.
- Clean-install CI coverage for Joomla 4.4.14, 5.4.8 and 6.1.3.

### Architecture

- No cross-component foreign keys or reads of private product tables.
- Recipient references never replace caller-side ACL/identity authorization.
- Communications remains responsible for PEC and official/manual communications.
- Source components remain responsible for business-rule detection such as document, membership or payment expiries.
- Browser push and digest policies remain optional future adapters/services rather than mandatory core dependencies.

## 0.3.0 — 2026-09-09

- Added recipient preferences, delivery queue, delivery attempts, retry lifecycle, native in-app channel and administration delivery/preference screens.

## 0.2.0 — 2026-09-08

- Added persistent notifications, public service API, dashboard, searchable notification center, secure state actions and deterministic build CI.

## 0.1.0 — 2026-09-08

- Initial Joomla component scaffold and optional Core integration placeholder.
