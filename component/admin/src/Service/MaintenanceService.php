<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class MaintenanceService
{
    /** @var DatabaseInterface */
    private $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function archiveExpired(): int
    {
        $now = Factory::getDate()->toSql();
        $archivedState = 'archived';
        $guardState = 'archived';

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__xdecaronotifications_items'))
            ->set($this->db->quoteName('state') . ' = :archived_state')
            ->set($this->db->quoteName('archived_at') . ' = COALESCE(' . $this->db->quoteName('archived_at') . ', :archived_at)')
            ->where($this->db->quoteName('expires_at') . ' IS NOT NULL')
            ->where($this->db->quoteName('expires_at') . ' < :now')
            ->where($this->db->quoteName('state') . ' <> :guard_state')
            ->bind(':archived_state', $archivedState)
            ->bind(':archived_at', $now)
            ->bind(':now', $now)
            ->bind(':guard_state', $guardState);

        $this->db->setQuery($query)->execute();

        return (int) $this->db->getAffectedRows();
    }

    public function purgeDeliveryAttempts(int $retentionDays = 90): int
    {
        $retentionDays = max(7, min(3650, $retentionDays));
        $cutoff = Factory::getDate('-' . $retentionDays . ' days')->toSql();

        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__xdecaronotifications_delivery_attempts'))
            ->where($this->db->quoteName('created') . ' < :cutoff')
            ->bind(':cutoff', $cutoff);

        $this->db->setQuery($query)->execute();

        return (int) $this->db->getAffectedRows();
    }

    /** @return array{archived_expired:int,purged_attempts:int} */
    public function run(bool $archiveExpired = true, int $retentionDays = 90): array
    {
        return [
            'archived_expired' => $archiveExpired ? $this->archiveExpired() : 0,
            'purged_attempts' => $this->purgeDeliveryAttempts($retentionDays),
        ];
    }
}
