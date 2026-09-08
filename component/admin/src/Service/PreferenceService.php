<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class PreferenceService
{
    /** @var DatabaseInterface */
    private $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function setPreference(
        string $recipientType,
        string $recipientId,
        string $category,
        string $channel,
        bool $enabled,
        ?int $userId = null
    ): void {
        $recipientType = $this->validateToken($recipientType, 32, 'recipient_type');
        $recipientId   = $this->validateIdentifier($recipientId, 'recipient_id');
        $category      = $this->normalizeCategory($category);
        $channel       = $this->validateChannel($channel);
        $now           = Factory::getDate()->toSql();
        $actor         = $userId !== null ? max(0, $userId) : (int) Factory::getApplication()->getIdentity()->id;
        $enabledValue  = $enabled ? 1 : 0;

        $existingId = $this->findId($recipientType, $recipientId, $category, $channel);

        if ($existingId !== null) {
            $query = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__xdecaronotifications_preferences'))
                ->set($this->db->quoteName('enabled') . ' = :enabled')
                ->set($this->db->quoteName('modified') . ' = :modified')
                ->set($this->db->quoteName('modified_by') . ' = :modified_by')
                ->where($this->db->quoteName('id') . ' = :id')
                ->bind(':enabled', $enabledValue, ParameterType::INTEGER)
                ->bind(':modified', $now)
                ->bind(':modified_by', $actor, ParameterType::INTEGER)
                ->bind(':id', $existingId, ParameterType::INTEGER);

            $this->db->setQuery($query)->execute();
            return;
        }

        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__xdecaronotifications_preferences'))
            ->columns([
                $this->db->quoteName('recipient_type'),
                $this->db->quoteName('recipient_id'),
                $this->db->quoteName('category'),
                $this->db->quoteName('channel'),
                $this->db->quoteName('enabled'),
                $this->db->quoteName('created'),
                $this->db->quoteName('created_by'),
                $this->db->quoteName('modified'),
                $this->db->quoteName('modified_by'),
            ])
            ->values(':recipient_type,:recipient_id,:category,:channel,:enabled,:created,:created_by,:modified,:modified_by')
            ->bind(':recipient_type', $recipientType)
            ->bind(':recipient_id', $recipientId)
            ->bind(':category', $category)
            ->bind(':channel', $channel)
            ->bind(':enabled', $enabledValue, ParameterType::INTEGER)
            ->bind(':created', $now)
            ->bind(':created_by', $actor, ParameterType::INTEGER)
            ->bind(':modified', $now)
            ->bind(':modified_by', $actor, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();
    }

    public function removePreference(
        string $recipientType,
        string $recipientId,
        string $category,
        string $channel
    ): void {
        $recipientType = $this->validateToken($recipientType, 32, 'recipient_type');
        $recipientId   = $this->validateIdentifier($recipientId, 'recipient_id');
        $category      = $this->normalizeCategory($category);
        $channel       = $this->validateChannel($channel);

        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__xdecaronotifications_preferences'))
            ->where($this->db->quoteName('recipient_type') . ' = :recipient_type')
            ->where($this->db->quoteName('recipient_id') . ' = :recipient_id')
            ->where($this->db->quoteName('category') . ' = :category')
            ->where($this->db->quoteName('channel') . ' = :channel')
            ->bind(':recipient_type', $recipientType)
            ->bind(':recipient_id', $recipientId)
            ->bind(':category', $category)
            ->bind(':channel', $channel);

        $this->db->setQuery($query)->execute();
    }

    public function isEnabled(
        string $recipientType,
        string $recipientId,
        string $category,
        string $channel,
        bool $default = true
    ): bool {
        $recipientType = $this->validateToken($recipientType, 32, 'recipient_type');
        $recipientId   = $this->validateIdentifier($recipientId, 'recipient_id');
        $category      = $this->normalizeCategory($category);
        $channel       = $this->validateChannel($channel);

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('category'),
                $this->db->quoteName('enabled'),
            ])
            ->from($this->db->quoteName('#__xdecaronotifications_preferences'))
            ->where($this->db->quoteName('recipient_type') . ' = :recipient_type')
            ->where($this->db->quoteName('recipient_id') . ' = :recipient_id')
            ->where($this->db->quoteName('channel') . ' = :channel')
            ->where('(' . $this->db->quoteName('category') . ' = :exact_category OR ' . $this->db->quoteName('category') . ' = :wildcard_category)')
            ->order('CASE WHEN ' . $this->db->quoteName('category') . ' = :order_category THEN 0 ELSE 1 END')
            ->bind(':recipient_type', $recipientType)
            ->bind(':recipient_id', $recipientId)
            ->bind(':channel', $channel)
            ->bind(':exact_category', $category)
            ->bind(':wildcard_category', $wildcard = '*')
            ->bind(':order_category', $category);

        $row = $this->db->setQuery($query, 0, 1)->loadAssoc();

        return $row === null ? $default : ((int) $row['enabled'] === 1);
    }

    /** @param array<int,string> $channels @return array<int,string> */
    public function filterEnabledChannels(
        string $recipientType,
        string $recipientId,
        string $category,
        array $channels,
        bool $default = true
    ): array {
        $result = [];

        foreach ($channels as $channel) {
            $channel = $this->validateChannel((string) $channel);

            if (in_array($channel, $result, true)) {
                continue;
            }

            if ($this->isEnabled($recipientType, $recipientId, $category, $channel, $default)) {
                $result[] = $channel;
            }
        }

        return $result;
    }

    /** @return array<int,object> */
    public function getPreferences(string $recipientType, string $recipientId): array
    {
        $recipientType = $this->validateToken($recipientType, 32, 'recipient_type');
        $recipientId   = $this->validateIdentifier($recipientId, 'recipient_id');

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('category'),
                $this->db->quoteName('channel'),
                $this->db->quoteName('enabled'),
                $this->db->quoteName('modified'),
            ])
            ->from($this->db->quoteName('#__xdecaronotifications_preferences'))
            ->where($this->db->quoteName('recipient_type') . ' = :recipient_type')
            ->where($this->db->quoteName('recipient_id') . ' = :recipient_id')
            ->order($this->db->quoteName('category') . ' ASC, ' . $this->db->quoteName('channel') . ' ASC')
            ->bind(':recipient_type', $recipientType)
            ->bind(':recipient_id', $recipientId);

        return (array) $this->db->setQuery($query)->loadObjectList();
    }

    private function findId(string $recipientType, string $recipientId, string $category, string $channel): ?int
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__xdecaronotifications_preferences'))
            ->where($this->db->quoteName('recipient_type') . ' = :recipient_type')
            ->where($this->db->quoteName('recipient_id') . ' = :recipient_id')
            ->where($this->db->quoteName('category') . ' = :category')
            ->where($this->db->quoteName('channel') . ' = :channel')
            ->bind(':recipient_type', $recipientType)
            ->bind(':recipient_id', $recipientId)
            ->bind(':category', $category)
            ->bind(':channel', $channel);

        $value = $this->db->setQuery($query)->loadResult();

        return $value === null ? null : (int) $value;
    }

    private function normalizeCategory(string $category): string
    {
        $category = strtolower(trim($category));
        if ($category === '*') {
            return '*';
        }

        return $this->validateToken($category === '' ? 'general' : $category, 64, 'category');
    }

    private function validateChannel(string $channel): string
    {
        $channel = strtolower(trim($channel));
        if ($channel === '' || strlen($channel) > 64 || !preg_match('/^[a-z][a-z0-9_.-]*$/', $channel)) {
            throw new InvalidArgumentException('Invalid notification channel.');
        }

        return $channel;
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
}
