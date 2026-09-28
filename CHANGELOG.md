# Changelog

All notable changes to **Notifications by xdecaro** are documented here.

## 1.1.12 — 2026-09-28

### Administrator bell unread queue

- Changed the administrator bell dropdown to show only the five latest **unread** notifications instead of mixing unread and already-read items.
- Marking one notification as read now removes it from the bell immediately and refills the five-row window with the next unread notification when one is available.
- Applied the unread-only query to both the initial PHP module render and every poll/read response, so the behavior remains stable after the 10-second live refresh.
- **Mark all as read** now leaves the bell empty while preserving every notification in the notification center/history.
- Added CI regression checks preventing read notifications from reappearing in the bell queue.
- No database structure or stored notification data format changes.

## 1.1.11 — 2026-09-28

### Administrator bell read actions

- Replaced the bell footer **Clear notifications** archive action with **Mark all as read**, so bell actions no longer remove notifications from the active history.
- Added a compact per-row check action on the right side of each unread notification to mark only that notification as read.
- Added subtle separators between notification rows and a reduced-emphasis treatment for already-read rows.
- Added authenticated, CSRF-protected `bell.markRead` and `bell.markAllRead` endpoints that scope mutations to the currently signed-in Joomla administrator through `NotificationService::markReadForRecipient()`.
- The unread badge and bell contents refresh immediately after single or bulk read actions and keep the existing 10-second polling behavior.
- Kept the old archive endpoint only for compatibility with pages that may still have older JavaScript loaded; the current bell UI no longer exposes it.
- Added CI regression checks for row separators, single-read controls, bulk mark-read behavior and non-destructive wording.
- No database structure or stored notification data format changes.

## 1.1.10 — 2026-09-28

### Administrator bell footer link contrast

- Fixed **Open notification center** remaining white-on-white in Joomla administrator Light Mode while **Clear notifications** was visible.
- The root cause was Atum's header link rule overriding the footer link color because it had higher selector specificity.
- Increased only the Notifications footer action selector specificity so both the link and button use Joomla's theme-aware `--body-color` without `!important` or fixed colors.
- Kept the existing Light/Dark footer background, separator and hover behavior unchanged.
- Added a CI regression guard requiring a footer-scoped action selector that outranks Atum's header link color rules.
- No notification logic, database structure or stored notification data changes.

## 1.1.9 — 2026-09-28

### Administrator bell footer theme contrast

- Fixed the notification footer actions becoming white-on-white in Joomla administrator Light Mode.
- The footer now uses Joomla Atum's theme-aware `--body-bg` and `--body-color` variables, so background, text and icons automatically follow Light/Dark mode.
- The footer separator now derives from `--body-color` instead of inherited header text color, preserving visible contrast in both themes.
- Hover and keyboard-focus states also derive from the active Joomla theme color instead of inheriting the status-bar color.
- Added CI regression checks that reject inherited footer text colors and require the theme-aware Joomla variables.
- No notification logic, database structure or stored notification data changes.

## 1.1.8 — 2026-09-27

### Administrator bell footer

- Removed the wide Bootstrap divider band below the notification list and replaced it with a single subtle separator derived from the current text color, so the line keeps appropriate contrast across light and dark administrator modes.
- Added a right-aligned **Clear notifications** action beside **Open notification center**.
- The clear action asks for explicit confirmation and submits a Joomla CSRF-protected POST request.
- Clearing the bell archives only unread/read notifications belonging to the currently authenticated Joomla administrator; notification rows are never physically deleted.
- The action archives in recipient-scoped batches and immediately refreshes the badge and dropdown to the empty state without reloading the administrator page.
- Added Italian and English labels plus CI regression checks for the adaptive separator, confirmation flow, CSRF protection and recipient-scoped archive behavior.
- No database structure or stored notification data format changes.

## 1.1.7 — 2026-09-27

### Administrator bell row layout

- Replaced the desktop three-column notification row with two columns: a fixed 120px title column and one flexible content column.
- Stacked the notification message and priority/state/date metadata inside the content column so metadata no longer steals horizontal space or overflows the 500px dropdown.
- Allowed metadata to wrap naturally and kept it left-aligned beneath the message.
- Applied the same two-column structure to the initial PHP render and the 10-second JavaScript live refresh.
- Preserved the narrow-screen fallback that stacks each notification vertically.
- Added CI regression checks preventing the old third metadata column and forced nowrap behavior from returning.
- No notification logic, database structure or stored notification data changes.

## 1.1.6 — 2026-09-27

### Administrator bell columns

- Reserved a fixed 120px first column for notification titles inside the 500px administrator bell dropdown.
- Kept message content flexible in the middle column and priority/state/date metadata aligned in a dedicated third column.
- Applied the same three-column layout to the 10-second JavaScript live refresh so the first column does not collapse after polling.
- Added a narrow-screen fallback that stacks each notification vertically and a dedicated module stylesheet installed with the administrator module.
- Added CI regression checks for the 120px desktop title column, live-rendered rows and mobile stacking behavior.
- No notification logic, database structure or stored notification data changes.

## 1.1.5 — 2026-09-27

### Administrator bell width

- Increased the administrator notification dropdown width to 500px on desktop so notification titles, messages and metadata wrap less aggressively.
- Added a viewport-safe maximum width of `calc(100vw - 24px)` so the dropdown remains usable on narrow screens.
- Added a CI regression guard for the dedicated dropdown class and the 500px responsive width contract.
- No notification logic, database structure or stored notification data changes.

## 1.1.4 — 2026-09-27

### Live administrator bell contrast

- Fixed the 10-second bell polling renderer so it no longer replaces readable light secondary text with Bootstrap `text-body-secondary` inside Atum's dark header dropdown.
- Live-rendered notification messages, metadata and the empty state now use the same white reduced-opacity treatment as the initial PHP render.
- Added a CI regression check covering both the initial layout and the JavaScript live renderer.
- No notification logic, database structure or stored notification data changes.

## 1.1.3 — 2026-09-27

### Administrator bell contrast

- Fixed low-contrast secondary text in the administrator notification dropdown when Joomla is in light mode.
- The root cause was the use of Bootstrap `text-body-secondary` inside Atum's intentionally dark header dropdown: in light mode the body-secondary color becomes dark while the dropdown remains dark.
- Replaced those secondary text classes with readable white text at reduced opacity, preserving the dark-mode appearance.
- Added a CI regression check preventing body-secondary colors from being reintroduced into the bell dropdown.
- No notification logic, database structure or stored notification data changes.

## 1.1.2 — 2026-09-27

### Administrator bell layout

- Fixed the administrator notification bell so the unread count no longer appears as a second stacked circle below the bell.
- Updated the module markup to follow Joomla's `header-item-icon` wrapper pattern.
- Rendered the unread count as a compact badge overlaid on the bell icon while keeping the label and dropdown control on one toolbar row.
- Added CI regression checks for the bell wrapper and overlaid badge markup.
- No notification table structure or stored notification data changes.

## 1.1.1 — 2026-09-15

### Joomla target

- Set Joomla 6.1.3 as the exclusive supported CMS target for Notifications.
- Removed Joomla 4.4.14 and Joomla 5.4.8 clean-install CI jobs and removed PHP 7.4 compatibility testing.
- Raised the package update-feed minimum runtime to PHP 8.3.0, matching Joomla 6.1.3's `^8.3.0` requirement.
- CI now validates PHP 8.3 and PHP 8.4 code/build compatibility and performs the full clean package-install test only on Joomla 6.1.3.
- Added a release/CI contract guard preventing accidental reintroduction of Joomla 4/5 targets.
- Added a non-destructive 1.1.1 component schema marker; no notification table or stored notification data changes.

## 1.1.0 — 2026-09-15

### Global administrator bell

- Added `mod_xdecaronotifications`, a Joomla administrator status-bar bell that shows the signed-in user's unread count and five latest active notifications on every backend page.
- Added a read-only authenticated bell.poll controller endpoint scoped to the authenticated administrator; callers cannot request another recipient.
- Added 10-second near-real-time polling with hidden-tab pause/resume and DOM-only badge/dropdown updates.
- The package provisions one published `status` module instance on first availability and marks it so later updates preserve administrator position/published choices.
- Added the module to deterministic package builds, SHA-256 assets, release uploads and Joomla 4.4.14 / 5.4.8 / 6.1.3 clean-install verification.
- Added a non-destructive 1.1.0 component schema marker; no notification table or stored notification data changes.

## 1.0.5 — 2026-09-14

### Live administrator refresh

- Added configurable automatic refresh for Dashboard, Notifications and Deliveries, defaulting to 10 seconds.
- Live refresh pauses while the administrator tab is hidden or while a list filter is being edited, avoiding needless requests and interrupted input.
- Added the browser refresh asset to the Joomla component media installation.
- Corrected the Information diagnostics Core check to use the canonical lowercase `xdecaro\Core` namespace.
- Replaced Joomla's placeholder-bearing `JACTIONS` table heading with a component-specific Actions label, fixing `Azioni per: %s` in Italian.
- Added a CI contract guard and a non-destructive 1.0.5 schema marker; no stored notification data changes.

## 1.0.4 — 2026-09-14

### Diagnostics

- Added an **Information → Diagnostics** button that creates a test notification for the currently signed-in Joomla administrator.
- The diagnostic test queues only the native `in_app` delivery channel and never sends an email.
- Protected the action with Joomla CSRF validation and the component `core.manage` permission.
- Added Italian and English administrator strings and a CI contract guard for the diagnostic flow.
- Added a non-destructive 1.0.4 schema marker; no database structure or stored notification data changes.

## 1.0.3 — 2026-09-13

### Joomla compatibility

- Fixed administrator list screens that called the undefined `ListModel::getApplication()` method.
- Updated Notifications, Deliveries and Preferences models to use Joomla's supported `Factory::getApplication()` access pattern.
- Added a CI regression guard so unsupported `ListModel` application access is rejected before release.
- Added a non-destructive 1.0.3 schema marker; no database structure or stored notification data changes.

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
- Exposed `CoreIntegrationService` from the booted component so other xdecaro products can discover capabilities without reading private tables.
- Kept Core optional: Notifications continues to work when Core or the 1.4 registry is unavailable.
- Added a non-destructive 1.0.1 component schema marker; no database structure or stored notification data changes.

## 1.0.0 — 2026-09-09

### Stable release

- Complete notification persistence and public publishing API with source-scoped idempotency.
- Recipient-scoped query API with validated filtering/pagination and scoped read/archive mutations.
- Read/unread/archive state, priorities, categories, actions and notification expiry.
- Recipient/category/channel preferences and public preference API.
- Concurrency-safe delivery queue with atomic claims, retry scheduling, maximum attempts, stale-claim recovery and immutable attempt history.
- Native in-app delivery and public delivery-channel contract.
- Automatic discovery of optional channel plugins through a Joomla event.
- Included automatic email delivery using Joomla mail configuration and Joomla user resolution.
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
