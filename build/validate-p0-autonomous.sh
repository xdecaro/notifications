#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"

php "$ROOT/build/test-p0-autonomous.php"

NOTIFICATIONS="$ROOT/component/admin/src/Service/NotificationService.php"
DELIVERY="$ROOT/component/admin/src/Service/DeliveryService.php"
IN_APP="$ROOT/component/admin/src/Service/InAppChannel.php"
EMAIL="$ROOT/plugins/xdecaronotifications/email/src/Extension/Email.php"
INSTALL_SQL="$ROOT/component/admin/sql/install.mysql.utf8mb4.sql"
MIGRATION="$ROOT/component/admin/sql/updates/mysql/1.1.13.sql"
DELIVERIES_MODEL="$ROOT/component/admin/src/Model/DeliveriesModel.php"
TASK="$ROOT/plugins/task/xdecaronotifications/src/Extension/Notifications.php"
PREFERENCES="$ROOT/component/admin/src/Service/PreferenceService.php"

# Publication idempotency is source event + recipient.
grep -Fq 'findByExternalKey($sourceComponent, $externalKey, $recipientType, $recipientId)' "$NOTIFICATIONS"
grep -Fq '`source_component`, `external_key`, `recipient_type`, `recipient_id`' "$INSTALL_SQL"

test -f "$MIGRATION"
grep -Fq 'idx_notifications_external_recipient' "$MIGRATION"
grep -Fq 'idx_notifications_external' "$MIGRATION"

# Unknown provider outcomes are explicit and not silently treated as delivery.
grep -Fq "'outcome_unknown'" "$DELIVERY"
grep -Fq 'IdempotentDeliveryChannelInterface' "$DELIVERY"
grep -Fq "'idempotency_key'" "$DELIVERY"
grep -Fq "'delivery_status'" "$DELIVERY"

# In-app is intrinsically idempotent; email transport acceptance is only submitted.
grep -Fq 'IdempotentDeliveryChannelInterface' "$IN_APP"
grep -Fq 'DeliveryResult::submitted(' "$EMAIL"
if grep -Fq "DeliveryResult::delivered('joomla-mail')" "$EMAIL"; then
  echo 'Email still maps Joomla transport acceptance to DELIVERED.' >&2
  exit 1
fi

grep -Fq 'recipient_email_override_forbidden' "$EMAIL"

# New terminal states remain visible to administrator filters.
grep -Fq "'submitted'" "$DELIVERIES_MODEL"
grep -Fq "'outcome_unknown'" "$DELIVERIES_MODEL"

# Scheduler and preference filtering remain present and are not bypassed.
grep -Fq "'xdecaronotifications.queue'" "$TASK"
grep -Fq 'processPending(' "$TASK"
grep -Fq 'filterEnabledChannels(' "$ROOT/component/admin/src/Service/DeliveryService.php"
grep -Fq 'function filterEnabledChannels' "$PREFERENCES"

echo 'Notifications autonomous P0 validation: PASS'
