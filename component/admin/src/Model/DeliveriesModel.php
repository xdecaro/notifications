<?php
namespace Xdecaro\Component\Notifications\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;

final class DeliveriesModel extends ListModel
{
    protected function populateState($ordering = 'd.updated', $direction = 'DESC'): void
    {
        $app = $this->getApplication();
        $this->setState('filter.search', $app->getUserStateFromRequest('com_xdecaronotifications.deliveries.search', 'filter_search', '', 'string'));
        $this->setState('filter.state', $app->getUserStateFromRequest('com_xdecaronotifications.deliveries.state', 'filter_state', '', 'cmd'));
        $this->setState('filter.channel', $app->getUserStateFromRequest('com_xdecaronotifications.deliveries.channel', 'filter_channel', '', 'cmd'));
        parent::populateState($ordering, $direction);
    }

    protected function getListQuery(): DatabaseQuery
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'd.id', 'd.notification_id', 'd.channel', 'd.state', 'd.attempts',
                'd.available_at', 'd.updated', 'd.delivered_at', 'd.provider_reference',
                'd.last_error', 'n.title', 'n.recipient_type', 'n.recipient_id',
                'n.category', 'n.priority', 'n.source_component', 'n.source_entity', 'n.source_id',
            ])
            ->from($db->quoteName('#__xdecaronotifications_deliveries', 'd'))
            ->leftJoin(
                $db->quoteName('#__xdecaronotifications_items', 'n') .
                ' ON ' . $db->quoteName('n.id') . ' = ' . $db->quoteName('d.notification_id')
            );

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $like = '%' . $db->escape($search, true) . '%';
            $query->where('(' . implode(' OR ', [
                $db->quoteName('n.title') . ' LIKE :search_title',
                $db->quoteName('n.recipient_id') . ' LIKE :search_recipient',
                $db->quoteName('n.source_id') . ' LIKE :search_source',
                $db->quoteName('d.last_error') . ' LIKE :search_error',
            ]) . ')')
                ->bind(':search_title', $like)
                ->bind(':search_recipient', $like)
                ->bind(':search_source', $like)
                ->bind(':search_error', $like);
        }

        $state = (string) $this->getState('filter.state');
        if (in_array($state, ['pending', 'processing', 'retry', 'delivered', 'failed'], true)) {
            $query->where($db->quoteName('d.state') . ' = :filter_state')->bind(':filter_state', $state);
        }

        $channel = strtolower(trim((string) $this->getState('filter.channel')));
        if ($channel !== '' && preg_match('/^[a-z][a-z0-9_.-]{0,63}$/', $channel)) {
            $query->where($db->quoteName('d.channel') . ' = :filter_channel')->bind(':filter_channel', $channel);
        }

        $orderCol = (string) $this->getState('list.ordering', 'd.updated');
        $orderDir = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $allowed = ['d.id', 'd.updated', 'd.state', 'd.channel', 'd.attempts', 'd.available_at'];
        if (!in_array($orderCol, $allowed, true)) {
            $orderCol = 'd.updated';
        }

        return $query->order($orderCol . ' ' . $orderDir);
    }
}
