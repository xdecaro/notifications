# Administrator Notification Bell Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship Notifications 1.1.0 with a global live administrator bell showing the signed-in user's unread count and latest notifications.

**Architecture:** Add a Joomla administrator status module backed by the existing `NotificationService`, plus a read-only current-user JSON poll controller. Package installation creates one idempotent `status` module instance; JavaScript polls every 10 seconds and updates the module only.

**Tech Stack:** PHP 7.4+, Joomla 4.4/5.4/6.1 MVC/module APIs, Bootstrap dropdown markup, vanilla JavaScript, MySQL/MariaDB, GitHub Actions.

**Spec:** `docs/superpowers/specs/2026-09-15-admin-notification-bell-design.md`

## Global Constraints

- Keep Notifications as persistence/query owner; the module must not query Notifications tables directly.
- Endpoint is administrator-only, read-only, and derives the recipient from the logged-in Joomla identity.
- Poll every 10 seconds and pause while `document.hidden` is true.
- Preserve Joomla 4.4, 5.4 and 6.1 compatibility.
- Package updates must not overwrite an existing module instance's published state or administrator choices.
- Release version is exactly 1.1.0.

---

### Task 1: Contract test

**Files:**
- Create: `build/validate-admin-bell.sh`
- Modify: `.github/workflows/ci.yml`

**Interfaces:**
- Consumes: repository/package file layout.
- Produces: a CI gate that fails until the bell module, endpoint, packaging, and installer contract exist.

- [ ] Write the contract script asserting required module/controller/package/installer/polling markers.
- [ ] Add it to the PHP matrix before the general build step.
- [ ] Push and confirm CI fails because the administrator bell implementation is missing.

### Task 2: Current-user poll endpoint

**Files:**
- Create: `component/admin/src/Controller/BellController.php`

**Interfaces:**
- Consumes: `NotificationService::getUnreadCount('user', $userId)` and `NotificationService::getForRecipient('user', $userId, ['limit' => 5])`.
- Produces: task `bell.poll`, JSON `{success,data:{unread,items}}` for the authenticated administrator.

- [ ] Implement identity/administrator authorization before reading data.
- [ ] Return only the documented safe notification fields.
- [ ] Close the Joomla application after emitting `JsonResponse`.

### Task 3: Administrator bell module

**Files:**
- Create: `modules/admin/xdecaronotifications/mod_xdecaronotifications.php`
- Create: `modules/admin/xdecaronotifications/mod_xdecaronotifications.xml`
- Create: `modules/admin/xdecaronotifications/tmpl/default.php`
- Create: `modules/admin/xdecaronotifications/media/js/admin-bell.js`
- Create: `modules/admin/xdecaronotifications/language/en-GB/mod_xdecaronotifications.ini`
- Create: `modules/admin/xdecaronotifications/language/en-GB/mod_xdecaronotifications.sys.ini`
- Create: `modules/admin/xdecaronotifications/language/it-IT/mod_xdecaronotifications.ini`
- Create: `modules/admin/xdecaronotifications/language/it-IT/mod_xdecaronotifications.sys.ini`

**Interfaces:**
- Consumes: Notifications component boot service and `bell.poll` endpoint.
- Produces: a `status`-position Bootstrap dropdown with unread badge and up to five latest notification rows.

- [ ] Render initial current-user data through `NotificationService`, degrading to an empty bell if the component is unavailable.
- [ ] Render accessible bell/dropdown markup using escaped text and safe routed links.
- [ ] Poll every 10 seconds, pause on hidden tabs, update count/list safely with DOM APIs, and resume immediately on visibility return.

### Task 4: Package installation and build

**Files:**
- Modify: `package/pkg_xdecaronotifications/pkg_xdecaronotifications.xml`
- Modify: `package/pkg_xdecaronotifications/script.php`
- Modify: `build/build.sh`
- Modify: `.github/workflows/ci.yml`

**Interfaces:**
- Consumes: `modules/admin/xdecaronotifications` source directory.
- Produces: `mod_xdecaronotifications_1.1.0.zip` inside the canonical package and one administrator module instance in position `status` assigned to all pages.

- [ ] Add the module child extension to the package manifest/build.
- [ ] In postflight, create the module instance only when none exists; never overwrite an existing instance on update.
- [ ] Extend clean-install CI to verify module extension, instance, status position and all-pages assignment on Joomla 4/5/6.

### Task 5: Version, documentation and release

**Files:**
- Modify: `VERSION`, component/plugin/package/module manifests, `CHANGELOG.md`, `updates/changelog.xml`, `updates/pkg_xdecaronotifications.xml`, and any schema marker required by repository convention.

**Interfaces:**
- Produces: installable stable Notifications 1.1.0 release.

- [ ] Bump every distributable manifest/feed reference to 1.1.0 without changing database structures.
- [ ] Run contract/build/install CI and deterministic package checks.
- [ ] Open PR, review diff, merge only after green CI, then verify release workflow and published package checksum.
