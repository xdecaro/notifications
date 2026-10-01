# Notifications Autonomous P0 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans and test-driven-development task-by-task.

**Goal:** Close the autonomous Notifications P0 items without adding a generic Core caller-identity contract.

**Architecture:** Preserve the existing component and queue. Make publication idempotent per source event and recipient, add explicit delivery outcomes without conflating transport acceptance with confirmed delivery, make stale-claim recovery provider-aware through an optional Notifications-specific idempotency marker, and forbid Joomla-user email redirection while retaining explicit email delivery for non-user recipients.

**Tech Stack:** Joomla 6.1.3, PHP 8.3+, MySQL 8, Joomla Scheduled Tasks.

**Spec:** User-authorized P0-A brief in the Notifications architecture conversation, 2026-10-01.

## Global Constraints

- Base: xdecaro Notifications 1.1.12.
- Release remains NO-GO.
- Do not modify xdecaro Core.
- Do not add Forms/Documents/Membership/Studio integrations.
- Do not add Redis/RabbitMQ or rewrite the queue.
- RED -> GREEN for every behavior change.
- Runtime on official.lucadecaro.it is explicitly outside this implementation pass.

## Review Focus

- Existing external_key rows remain valid when the uniqueness scope expands to include recipient_type + recipient_id.
- Same source event for two recipients creates two notifications while duplicate publication for one recipient remains idempotent.
- Non-idempotent stale processing never becomes an automatic retry.
- Provider-safe idempotent stale processing retries with the same deterministic idempotency key.
- A Joomla user recipient cannot be redirected through context[email].

---

### Task 1: Regression contract and RED tests

**Files:**
- Create: `build/test-p0-autonomous.php`
- Create: `build/validate-p0-autonomous.sh`
- Modify: `.github/workflows/ci.yml`

Write executable contract tests first for recipient-scoped external keys, delivery result statuses, provider idempotency marker, email override policy, schema migration, queue/retry/outcome_unknown behavior, scheduler and preference-regression invariants. Run CI on the test-only commit and require the expected RED failures before production changes.

### Task 2: Recipient-scoped idempotency and schema migration

**Files:**
- Modify: `component/admin/src/Service/NotificationService.php`
- Modify: `component/admin/sql/install.mysql.utf8mb4.sql`
- Create: `component/admin/sql/updates/mysql/1.1.13.sql`

Change lookup and uniqueness semantics to `(source_component, external_key, recipient_type, recipient_id)`. The update is data-preserving: the legacy unique key is stricter than the new key, so every valid 1.1.12 row already satisfies the new uniqueness rule. Do not rewrite rows.

### Task 3: Explicit delivery outcomes and provider-aware recovery

**Files:**
- Modify: `component/admin/src/Service/DeliveryResult.php`
- Create: `component/admin/src/Service/IdempotentDeliveryChannelInterface.php`
- Modify: `component/admin/src/Service/InAppChannel.php`
- Modify: `component/admin/src/Service/DeliveryService.php`

Add explicit `submitted`, `delivered`, `failed`, and `outcome_unknown` results. Preserve operational queue states but expose semantic delivery status. Stale claims for idempotent providers may retry with a deterministic key; stale claims for other providers become terminal `outcome_unknown` instead of blind retry.

### Task 4: Email override protection and transport semantics

**Files:**
- Modify: `plugins/xdecaronotifications/email/src/Extension/Email.php`

For recipient_type `user`, resolve the canonical Joomla user address and reject a differing context email. For non-user recipients, a valid explicit context email remains supported. Successful Joomla mail transport becomes `submitted`, not `delivered`; indeterminate send errors become `outcome_unknown`.

### Task 5: Administration/status compatibility and documentation

**Files:**
- Modify: `component/admin/src/Model/DeliveriesModel.php`
- Modify: administrator delivery template/language only where required by existing rendering.
- Modify: `README.md`

Keep legacy queue state visibility while adding the new final states and document mapping: pending/processing/retry -> REQUEST_ACCEPTED, submitted -> SUBMITTED, delivered -> DELIVERED, failed -> FAILED, outcome_unknown -> OUTCOME_UNKNOWN. Keep worker/maintenance methods documented as internal operational APIs.

### Task 6: GREEN verification

Run the P0 PHP test, validation script, PHP lint, XML parse, build/package smoke, schema/migration checks and CI. Inspect the branch diff against main. Do not bump manifests/VERSION, create ZIP artifacts for handoff, tag, merge or release. Propose 1.1.13 only after all automatic checks are green.