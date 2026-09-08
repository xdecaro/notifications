<?php
namespace Xdecaro\Component\Notifications\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;

final class NotificationsModel extends ListModel
{
    protected function populateState($ordering = 'n.created', $direction = 'DESC'): void
    {
        $app = $this->getApplication();

        $this->setState(
            'filter.search',
            $app->getUserStateFromRequest('com_xdecaronotifications.notifications.filter.search', 'filter_search', '', 'string')
        );
        $this->setState(
            'filter.state',
            $app->getUserStateFromRequest('com_xdecaronotifications.notifications.filter.state', 'filter_state', '', 'cmd')
        );
        $this->setState(
            'filter.priority',
            $app->getUserStateFromRequest('com_xdecaronotifications.notifications.filter.priority', 'filter_priority', '', 'cmd')
        );
        $this->setState(
            'filter.category',
            $app->getUserStateFromRequest('com_xdecaronotifications.notifications.filter.category', 'filter_category', '', 'string')
        );

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery(): DatabaseQuery
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'n.id', 'n.source_component', 'n.source_entity', 'n.source_id',
                'n.recipient_type', 'n.recipient_id', 'n.category', 'n.priority',
                'n.title', 'n.message', 'n.action_url', 'n.state', 'n.created',
                'n.read_at', 'n.archived_at', 'n.expires_at',
            ])
            ->from($db->quoteName('#__xdecaro_notifications', 'n'));

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $like = '%' . $db->escape($search, true) . '%';
            $query->where('(' . implode(' OR ', [
                $db->quoteName('n.title') . ' LIKE :search_title',
                $db->quoteName('n.message') . ' LIKE :search_message',
                $db->quoteName('n.recipient_id') . ' LIKE :search_recipient',
                $db->quoteName('n.source_id') . ' LIKE :search_source',
            ]) . ')')
                ->bind(':search_title', $like)
                ->bind(':search_message', $like)
                ->bind(':search_recipient', $like)
                ->bind(':search_source', $like);
        }

        $state = (string) $this->getState('filter.state');
        if (in_array($state, ['unread', 'read', 'archived'], true)) {
            $query->where($db->quoteName('n.state') . ' = :filter_state')
                ->bind(':filter_state', $state);
        }

        $priority = (string) $this->getState('filter.priority');
        if (in_array($priority, ['low', 'normal', 'high', 'critical'], true)) {
            $query->where($db->quoteName('n.priority') . ' = :filter_priority')
                ->bind(':filter_priority', $priority);
        }

        $category = trim((string) $this->getState('filter.category'));
        if ($category !== '') {
            $query->where($db->quoteName('n.category') . ' = :filter_category')
                ->bind(':filter_category', $category);
        }

        $orderCol = (string) $this->getState('list.ordering', 'n.created');
        $orderDir = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $allowedOrder = ['n.id', 'n.created', 'n.priority', 'n.state', 'n.category'];
        if (!in_array($orderCol, $allowedOrder, true)) {
            $orderCol = 'n.created';
        }

        $query->order($orderCol . ' ' . $orderDir);

        return $query;
    }
}
