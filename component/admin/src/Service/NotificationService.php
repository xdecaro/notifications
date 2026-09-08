<?php
namespace Xdecaro\Component\Decaronotifications\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;
use Throwable;
use Xdecaro\Component\Decaronotifications\Administrator\Value\RecipientReference;

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
     * Publish one notification to one or more recipients.
     *
     * @param array<string,mixed> $data
     * @param array<int,RecipientReference|array<string,mixed>> $recipients
     */
    public function publish(array $data, array $recipients): int
    {
        $normalized = $this->normalizeNotification($data);
        $normalizedRecipients = $this->normalizeRecipients($recipients);

        if ($normalizedRecipients === []) {
            throw new InvalidArgumentException('At least one valid notification recipient is required.');
        }

        if ($normalized['external_key'] !== null) {
            $existingId = $this->findByExternalKey($normalized['source_component'], $normalized['external_key']);
            if ($existingId > 0) {
                $this->ensureRecipients($existingId, $normalized['category'], $normalizedRecipients);
                return $existingId;
            }
        }

        $this->db->transactionStart();

        try {
            $row = (object) $normalized;
            $this->db->insertObject('#__decaronotifications_notifications', $row, 'id');
            $notificationId = (int) $row->id;

            if ($notificationId <= 0) {
                throw new RuntimeException('Notification insert did not return a valid ID.');
            }

            $this->ensureRecipients($notificationId, $normalized['category'], $normalizedRecipients);
            $this->db->transactionCommit();

            return $notificationId;
        } catch (Throwable $exception) {
            $this->db->transactionRollback();
            throw $exception;
        }
    }

    /**
     * Convert a Core IntegrationEvent-like object to a persisted notification.
     * The method deliberately uses duck typing so Notifications remains usable without Core.
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
        $payload = (array) $event->getPayload();
        $sourceComponent = isset($presentation['source_component']) ? (string) $presentation['source_component'] : 'com_decaronotifications';
        $sourceEntity = null;
        $sourceEntityId = null;

        if (is_object($source) && method_exists($source, 'getComponent') && method_exists($source, 'getEntity') && method_exists($source, 'getId')) {
            $sourceComponent = (string) $source->getComponent();
            $sourceEntity = (string) $source->getEntity();
            $sourceEntityId = (string) $source->getId();
        }

        $eventName = (string) $event->getName();
        $externalKey = isset($presentation['external_key']) ? (string) $presentation['external_key'] : null;
        if (($externalKey === null || trim($externalKey) === '') && isset($payload['external_key'])) {
            $externalKey = (string) $payload['external_key'];
        }

        return $this->publish([
            'source_component' => $sourceComponent,
            'source_entity' => $sourceEntity,
            'source_entity_id' => $sourceEntityId,
            'event_name' => $eventName,
            'event_version' => (string) $event->getVersion(),
            'external_key' => $externalKey,
            'category' => isset($presentation['category']) ? (string) $presentation['category'] : str_replace('.', '_', $eventName),
            'priority' => isset($presentation['priority']) ? (string) $presentation['priority'] : 'normal',
            'title' => isset($presentation['title']) ? (string) $presentation['title'] : $eventName,
            'message' => isset($presentation['message']) ? (string) $presentation['message'] : '',
            'action_url' => isset($presentation['action_url']) ? (string) $presentation['action_url'] : null,
            'action_label' => isset($presentation['action_label']) ? (string) $presentation['action_label'] : null,
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

        $now = gmdate('Y-m-d H:i:s');
        $query = $this->db->getQuery(true)
            ->select([
                'n.*',
                $this->db->quoteName('r.read_at'),
                $this->db->quoteName('r.archived_at'),
                $this->db->quoteName('r.id', 'recipient_row_id'),
            ])
            ->from($this->db->quoteName('#__decaronotifications_notifications', 'n'))
            ->innerJoin($this->db->quoteName('#__decaronotifications_recipients', 'r') . ' ON r.notification_id = n.id')
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

        $now = gmdate('Y-m-d H:i:s');
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__decaronotifications_recipients', 'r'))
            ->innerJoin($this->db->quoteName('#__decaronotifications_notifications', 'n') . ' ON n.id = r.notification_id')
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
        return $this->updateRecipientState($notificationId, $userId, 'read_at', $read ? gmdate('Y-m-d H:i:s') : null);
    }

    public function archiveForUser(int $notificationId, int $userId, bool $archived = true): bool
    {
        return $this->updateRecipientState($notificationId, $userId, 'archived_at', $archived ? gmdate('Y-m-d H:i:s') : null);
    }

    /** @return array<string,int> */
    public function getAdminStats(): array
    {
        $now = gmdate('Y-m-d H:i:s');
        $stats = ['total' => 0, 'active' => 0, 'scheduled' => 0, 'expired' => 0, 'unread' => 0];

        $queries = [
            'total' => 'SELECT COUNT(*) FROM #__decaronotifications_notifications',
            'active' => 'SELECT COUNT(*) FROM #__decaronotifications_notifications WHERE published = 1 AND (scheduled_at IS NULL OR scheduled_at <= ' . $this->db->quote($now) . ') AND (expires_at IS NULL OR expires_at > ' . $this->db->quote($now) . ')',
            'scheduled' => 'SELECT COUNT(*) FROM #__decaronotifications_notifications WHERE published = 1 AND scheduled_at > ' . $this->db->quote($now),
            'expired' => 'SELECT COUNT(*) FROM #__decaronotifications_notifications WHERE expires_at IS NOT NULL AND expires_at <= ' . $this->db->quote($now),
            'unread' => 'SELECT COUNT(*) FROM #__decaronotifications_recipients WHERE read_at IS NULL AND archived_at IS NULL',
        ];

        foreach ($queries as $key => $sql) {
            $this->db->setQuery($sql);
            $stats[$key] = (int) $this->db->loadResult();
        }

        return $stats;
    }

    /** @return array<int,object> */
    public function getAdminRecent(int $limit = 20): array
    {
        $query = $this->db->getQuery(true)
            ->select('n.*, COUNT(r.id) AS recipient_count, SUM(CASE WHEN r.read_at IS NOT NULL THEN 1 ELSE 0 END) AS read_count')
            ->from($this->db->quoteName('#__decaronotifications_notifications', 'n'))
            ->leftJoin($this->db->quoteName('#__decaronotifications_recipients', 'r') . ' ON r.notification_id = n.id')
            ->group('n.id')
            ->order('n.created DESC, n.id DESC');
        $this->db->setQuery($query, 0, max(1, min(100, $limit)));
        return (array) $this->db->loadObjectList();
    }

    public function getById(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }
        $query = $this->db->getQuery(true)
            ->select('n.*, COUNT(r.id) AS recipient_count, SUM(CASE WHEN r.read_at IS NOT NULL THEN 1 ELSE 0 END) AS read_count')
            ->from($this->db->quoteName('#__decaronotifications_notifications', 'n'))
            ->leftJoin($this->db->quoteName('#__decaronotifications_recipients', 'r') . ' ON r.notification_id = n.id')
            ->where('n.id = :id')
            ->group('n.id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $this->db->setQuery($query, 0, 1);
        $item = $this->db->loadObject();
        return $item ?: null;
    }

    /** @return array<int,object> */
    public function getRecipients(int $notificationId): array
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__decaronotifications_recipients'))
            ->where($this->db->quoteName('notification_id') . ' = :notification_id')
            ->order($this->db->quoteName('id') . ' ASC')
            ->bind(':notification_id', $notificationId, ParameterType::INTEGER);
        $this->db->setQuery($query);
        return (array) $this->db->loadObjectList();
    }

    private function findByExternalKey(string $sourceComponent, string $externalKey): int
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__decaronotifications_notifications'))
            ->where($this->db->quoteName('source_component') . ' = :source_component')
            ->where($this->db->quoteName('external_key') . ' = :external_key')
            ->bind(':source_component', $sourceComponent, ParameterType::STRING)
            ->bind(':external_key', $externalKey, ParameterType::STRING);
        $this->db->setQuery($query, 0, 1);
        return (int) $this->db->loadResult();
    }

    /** @param array<int,RecipientReference> $recipients */
    private function ensureRecipients(int $notificationId, string $category, array $recipients): void
    {
        $now = gmdate('Y-m-d H:i:s');

        foreach ($recipients as $recipient) {
            if (!$this->preferences->isEnabled($recipient, $category, 'internal', true)) {
                continue;
            }

            $recipientKey = $recipient->key();
            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__decaronotifications_recipients'))
                ->where($this->db->quoteName('notification_id') . ' = :notification_id')
                ->where($this->db->quoteName('recipient_key') . ' = :recipient_key')
                ->bind(':notification_id', $notificationId, ParameterType::INTEGER)
                ->bind(':recipient_key', $recipientKey, ParameterType::STRING);
            $this->db->setQuery($query, 0, 1);
            if ((int) $this->db->loadResult() > 0) {
                continue;
            }

            $data = $recipient->toArray();
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
            $this->db->insertObject('#__decaronotifications_recipients', $row, 'id');

            $delivery = (object) [
                'notification_id' => $notificationId,
                'recipient_id' => (int) $row->id,
                'channel' => 'internal',
                'status' => 'delivered',
                'attempts' => 1,
                'last_attempt_at' => $now,
                'delivered_at' => $now,
                'provider' => 'internal',
                'external_id' => null,
                'error_code' => null,
                'error_message' => null,
                'created' => $now,
                'modified' => $now,
            ];
            $this->db->insertObject('#__decaronotifications_deliveries', $delivery, 'id');
        }
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
        if (!preg_match('/^com_[a-z0-9][a-z0-9_]*$/', $sourceComponent)) {
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

        $scheduledAt = $this->normalizeDate($data['scheduled_at'] ?? null);
        $expiresAt = $this->normalizeDate($data['expires_at'] ?? null);
        if ($scheduledAt !== null && $expiresAt !== null && strtotime($expiresAt) <= strtotime($scheduledAt)) {
            throw new InvalidArgumentException('Notification expiry must be after its scheduled time.');
        }

        $actionUrl = isset($data['action_url']) ? trim((string) $data['action_url']) : null;
        if ($actionUrl === '') {
            $actionUrl = null;
        }
        if ($actionUrl !== null && !$this->isSafeActionUrl($actionUrl)) {
            throw new InvalidArgumentException('Unsafe notification action URL.');
        }

        $params = $data['params'] ?? [];
        if (!is_array($params)) {
            throw new InvalidArgumentException('Notification params must be an array.');
        }
        $paramsJson = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($paramsJson === false) {
            throw new InvalidArgumentException('Notification params are not JSON serializable.');
        }

        $externalKey = isset($data['external_key']) ? trim((string) $data['external_key']) : null;
        if ($externalKey === '') {
            $externalKey = null;
        }
        if ($externalKey !== null && mb_strlen($externalKey) > 191) {
            throw new InvalidArgumentException('Notification external_key is too long.');
        }

        $sourceEntity = isset($data['source_entity']) ? trim((string) $data['source_entity']) : null;
        $sourceEntityId = isset($data['source_entity_id']) ? trim((string) $data['source_entity_id']) : null;
        if ($sourceEntity === '') {
            $sourceEntity = null;
        }
        if ($sourceEntityId === '') {
            $sourceEntityId = null;
        }

        $now = gmdate('Y-m-d H:i:s');
        return [
            'uuid' => $this->uuidV4(),
            'external_key' => $externalKey,
            'source_component' => $sourceComponent,
            'source_entity' => $sourceEntity,
            'source_entity_id' => $sourceEntityId,
            'event_name' => isset($data['event_name']) && trim((string) $data['event_name']) !== '' ? trim((string) $data['event_name']) : null,
            'event_version' => isset($data['event_version']) && trim((string) $data['event_version']) !== '' ? trim((string) $data['event_version']) : null,
            'category' => $category,
            'priority' => $priority,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_label' => isset($data['action_label']) && trim((string) $data['action_label']) !== '' ? trim((string) $data['action_label']) : null,
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
            ->update($this->db->quoteName('#__decaronotifications_recipients'))
            ->set($this->db->quoteName($column) . ' = :state_value')
            ->where($this->db->quoteName('notification_id') . ' = :notification_id')
            ->where($this->db->quoteName('user_id') . ' = :user_id')
            ->bind(':state_value', $value, ParameterType::STRING)
            ->bind(':notification_id', $notificationId, ParameterType::INTEGER)
            ->bind(':user_id', $userId, ParameterType::INTEGER);
        $this->db->setQuery($query)->execute();
        return $this->db->getAffectedRows() > 0;
    }

    private function normalizeDate($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            throw new InvalidArgumentException('Invalid notification date.');
        }
        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    private function isSafeActionUrl(string $url): bool
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return false;
        }
        if (preg_match('#^(?:https?://|/|index\.php(?:\?|$))#i', $url)) {
            return true;
        }
        return false;
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
