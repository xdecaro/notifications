<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

final class NotificationService
{
    private const PRIORITIES = ['low', 'normal', 'high', 'critical'];

    /** @var DatabaseInterface */
    private $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Create a notification and return its ID.
     *
     * `external_key`, when supplied, is idempotent within `source_component`.
     * Cross-product references are stored as plain stable identifiers; this
     * component never creates foreign keys to another extension's tables.
     */
    public function create(array $data): int
    {
        $recipientType = $this->validateToken((string) ($data['recipient_type'] ?? ''), 32, 'recipient_type');
        $recipientId   = $this->validateIdentifier((string) ($data['recipient_id'] ?? ''), 'recipient_id');
        $category      = $this->validateToken((string) ($data['category'] ?? 'general'), 64, 'category');
        $priority      = strtolower(trim((string) ($data['priority'] ?? 'normal')));
        $title         = trim((string) ($data['title'] ?? ''));
        $message       = trim((string) ($data['message'] ?? ''));

        if (!in_array($priority, self::PRIORITIES, true)) {
            throw new InvalidArgumentException('Invalid notification priority.');
        }

        if ($title === '' || strlen($title) > 255) {
            throw new InvalidArgumentException('Notification title is required and must not exceed 255 bytes.');
        }

        if ($message === '') {
            throw new InvalidArgumentException('Notification message is required.');
        }

        $sourceComponent = trim((string) ($data['source_component'] ?? ''));
        $sourceEntity    = trim((string) ($data['source_entity'] ?? ''));
        $sourceId        = trim((string) ($data['source_id'] ?? ''));

        if ($sourceComponent !== '' && !preg_match('/^com_[a-z0-9][a-z0-9_]*$/', $sourceComponent)) {
            throw new InvalidArgumentException('Invalid source_component.');
        }

        if (($sourceEntity !== '' || $sourceId !== '') && $sourceComponent === '') {
            throw new InvalidArgumentException('source_component is required when a source entity is provided.');
        }

        if ($sourceEntity !== '') {
            $sourceEntity = $this->validateToken($sourceEntity, 64, 'source_entity');
        }

        if ($sourceId !== '') {
            $sourceId = $this->validateIdentifier($sourceId, 'source_id');
        }

        $externalKey = trim((string) ($data['external_key'] ?? ''));
        if ($externalKey !== '' && strlen($externalKey) > 191) {
            throw new InvalidArgumentException('external_key must not exceed 191 bytes.');
        }

        if ($externalKey !== '' && $sourceComponent === '') {
            throw new InvalidArgumentException('source_component is required when external_key is supplied.');
        }

        if ($externalKey !== '') {
            $existing = $this->findByExternalKey($sourceComponent, $externalKey);
            if ($existing !== null) {
                return $existing;
            }
        }

        $actionUrl = $this->normalizeActionUrl((string) ($data['action_url'] ?? ''));
        $payload   = $this->encodePayload($data['payload'] ?? null);
        $expiresAt = $this->normalizeSqlDate($data['expires_at'] ?? null);
        $createdBy = isset($data['created_by']) ? max(0, (int) $data['created_by']) : (int) Factory::getApplication()->getIdentity()->id;
        $created   = Factory::getDate()->toSql();

        $columns = [
            'external_key', 'source_component', 'source_entity', 'source_id',
            'recipient_type', 'recipient_id', 'category', 'priority', 'title',
            'message', 'action_url', 'payload', 'state', 'created', 'created_by',
            'read_at', 'archived_at', 'expires_at',
        ];

        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__xdecaro_notifications'))
            ->columns(array_map([$this->db, 'quoteName'], $columns))
            ->values(implode(',', [
                ':external_key', ':source_component', ':source_entity', ':source_id',
                ':recipient_type', ':recipient_id', ':category', ':priority', ':title',
                ':message', ':action_url', ':payload', ':state', ':created', ':created_by',
                'NULL', 'NULL', ':expires_at',
            ]));

        $externalValue = $externalKey !== '' ? $externalKey : null;
        $actionValue   = $actionUrl !== '' ? $actionUrl : null;

        $query->bind(':external_key', $externalValue)
            ->bind(':source_component', $sourceComponent)
            ->bind(':source_entity', $sourceEntity)
            ->bind(':source_id', $sourceId)
            ->bind(':recipient_type', $recipientType)
            ->bind(':recipient_id', $recipientId)
            ->bind(':category', $category)
            ->bind(':priority', $priority)
            ->bind(':title', $title)
            ->bind(':message', $message)
            ->bind(':action_url', $actionValue)
            ->bind(':payload', $payload)
            ->bind(':state', $state = 'unread')
            ->bind(':created', $created)
            ->bind(':created_by', $createdBy)
            ->bind(':expires_at', $expiresAt);

        try {
            $this->db->setQuery($query)->execute();
        } catch (RuntimeException $exception) {
            if ($externalKey !== '') {
                $existing = $this->findByExternalKey($sourceComponent, $externalKey);
                if ($existing !== null) {
                    return $existing;
                }
            }
            throw $exception;
        }

        return (int) $this->db->insertid();
    }

    public function markRead(int $id): void
    {
        if ($id < 1) {
            throw new InvalidArgumentException('Invalid notification ID.');
        }

        $readAt = Factory::getDate()->toSql();
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__xdecaro_notifications'))
            ->set($this->db->quoteName('state') . ' = :state')
            ->set($this->db->quoteName('read_at') . ' = :read_at')
            ->where($this->db->quoteName('id') . ' = :id')
            ->where($this->db->quoteName('state') . ' = :unread')
            ->bind(':state', $state = 'read')
            ->bind(':read_at', $readAt)
            ->bind(':id', $id)
            ->bind(':unread', $unread = 'unread');

        $this->db->setQuery($query)->execute();
    }

    public function archive(int $id): void
    {
        if ($id < 1) {
            throw new InvalidArgumentException('Invalid notification ID.');
        }

        $archivedAt = Factory::getDate()->toSql();
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__xdecaro_notifications'))
            ->set($this->db->quoteName('state') . ' = :state')
            ->set($this->db->quoteName('archived_at') . ' = :archived_at')
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':state', $state = 'archived')
            ->bind(':archived_at', $archivedAt)
            ->bind(':id', $id);

        $this->db->setQuery($query)->execute();
    }

    public function getUnreadCount(string $recipientType, string $recipientId): int
    {
        $recipientType = $this->validateToken($recipientType, 32, 'recipient_type');
        $recipientId   = $this->validateIdentifier($recipientId, 'recipient_id');

        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__xdecaro_notifications'))
            ->where($this->db->quoteName('recipient_type') . ' = :recipient_type')
            ->where($this->db->quoteName('recipient_id') . ' = :recipient_id')
            ->where($this->db->quoteName('state') . ' = :state')
            ->where('(' . $this->db->quoteName('expires_at') . ' IS NULL OR ' . $this->db->quoteName('expires_at') . ' >= :now)')
            ->bind(':recipient_type', $recipientType)
            ->bind(':recipient_id', $recipientId)
            ->bind(':state', $state = 'unread')
            ->bind(':now', $now = Factory::getDate()->toSql());

        return (int) $this->db->setQuery($query)->loadResult();
    }

    private function findByExternalKey(string $sourceComponent, string $externalKey): ?int
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__xdecaro_notifications'))
            ->where($this->db->quoteName('source_component') . ' = :source_component')
            ->where($this->db->quoteName('external_key') . ' = :external_key')
            ->bind(':source_component', $sourceComponent)
            ->bind(':external_key', $externalKey);

        $value = $this->db->setQuery($query)->loadResult();

        return $value === null ? null : (int) $value;
    }

    private function validateToken(string $value, int $maxLength, string $field): string
    {
        $value = strtolower(trim($value));
        if ($value === '' || strlen($value) > $maxLength || !preg_match('/^[a-z][a-z0-9_]*$/', $value)) {
            throw new InvalidArgumentException('Invalid ' . $field . '.');
        }

        return $value;
    }

    private function validateIdentifier(string $value, string $field): string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 128 || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]*$/', $value)) {
            throw new InvalidArgumentException('Invalid ' . $field . '.');
        }

        return $value;
    }

    private function normalizeActionUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (strlen($url) > 2048 || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            throw new InvalidArgumentException('Invalid action_url.');
        }

        if (strpos($url, 'index.php?') === 0 || strpos($url, '/') === 0) {
            return $url;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme']) || !in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            throw new InvalidArgumentException('action_url must be a Joomla-relative URL or HTTP(S) URL.');
        }

        return $url;
    }

    private function encodePayload($payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        if (is_string($payload)) {
            json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('payload string must contain valid JSON.');
            }
            return $payload;
        }

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new InvalidArgumentException('payload cannot be encoded as JSON.');
        }

        return $json;
    }

    private function normalizeSqlDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Factory::getDate($value)->toSql();
        } catch (\Exception $exception) {
            throw new InvalidArgumentException('Invalid date value.', 0, $exception);
        }
    }
}
