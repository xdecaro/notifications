# Notifications — Repository Guidelines

## Scope

Notifications by xdecaro is the shared automatic notification center for the xdecaro Joomla ecosystem. Stable 1.0 owns notification persistence, recipients, preferences, read/unread state, priorities, delivery channels, queue state, delivery attempts, retries, expiry of notification records and notification history.

Source-domain rules remain in the source component. Notifications must not duplicate Competitions, Finance, Documents, Membership, Courses, Events, Bookings or other product business logic.

Communications remains the owner of official/manual communications, PEC, protocol-oriented communications and richer communication workflows. Notifications email is only an automatic delivery channel.

## Core integration

Use Xdecaro Core only through documented public APIs. Prefer `EntityReference`, `Capability`, `IntegrationEvent` and shared UI assets when available. Core integration must remain optional and degrade safely when Core is missing or too old.

Public capabilities are `notifications.publish`, `notifications.query`, `notifications.state`, `notifications.unread_count`, `notifications.preferences`, `notifications.delivery_status` and `notifications.delivery_channels`. Never advertise a capability before the corresponding implementation and safe fallback exist.

An entity reference is an integration pointer, never proof of authorization. Recipient-scoped query/state methods reduce accidental cross-recipient access but callers must still enforce Joomla ACL and identity ownership before invoking them.

## Delivery architecture

Delivery adapters implement the public channel contract and never write Notifications tables. Register optional channels through `onXdecaroNotificationsRegisterChannels`.

`DeliveryService` exclusively owns delivery persistence, atomic claims, attempts, retries and final status. Preserve idempotency for notification publication and per-notification/channel queue entries.

Use Joomla Scheduled Tasks for recurring queue and maintenance work. Do not create scheduler task rows directly or assume a private Scheduler database schema.

## Joomla and security

Target Joomla 4, 5 and 6 where technically possible. Use namespaces, MVC, service providers, DI, ACL, CSRF protection, filtered input, escaped output, Language API and Web Asset Manager. Use `#__` for tables and preserve data on updates.

All administrator state-changing endpoints require server-side ACL and CSRF checks. Never use client-side state as authorization.

## Database and updates

Reserved database namespace: `#__xdecaronotifications_*`. Do not rename existing tables or destroy notification/preferences/delivery history in an update. New updates must be additive or safely migrated.

## Distribution

Stable distribution is `pkg_xdecaronotifications`. It contains the component, Scheduled Tasks plugin and automatic email channel plugin. Every release must update VERSION, manifests, package manifest, update feed, changelog, tag/release and installable ZIPs. Never publish different files with the same version. Package updates must preserve administrator plugin-enabled/disabled choices.

Clean-install CI must validate the installable package on supported Joomla majors, not only PHP syntax or ZIP structure.

## UI

Use Core shared UI assets when available. Keep administrator screens responsive, accessible and light/dark compatible with Joomla toolbar conventions. The Information page follows the ecosystem standard: Product + Environment; Included extensions + Updates; Connected components full width; Diagnostics full width.

## Working rule

When the user says `procedi`, execute directly after inspecting current code and dependencies. Preserve working behavior and avoid unnecessary refactors.
