<?php
namespace Xdecaro\Component\Notifications\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class DashboardModel extends BaseDatabaseModel
{
    public function getStats(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'COUNT(*) AS total',
                "SUM(CASE WHEN " . $db->quoteName('state') . " = 'unread' THEN 1 ELSE 0 END) AS unread",
                "SUM(CASE WHEN " . $db->quoteName('priority') . " = 'critical' AND " . $db->quoteName('state') . " <> 'archived' THEN 1 ELSE 0 END) AS critical",
                "SUM(CASE WHEN " . $db->quoteName('state') . " = 'archived' THEN 1 ELSE 0 END) AS archived",
            ])
            ->from($db->quoteName('#__xdecaro_notifications'));

        $row = $db->setQuery($query)->loadAssoc() ?: [];

        return [
            'total'    => (int) ($row['total'] ?? 0),
            'unread'   => (int) ($row['unread'] ?? 0),
            'critical' => (int) ($row['critical'] ?? 0),
            'archived' => (int) ($row['archived'] ?? 0),
        ];
    }

    public function getRecent(int $limit = 8): array
    {
        $limit = max(1, min(25, $limit));
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'id', 'title', 'recipient_type', 'recipient_id', 'category',
                'priority', 'state', 'created', 'source_component',
            ])
            ->from($db->quoteName('#__xdecaro_notifications'))
            ->order($db->quoteName('created') . ' DESC');

        return (array) $db->setQuery($query, 0, $limit)->loadObjectList();
    }
}
