# Notifications — Repository Guidelines

## Scope

Notifications by xdecaro is the shared notification center for the xdecaro Joomla ecosystem. It owns notification persistence, recipients, preferences, read/unread state, priorities, delivery channels, delivery attempts, retries and notification history.

Source-domain rules remain in the source component. Notifications must not duplicate Competition, Finance, Documents, Membership, Courses, Events, Bookings or other product business logic.

## Core integration

Use Xdecaro Core only through documented public APIs. Prefer `EntityReference`, `Capability`, `IntegrationEvent` and shared UI assets when available. Core integration must remain optional and degrade safely when Core is missing or too old.

Public capabilities include `notifications.publish`, `notifications.state`, `notifications.unread_count`, `notifications.preferences`, `notifications.delivery_status` and `notifications.delivery_channels`. Do not advertise a capability before the corresponding implementation and safe fallback exist.

A source event may result in a notification, but Notifications owns the resulting notification record and delivery lifecycle. Never use an entity reference as proof of authorization.

Delivery adapters must use the public channel contract and must not write Notifications tables directly. Communications remains the owner of official/manual communications and PEC workflows; Notifications must not become a second Communications component.

## Joomla

Target Joomla 4, 5 and 6 where technically possible. Use namespaces, MVC, service providers, DI, ACL, CSRF protection, filtered input, escaped output, Language API and Web Asset Manager. Use `#__` for tables and preserve data on updates.

## UI

Use Core shared UI assets when available. Keep responsive, accessible, light/dark compatible administrator screens with Joomla toolbar conventions.

## Working rule

When the user says `procedi`, execute directly after inspecting current code and dependencies. Preserve working behavior and avoid unnecessary refactors.
