<?php
namespace Xdecaro\Component\Notifications\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;

final class PreferencesModel extends ListModel
{
    protected function populateState($ordering = 'p.modified', $direction = 'DESC'): void
    {
        $app = $this->getApplication();
        $this->setState('filter.search', $app->getUserStateFromRequest('com_xdecaronotifications.preferences.search', 'filter_search', '', 'string'));
        $this->setState('filter.channel', $app->getUserStateFromRequest('com_xdecaronotifications.preferences.channel', 'filter_channel', '', 'cmd'));
        parent::populateState($ordering, $direction);
    }

    protected function getListQuery(): DatabaseQuery
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'p.id', 'p.recipient_type', 'p.recipient_id', 'p.category',
                'p.channel', 'p.enabled', 'p.modified', 'p.modified_by',
            ])
            ->from($db->quoteName('#__xdecaronotifications_preferences', 'p'));

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $like = '%' . $db->escape($search, true) . '%';
            $query->where('(' . implode(' OR ', [
                $db->quoteName('p.recipient_id') . ' LIKE :search_recipient',
                $db->quoteName('p.recipient_type') . ' LIKE :search_type',
                $db->quoteName('p.category') . ' LIKE :search_category',
            ]) . ')')
                ->bind(':search_recipient', $like)
                ->bind(':search_type', $like)
                ->bind(':search_category', $like);
        }

        $channel = strtolower(trim((string) $this->getState('filter.channel')));
        if ($channel !== '' && preg_match('/^[a-z][a-z0-9_.-]{0,63}$/', $channel)) {
            $query->where($db->quoteName('p.channel') . ' = :filter_channel')->bind(':filter_channel', $channel);
        }

        $orderCol = (string) $this->getState('list.ordering', 'p.modified');
        $orderDir = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $allowed = ['p.id', 'p.modified', 'p.recipient_type', 'p.recipient_id', 'p.category', 'p.channel', 'p.enabled'];
        if (!in_array($orderCol, $allowed, true)) {
            $orderCol = 'p.modified';
        }

        return $query->order($orderCol . ' ' . $orderDir);
    }
}
