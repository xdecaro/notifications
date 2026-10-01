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
INSTALLER="$ROOT/component/script.php"
MANIFEST="$ROOT/component/xdecaronotifications.xml"
DELIVERIES_MODEL="$ROOT/component/admin/src/Model/DeliveriesModel.php"
DELIVERIES_TMPL="$ROOT/component/admin/tmpl/deliveries/default.php"
TASK="$ROOT/plugins/task/xdecaronotifications/src/Extension/Notifications.php"
PREFERENCES="$ROOT/component/admin/src/Service/PreferenceService.php"

# Publication idempotency is source event + recipient.
grep -Fq 'findByExternalKey($sourceComponent, $externalKey, $recipientType, $recipientId)' "$NOTIFICATIONS"
grep -Fq '`source_component`, `external_key`, `recipient_type`, `recipient_id`' "$INSTALL_SQL"

test -f "$MIGRATION"
test -f "$INSTALLER"
grep -Fq 'idx_notifications_external_recipient' "$MIGRATION"
grep -Fq 'idx_notifications_external' "$MIGRATION"
grep -Fq '<scriptfile>script.php</scriptfile>' "$MANIFEST"
grep -Fq 'ADD UNIQUE KEY' "$INSTALLER"
grep -Fq 'DROP INDEX' "$INSTALLER"

# Migration is data-preserving: add broader protection first, then remove legacy.
add_line="$(grep -n 'ADD UNIQUE KEY' "$INSTALLER" | head -n1 | cut -d: -f1)"
drop_line="$(grep -n 'DROP INDEX' "$INSTALLER" | head -n1 | cut -d: -f1)"
test -n "$add_line" && test -n "$drop_line" && test "$add_line" -lt "$drop_line"
if grep -Eq '\b(DELETE|TRUNCATE|REPLACE)[[:space:]]' "$INSTALLER" "$MIGRATION"; then
  echo 'External-key migration contains a destructive data operation.' >&2
  exit 1
fi

# Unknown provider outcomes are explicit and not silently treated as delivery.
grep -Fq "'outcome_unknown'" "$DELIVERY"
grep -Fq 'IdempotentDeliveryChannelInterface' "$DELIVERY"
grep -Fq "'idempotency_key'" "$DELIVERY"
grep -Fq "'delivery_status'" "$DELIVERY"
grep -Fq 'stale_claim_outcome_unknown' "$DELIVERY"
grep -Fq 'DeliverySemantics::canSafelyRetryUnknown($channel)' "$DELIVERY"

# In-app is intrinsically idempotent; email transport acceptance is only submitted.
grep -Fq 'IdempotentDeliveryChannelInterface' "$IN_APP"
grep -Fq 'DeliveryResult::submitted(' "$EMAIL"
if grep -Fq "DeliveryResult::delivered('joomla-mail')" "$EMAIL"; then
  echo 'Email still maps Joomla transport acceptance to DELIVERED.' >&2
  exit 1
fi

grep -Fq 'recipient_email_override_forbidden' "$EMAIL"
grep -Fq "unset(\$context['idempotency_key'])" "$DELIVERY"

# New terminal states remain visible to administrator filters and UI.
grep -Fq "'submitted'" "$DELIVERIES_MODEL"
grep -Fq "'outcome_unknown'" "$DELIVERIES_MODEL"
grep -Fq "'submitted'" "$DELIVERIES_TMPL"
grep -Fq "'outcome_unknown'" "$DELIVERIES_TMPL"

# Scheduler and preference filtering remain present and are not bypassed.
grep -Fq "'xdecaronotifications.queue'" "$TASK"
grep -Fq 'processPending(' "$TASK"
grep -Fq "\$stats['submitted']" "$TASK"
grep -Fq "\$stats['outcome_unknown']" "$TASK"
grep -Fq 'filterEnabledChannels(' "$DELIVERY"
grep -Fq 'function filterEnabledChannels' "$PREFERENCES"

echo 'Notifications autonomous P0 validation: PASS'
