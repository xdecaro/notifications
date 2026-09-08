<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Xdecaro\Component\Notifications\Administrator\Value\RecipientReference;

final class PreferenceService
{
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function isInternalEnabled(RecipientReference $recipient, string $category, bool $default = true): bool
    {
        $recipientKey = $recipient->key();
        $category = $this->normalizeCategory($category);

        $query = $this->db->getQuery(true)
            ->select([$this->db->quoteName('enabled'), $this->db->quoteName('category')])
            ->from($this->db->quoteName('#__xdecaronotifications_preferences'))
            ->where($this->db->quoteName('recipient_key') . ' = :recipient_key')
            ->where('(' . $this->db->quoteName('category') . ' = :category OR ' . $this->db->quoteName('category') . ' = ' . $this->db->quote('*') . ')')
            ->where($this->db->quoteName('channel') . ' = ' . $this->db->quote('internal'))
            ->order('CASE WHEN ' . $this->db->quoteName('category') . ' = :exact_category THEN 0 ELSE 1 END')
            ->bind(':recipient_key', $recipientKey, ParameterType::STRING)
            ->bind(':category', $category, ParameterType::STRING)
            ->bind(':exact_category', $category, ParameterType::STRING);

        $this->db->setQuery($query, 0, 1);
        $row = $this->db->loadAssoc();

        return $row === null ? $default : (bool) $row['enabled'];
    }

    public function setInternalEnabled(RecipientReference $recipient, string $category, bool $enabled): void
    {
        $recipientKey = $recipient->key();
        $category = $category === '*' ? '*' : $this->normalizeCategory($category);
        $now = gmdate('Y-m-d H:i:s');

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__xdecaronotifications_preferences'))
            ->where($this->db->quoteName('recipient_key') . ' = :recipient_key')
            ->where($this->db->quoteName('category') . ' = :category')
            ->where($this->db->quoteName('channel') . ' = ' . $this->db->quote('internal'))
            ->bind(':recipient_key', $recipientKey, ParameterType::STRING)
            ->bind(':category', $category, ParameterType::STRING);
        $this->db->setQuery($query, 0, 1);
        $id = (int) $this->db->loadResult();

        $row = (object) [
            'recipient_key' => $recipientKey,
            'category' => $category,
            'channel' => 'internal',
            'enabled' => $enabled ? 1 : 0,
            'digest' => 'immediate',
            'modified' => $now,
        ];

        if ($id > 0) {
            $row->id = $id;
            $this->db->updateObject('#__xdecaronotifications_preferences', $row, 'id');
            return;
        }

        $row->created = $now;
        $this->db->insertObject('#__xdecaronotifications_preferences', $row, 'id');
    }

    /** @return array<int,object> */
    public function getPreferences(RecipientReference $recipient): array
    {
        $recipientKey = $recipient->key();
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__xdecaronotifications_preferences'))
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
            throw new InvalidArgumentException('Invalid notification category.');
        }
        return $category;
    }
}
