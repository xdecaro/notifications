<?php
namespace Xdecaro\Component\Decaronotifications\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Xdecaro\Component\Decaronotifications\Administrator\Value\RecipientReference;

final class PreferenceService
{
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function isEnabled(RecipientReference $recipient, string $category, string $channel = 'internal', bool $default = true): bool
    {
        $recipientKey = $recipient->key();
        $category = $this->normalizeCategory($category);
        $channel = $this->normalizeChannel($channel);

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('enabled'))
            ->from($this->db->quoteName('#__decaronotifications_preferences'))
            ->where($this->db->quoteName('recipient_key') . ' = :recipient_key')
            ->where('(' . $this->db->quoteName('category') . ' = :category OR ' . $this->db->quoteName('category') . ' = ' . $this->db->quote('*') . ')')
            ->where($this->db->quoteName('channel') . ' = :channel')
            ->order('CASE WHEN ' . $this->db->quoteName('category') . ' = :category_exact THEN 0 ELSE 1 END')
            ->bind(':recipient_key', $recipientKey, ParameterType::STRING)
            ->bind(':category', $category, ParameterType::STRING)
            ->bind(':category_exact', $category, ParameterType::STRING)
            ->bind(':channel', $channel, ParameterType::STRING);

        $this->db->setQuery($query, 0, 1);
        $value = $this->db->loadResult();

        return $value === null ? $default : (bool) $value;
    }

    public function setEnabled(RecipientReference $recipient, string $category, string $channel, bool $enabled, string $digest = 'immediate'): void
    {
        $recipientKey = $recipient->key();
        $category = $category === '*' ? '*' : $this->normalizeCategory($category);
        $channel = $this->normalizeChannel($channel);
        $digest = strtolower(trim($digest));

        if (!in_array($digest, ['immediate', 'daily', 'weekly'], true)) {
            throw new \InvalidArgumentException('Invalid notification digest preference.');
        }

        $now = gmdate('Y-m-d H:i:s');
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__decaronotifications_preferences'))
            ->where($this->db->quoteName('recipient_key') . ' = :recipient_key')
            ->where($this->db->quoteName('category') . ' = :category')
            ->where($this->db->quoteName('channel') . ' = :channel')
            ->bind(':recipient_key', $recipientKey, ParameterType::STRING)
            ->bind(':category', $category, ParameterType::STRING)
            ->bind(':channel', $channel, ParameterType::STRING);
        $this->db->setQuery($query, 0, 1);
        $id = (int) $this->db->loadResult();

        $row = (object) [
            'recipient_key' => $recipientKey,
            'category' => $category,
            'channel' => $channel,
            'enabled' => $enabled ? 1 : 0,
            'digest' => $digest,
            'modified' => $now,
        ];

        if ($id > 0) {
            $row->id = $id;
            $this->db->updateObject('#__decaronotifications_preferences', $row, 'id');
            return;
        }

        $row->created = $now;
        $this->db->insertObject('#__decaronotifications_preferences', $row, 'id');
    }

    /** @return array<int,object> */
    public function getPreferences(RecipientReference $recipient): array
    {
        $recipientKey = $recipient->key();
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__decaronotifications_preferences'))
            ->where($this->db->quoteName('recipient_key') . ' = :recipient_key')
            ->order($this->db->quoteName('category') . ' ASC, ' . $this->db->quoteName('channel') . ' ASC')
            ->bind(':recipient_key', $recipientKey, ParameterType::STRING);
        $this->db->setQuery($query);

        return (array) $this->db->loadObjectList();
    }

    private function normalizeCategory(string $category): string
    {
        $category = strtolower(trim($category));
        if (!preg_match('/^[a-z][a-z0-9_.-]{0,63}$/', $category)) {
            throw new \InvalidArgumentException('Invalid notification category.');
        }
        return $category;
    }

    private function normalizeChannel(string $channel): string
    {
        $channel = strtolower(trim($channel));
        if (!preg_match('/^[a-z][a-z0-9_.-]{0,31}$/', $channel)) {
            throw new \InvalidArgumentException('Invalid notification channel.');
        }
        return $channel;
    }
}
