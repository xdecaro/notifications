# Changelog

All notable changes to **Notifications by xdecaro** are documented here.

## 0.2.0 — 2026-09-08

### Added
- Persistent notification, recipient, preference and delivery-status storage.
- Idempotent publication by source component and external key, including concurrent retry recovery.
- Joomla User notification center with read, unread and archive actions protected by CSRF tokens.
- Generic entity recipients for optional People/Organizations-style integrations without mandatory dependencies.
- Scheduling and expiry visibility for notifications created by source products.
- Administrator dashboard, notification list/detail and standard Information page.
- Optional Core 1.2.0 integration using Capability, EntityReference, IntegrationEvent and shared UI assets.
- Deterministic component/package ZIP builds, CI, update feed, changelog feed and release workflow.

### Architecture
- Technical identity is `com_xdecaronotifications`, namespace `Xdecaro\\Component\\Notifications`, package `pkg_xdecaronotifications`, database namespace `#__xdecaronotifications_*`.
- The only active delivery channel in 0.2.0 is the internal Joomla notification center.
- Email, PEC and official human-authored communications remain owned by Communications.
- Source-domain rules remain in the originating product; Notifications does not inspect other products' private tables.
- Generic entity recipients remain queued until a real resolver/channel adapter exists; they are not falsely marked delivered.

### Validation
- Static CI validates PHP 7.4 and PHP 8.3, XML, SQL boundaries and deterministic ZIP integrity.
- Joomla 4/5/6 remain target platforms; runtime installation validation is still required before a stable release designation.

## 0.1.0 — 2026-09-08

### Added
- Initial Joomla scaffold and optional Core integration.
