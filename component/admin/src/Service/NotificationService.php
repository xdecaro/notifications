<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;
use Throwable;
use Xdecaro\Component\Notifications\Administrator\Value\RecipientReference;

final class NotificationService
{
    private DatabaseInterface $db;
    private PreferenceService $preferences;

    public function __construct(DatabaseInterface $db, PreferenceService $preferences)
    {
        $this->db = $db;
        $this->preferences = $preferences;
    }

    /**
     * Persist an internal notification and attach recipients.
     *
     * `external_key` is an idempotency key scoped to `source_component`.
     * Retrying the same source/key reuses the existing notification.
     *
     * @param array<string,mixed> $data
     * @param array<int,RecipientReference|array<string,mixed>> $recipients
     */
    public function publish(array $data, array $recipients): int
    {
        if (!array_key_exists('published', $data)) {
            $data['published'] = 1;
        }

        $notification = $this->normalizeNotification($data);
        $recipientReferences = $this->normalizeRecipients($recipients);

        if ($recipientReferences === []) {
            throw new InvalidArgumentException('At least one valid notification recipient is required.');
        }

        if ($notification['external_key'] !== null) {
            $existing = $this->findExistingByExternalKey(
                $notification['source_component'],
                $notification['external_key']
            );

            if ($existing !== null) {
                $this->ensureRecipients((int) $existing->id, (string) $existing->category, $recipientReferences);
                return (int) $existing->id;
            }
        }

        $this->db->transactionStart();

        try {
            $row = (object) $notification;
            $this->db->insertObject('#__xdecaronotifications_notifications', $row, 'id');
            $notificationId = (int) ($row->id ?? 0);

            if ($notificationId <= 0) {
                throw new RuntimeException('Notification insert did not return a valid ID.');
            }

            $this->ensureRecipients($notificationId, $notification['category'], $recipientReferences);
            $this->db->transactionCommit();

            return $notificationId;
        } catch (Throwable $exception) {
            $this->db->transactionRollback();

            // Another request may have won the unique source/external-key race.
            if ($notification['external_key'] !== null) {
                $existing = $this->findExistingByExternalKey(
                    $notification['source_component'],
                    $notification['external_key']
                );

                if ($existing !== null) {
                    $this->ensureRecipients((int) $existing->id, (string) $existing->category, $recipientReferences);
                    return (int) $existing->id;
                }
            }

            throw $exception;
        }
    }

    /**
     * Persist a Core IntegrationEvent-like object without making Core mandatory.
     *
     * @param object $event
     * @param array<int,RecipientReference|array<string,mixed>> $recipients
     * @param array<string,mixed> $presentation
     */
    public function publishIntegrationEvent($event, array $recipients, array $presentation): int
    {
        foreach (['getName', 'getVersion', 'getSource', 'getPayload', 'getOccurredAt'] as $method) {
            if (!is_object($event) || !method_exists($event, $method)) {
                throw new InvalidArgumentException('Unsupported integration event object.');
            }
        }

        $source = $event->getSource();
        $sourceComponent = isset($presentation['source_component'])
            ? trim((string) $presentation['source_component'])
            : '';
        $sourceEntity = null;
        $sourceEntityId = null;

        if (
            is_object($source)
            && method_exists($source, 'getComponent')
            && method_exists($source, 'getEntity')
            && method_exists($source, 'getId')
        ) {
            $sourceComponent = (string) $source->getComponent();
            $sourceEntity = (string) $source->getEntity();
            $sourceEntityId = (string) $source->getId();
        }

        if ($sourceComponent === '') {
            throw new InvalidArgumentException('source_component is required when an integration event has no entity source.');
        }

        $payload = (array) $event->getPayload();
        $externalKey = isset($presentation['external_key'])
            ? trim((string) $presentation['external_key'])
            : '';

        if ($externalKey === '' && isset($payload['external_key'])) {
            $externalKey = trim((string) $payload['external_key']);
        }

        $eventName = (string) $event->getName();

        return $this->publish([
            'source_component' => $sourceComponent,
            'source_entity' => $sourceEntity,
            'source_entity_id' => $sourceEntityId,
            'event_name' => $eventName,
            'event_version' => (string) $event->getVersion(),
            'external_key' => $externalKey === '' ? null : $externalKey,
            'category' => isset($presentation['category'])
                ? (string) $presentation['category']
                : str_replace('.', '_', $eventName),
            'priority' => isset($presentation['priority']) ? (string) $presentation['priority'] : 'normal',
            'title' => isset($presentation['title']) ? (string) $presentation['title'] : $eventName,
            'message' => isset($presentation['message']) ? (string) $presentation['message'] : '',
            'action_url' => $presentation['action_url'] ?? null,
            'action_label' => $presentation['action_label'] ?? null,
            'scheduled_at' => $presentation['scheduled_at'] ?? null,
            'expires_at' => $presentation['expires_at'] ?? null,
            'published' => $presentation['published'] ?? 1,
            'params' => [
                'event_payload' => $payload,
                'event_occurred_at' => $event->getOccurredAt(),
            ],
        ], $recipients);
    }

    /** @return array<int,object> */
    public function getForUser(int $userId, int $limit = 50, int $offset = 0, bool $includeArchived = false): array
    {
        if ($userId <= 0) {
            return [];
        }

        $now = $this->now();
        $query = $this->db->getQuery(true)
            ->select([
                'n.*',
                $this->db->quoteName('r.read_at'),
                $this->db->quoteName('r.archived_at'),
                $this->db->quoteName('r.id', 'recipient_row_id'),
            ])
            ->from($this->db->quoteName('#__xdecaronotifications_notifications', 'n'))
            ->innerJoin($this->db->quoteName('#__xdecaronotifications_recipients', 'r') . ' ON r.notification_id = n.id')
            ->where('r.user_id = :user_id')
            ->where('n.published = 1')
            ->where('(n.scheduled_at IS NULL OR n.scheduled_at <= :now_schedule)')
            ->where('(n.expires_at IS NULL OR n.expires_at > :now_expiry)')
            ->order('COALESCE(n.scheduled_at, n.created) DESC, n.id DESC')
            ->bind(':user_id', $userId, ParameterType::INTEGER)
            ->bind(':now_schedule', $now, ParameterType::STRING)
            ->bind(':now_expiry', $now, ParameterType::STRING);

        if (!$includeArchived) {
            $query->where('r.archived_at IS NULL');
        }

        $this->db->setQuery($query, max(0, $offset), max(1, min(200, $limit)));
        return (array) $this->db->loadObjectList();
    }

    public function countUnreadForUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $now = $this->now();
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__xdecaronotifications_recipients', 'r'))
            ->innerJoin($this->db->quoteName('#__xdecaronotifications_notifications', 'n') . ' ON n.id = r.notification_id')
            ->where('r.user_id = :user_id')
            ->where('r.read_at IS NULL')
            ->where('r.archived_at IS NULL')
            ->where('n.published = 1')
            ->where('(n.scheduled_at IS NULL OR n.scheduled_at <= :now_schedule)')
            ->where('(n.expires_at IS NULL OR n.expires_at > :now_expiry)')
            ->bind(':user_id', $userId, ParameterType::INTEGER)
            ->bind(':now_schedule', $now, ParameterType::STRING)
            ->bind(':now_expiry', $now, ParameterType::STRING);
        $this->db->setQuery($query);
        return (int) $this->db->loadResult();
    }

    public function markReadForUser(int $notificationId, int $userId, bool $read = true): bool
    {
        return $this->updateRecipientState(
            $notificationId,
            $userId,
            'read_at',
            $read ? $this->now() : null
        );
    }

    public function archiveForUser(int $notificationId, int $userId, bool $archived = true): bool
    {
        return $this->updateRecipientState(
            $notificationId,
            $userId,
            'archived_at',
            $archived ? $this->now() : null
        );
    }

    /** @return array<string,int> */
    public function getAdminStats(): array
    {
        $now = $this->now();
        $query = $this->db->getQuery(true)
            ->select([
                'COUNT(*) AS total',
                'SUM(CASE WHEN published = 1 AND (scheduled_at IS NULL OR scheduled_at <= :active_1) AND (expires_at IS NULL OR expires_at > :active_2) THEN 1 ELSE 0 END) AS active',
                'SUM(CASE WHEN published = 1 AND scheduled_at > :scheduled THEN 1 ELSE 0 END) AS scheduled',
                'SUM(CASE WHEN expires_at IS NOT NULL AND expires_at <= :expired THEN 1 ELSE 0 END) AS expired',
            ])
            ->from($this->db->quoteName('#__xdecaronotifications_notifications'))
            ->bind(':active_1', $now, ParameterType::STRING)
            ->bind(':active_2', $now, ParameterType::STRING)
            ->bind(':scheduled', $now, ParameterType::STRING)
            ->bind(':expired', $now, ParameterType::STRING);
        $this->db->setQuery($query);
        $row = $this->db->loadAssoc() ?: [];

        $unreadQuery = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__xdecaronotifications_recipients'))
            ->where('user_id IS NOT NULL')
            ->where('read_at IS NULL')
            ->where('archived_at IS NULL');
        $this->db->setQuery($unreadQuery);

        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'scheduled' => (int) ($row['scheduled'] ?? 0),
            'expired' => (int) ($row['expired'] ?? 0),
            'unread' => (int) $this->db->loadResult(),
        ];
    }

    /** @return array<int,object> */
    public function getAdminRecent(int $limit = 20): array
    {
        $recipientTable = $this->db->quoteName('#__xdecaronotifications_recipients');
        $query = $this->db->getQuery(true)
            ->select([
                'n.*',
                '(SELECT COUNT(*) FROM ' . $recipientTable . ' AS rc WHERE rc.notification_id = n.id) AS recipient_count',
                '(SELECT COUNT(*) FROM ' . $recipientTable . ' AS rr WHERE rr.notification_id = n.id AND rr.read_at IS NOT NULL) AS read_count',
            ])
            ->from($this->db->quoteName('#__xdecaronotifications_notifications', 'n'))
            ->order('n.created DESC, n.id DESC');
        $this->db->setQuery($query, 0, max(1, min(100, $limit)));
        return (array) $this->db->loadObjectList();
    }

    public function getById(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $recipientTable = $this->db->quoteName('#__xdecaronotifications_recipients');
        $query = $this->db->getQuery(true)
            ->select([
                'n.*',
                '(SELECT COUNT(*) FROM ' . $recipientTable . ' AS rc WHERE rc.notification_id = n.id) AS recipient_count',
                '(SELECT COUNT(*) FROM ' . $recipientTable . ' AS rr WHERE rr.notification_id = n.id AND rr.read_at IS NOT NULL) AS read_count',
            ])
            ->from($this->db->quoteName('#__xdecaronotifications_notifications', 'n'))
            ->where('n.id = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $this->db->setQuery($query, 0, 1);
        $item = $this->db->loadObject();
        return $item ?: null;
    }

    /** @return array<int,object> */
    public function getRecipients(int $notificationId): array
    {
        if ($notificationId <= 0) {
            return [];
        }

        $query = $this->db->getQuery(true)
            ->select([
                'r.*',
                $this->db->quoteName('d.status', 'delivery_status'),
            ])
            ->from($this->db->quoteName('#__xdecaronotifications_recipients', 'r'))
            ->leftJoin(
                $this->db->quoteName('#__xdecaronotifications_deliveries', 'd')
                . ' ON d.recipient_id = r.id AND d.notification_id = r.notification_id AND d.channel = '
                . $this->db->quote('internal')
            )
            ->where('r.notification_id = :notification_id')
            ->order('r.id ASC')
            ->bind(':notification_id', $notificationId, ParameterType::INTEGER);
        $this->db->setQuery($query);
        return (array) $this->db->loadObjectList();
    }

    private function findExistingByExternalKey(string $sourceComponent, string $externalKey): ?object
    {
        $query = $this->db->getQuery(true)
            ->select([$this->db->quoteName('id'), $this->db->quoteName('category')])
            ->from($this->db->quoteName('#__xdecaronotifications_notifications'))
            ->where($this->db->quoteName('source_component') . ' = :source_component')
            ->where($this->db->quoteName('external_key') . ' = :external_key')
            ->bind(':source_component', $sourceComponent, ParameterType::STRING)
            ->bind(':external_key', $externalKey, ParameterType::STRING);
        $this->db->setQuery($query, 0, 1);
        $item = $this->db->loadObject();
        return $item ?: null;
    }

    /** @param array<int,RecipientReference> $recipients */
    private function ensureRecipients(int $notificationId, string $category, array $recipients): void
    {
        $now = $this->now();

        foreach ($recipients as $recipient) {
            if (!$this->preferences->isInternalEnabled($recipient, $category, true)) {
                continue;
            }

            $recipientKey = $recipient->key();
            $recipientId = $this->findRecipientId($notificationId, $recipientKey);

            if ($recipientId <= 0) {
                $row = (object) [
                    'notification_id' => $notificationId,
                    'recipient_key' => $recipientKey,
                    'recipient_type' => $recipient->getType(),
                    'user_id' => $recipient->getUserId(),
                    'recipient_component' => $recipient->getComponent(),
                    'recipient_entity' => $recipient->getEntity(),
                    'recipient_entity_id' => $recipient->getEntityId(),
                    'read_at' => null,
                    'archived_at' => null,
                    'created' => $now,
                ];

                try {
                    $this->db->insertObject('#__xdecaronotifications_recipients', $row, 'id');
                    $recipientId = (int) ($row->id ?? 0);
                } catch (Throwable $exception) {
                    $recipientId = $this->findRecipientId($notificationId, $recipientKey);
                    if ($recipientId <= 0) {
                        throw $exception;
                    }
                }
            }

            if ($recipientId <= 0 || $this->deliveryExists($notificationId, $recipientId, 'internal')) {
                continue;
            }

            $resolved = $recipient->getType() === 'user';
            $delivery = (object) [
                'notification_id' => $notificationId,
                'recipient_id' => $recipientId,
                'channel' => 'internal',
                'status' => $resolved ? 'delivered' : 'queued',
                'attempts' => $resolved ? 1 : 0,
                'last_attempt_at' => $resolved ? $now : null,
                'delivered_at' => $resolved ? $now : null,
                'provider' => $resolved ? 'internal' : null,
                'external_id' => null,
                'error_code' => null,
                'error_message' => null,
                'created' => $now,
                'modified' => $now,
            ];
            $this->db->insertObject('#__xdecaronotifications_deliveries', $delivery, 'id');
        }
    }

    private function findRecipientId(int $notificationId, string $recipientKey): int
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__xdecaronotifications_recipients'))
            ->where($this->db->quoteName('notification_id') . ' = :notification_id')
            ->where($this->db->quoteName('recipient_key') . ' = :recipient_key')
            ->bind(':notification_id', $notificationId, ParameterType::INTEGER)
            ->bind(':recipient_key', $recipientKey, ParameterType::STRING);
        $this->db->setQuery($query, 0, 1);
        return (int) $this->db->loadResult();
    }

    private function deliveryExists(int $notificationId, int $recipientId, string $channel): bool
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__xdecaronotifications_deliveries'))
            ->where($this->db->quoteName('notification_id') . ' = :notification_id')
            ->where($this->db->quoteName('recipient_id') . ' = :recipient_id')
            ->where($this->db->quoteName('channel') . ' = :channel')
            ->bind(':notification_id', $notificationId, ParameterType::INTEGER)
            ->bind(':recipient_id', $recipientId, ParameterType::INTEGER)
            ->bind(':channel', $channel, ParameterType::STRING);
        $this->db->setQuery($query);
        return (int) $this->db->loadResult() > 0;
    }

    /** @param array<int,RecipientReference|array<string,mixed>> $recipients @return array<int,RecipientReference> */
    private function normalizeRecipients(array $recipients): array
    {
        $normalized = [];

        foreach ($recipients as $recipient) {
            if ($recipient instanceof RecipientReference) {
                $reference = $recipient;
            } elseif (is_array($recipient)) {
                $reference = RecipientReference::fromArray($recipient);
            } else {
                throw new InvalidArgumentException('Invalid notification recipient.');
            }

            $normalized[$reference->key()] = $reference;
        }

        return array_values($normalized);
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function normalizeNotification(array $data): array
    {
        $sourceComponent = trim((string) ($data['source_component'] ?? ''));

        if (strlen($sourceComponent) > 64 || !preg_match('/^com_[a-z0-9][a-z0-9_]*$/', $sourceComponent)) {
            throw new InvalidArgumentException('A valid source_component is required.');
        }

        $title = trim((string) ($data['title'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        if ($title === '' || mb_strlen($title) > 255 || $message === '') {
            throw new InvalidArgumentException('Notification title and message are required.');
        }

        $category = strtolower(trim((string) ($data['category'] ?? 'general')));
        if (!preg_match('/^[a-z][a-z0-9_.-]{0,63}$/', $category)) {
            throw new InvalidArgumentException('Invalid notification category.');
        }

        $priority = strtolower(trim((string) ($data['priority'] ?? 'normal')));
        if (!in_array($priority, ['low', 'normal', 'high', 'critical'], true)) {
            throw new InvalidArgumentException('Invalid notification priority.');
        }

        $sourceEntity = $this->nullableString($data['source_entity'] ?? null);
        $sourceEntityId = $this->nullableString($data['source_entity_id'] ?? null);

        if (($sourceEntity === null) !== ($sourceEntityId === null)) {
            throw new InvalidArgumentException('source_entity and source_entity_id must be provided together.');
        }

        if ($sourceEntity !== null && !preg_match('/^[a-z][a-z0-9_]{0,63}$/', $sourceEntity)) {
            throw new InvalidArgumentException('Invalid source entity type.');
        }

        if ($sourceEntityId !== null && !preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$/', $sourceEntityId)) {
            throw new InvalidArgumentException('Invalid source entity ID.');
        }

        $eventName = $this->nullableString($data['event_name'] ?? null);
        if ($eventName !== null && !preg_match('/^[a-z][a-z0-9_.-]{0,127}$/', $eventName)) {
            throw new InvalidArgumentException('Invalid notification event name.');
        }

        $eventVersion = $this->nullableString($data['event_version'] ?? null);
        if ($eventVersion !== null && !preg_match('/^[0-9]+(?:\.[0-9]+){0,2}$/', $eventVersion)) {
            throw new InvalidArgumentException('Invalid notification event version.');
        }

        $scheduledAt = $this->normalizeDate($data['scheduled_at'] ?? null);
        $expiresAt = $this->normalizeDate($data['expires_at'] ?? null);
        if ($scheduledAt !== null && $expiresAt !== null && $expiresAt <= $scheduledAt) {
            throw new InvalidArgumentException('Notification expiry must be after its scheduled time.');
        }

        $actionUrl = $this->nullableString($data['action_url'] ?? null);
        if ($actionUrl !== null && !$this->isSafeActionUrl($actionUrl)) {
            throw new InvalidArgumentException('Unsafe notification action URL.');
        }

        $actionLabel = $this->nullableString($data['action_label'] ?? null);
        if ($actionLabel !== null && mb_strlen($actionLabel) > 255) {
            throw new InvalidArgumentException('Notification action label is too long.');
        }

        $params = $data['params'] ?? [];
        if (!is_array($params)) {
            throw new InvalidArgumentException('Notification params must be an array.');
        }

        $paramsJson = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($paramsJson === false) {
            throw new InvalidArgumentException('Notification params are not JSON serializable.');
        }

        $externalKey = $this->nullableString($data['external_key'] ?? null);
        if ($externalKey !== null) {
            if (strlen($externalKey) > 191 || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._:\/-]{0,190}$/', $externalKey)) {
                throw new InvalidArgumentException('Invalid notification external_key.');
            }
        }

        $now = $this->now();

        return [
            'uuid' => $this->uuidV4(),
            'external_key' => $externalKey,
            'source_component' => $sourceComponent,
            'source_entity' => $sourceEntity,
            'source_entity_id' => $sourceEntityId,
            'event_name' => $eventName,
            'event_version' => $eventVersion,
            'category' => $category,
            'priority' => $priority,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_label' => $actionLabel,
            'scheduled_at' => $scheduledAt,
            'expires_at' => $expiresAt,
            'published' => !empty($data['published']) ? 1 : 0,
            'params' => $paramsJson,
            'created_by' => isset($data['created_by']) ? max(0, (int) $data['created_by']) : 0,
            'created' => $now,
            'modified_by' => 0,
            'modified' => null,
        ];
    }

    private function updateRecipientState(int $notificationId, int $userId, string $column, ?string $value): bool
    {
        if ($notificationId <= 0 || $userId <= 0 || !in_array($column, ['read_at', 'archived_at'], true)) {
            return false;
        }

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__xdecaronotifications_recipients'))
            ->where($this->db->quoteName('notification_id') . ' = :notification_id')
            ->where($this->db->quoteName('user_id') . ' = :user_id')
            ->bind(':notification_id', $notificationId, ParameterType::INTEGER)
            ->bind(':user_id', $userId, ParameterType::INTEGER);

        if ($value === null) {
            $query->set($this->db->quoteName($column) . ' = NULL');
        } else {
            $query->set($this->db->quoteName($column) . ' = :state_value')
                ->bind(':state_value', $value, ParameterType::STRING);
        }

        $this->db->setQuery($query)->execute();
        return $this->db->getAffectedRows() > 0;
    }

    private function normalizeDate($value): ?string
    {
        $value = $this->nullableString($value);
        if ($value === null) {
            return null;
        }

        try {
            $date = new DateTimeImmutable($value, new DateTimeZone('UTC'));
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Invalid notification date.', 0, $exception);
        }

        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    private function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function isSafeActionUrl(string $url): bool
    {
        if (preg_match('/[\x00-\x1F\x7F\\\\]/', $url)) {
            return false;
        }
        if (preg_match('#^https?://#i', $url)) {
            return true;
        }
        if (strpos($url, '//') === 0) {
            return false;
        }
        return strpos($url, '/') === 0 || preg_match('#^index\.php(?:\?|$)#i', $url) === 1;
    }

    private function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        $hex = bin2hex($data);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
