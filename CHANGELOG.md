# Changelog

All notable changes to Notifications by xdecaro are documented here.

## 0.2.0 — 2026-09-08

### Added
- Persistent notifications, recipients, preferences and delivery-status tables.
- Idempotent publication through source component + external key.
- Optional Core 1.2 integration through Capability, EntityReference and IntegrationEvent.
- Joomla-user notification center with read/unread/archive actions protected by CSRF tokens.
- Generic entity recipients for future People/Organizations integrations without mandatory dependencies.
- Administrator dashboard, notification list, detail view and standard Information page.
- Deterministic Joomla component/package build, CI and release/update feed.

### Architecture
- Internal notifications are the only active delivery channel in 0.2.0.
- Email, PEC and official communication delivery remain owned by Communications.
- Source-domain business rules remain in the originating component.
- No direct reads from another xdecaro component's private tables.

## 0.1.0 — 2026-09-08

### Added
- Initial Joomla scaffold and optional Core capability integration.
