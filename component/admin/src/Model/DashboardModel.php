<?php
namespace Xdecaro\Component\Decaronotifications\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use RuntimeException;
use Xdecaro\Component\Decaronotifications\Administrator\Service\NotificationService;

final class DashboardModel extends BaseDatabaseModel
{
    /** @return array<string,int> */
    public function getStats(): array
    {
        return $this->service()->getAdminStats();
    }

    /** @return array<int,object> */
    public function getRecent(): array
    {
        return $this->service()->getAdminRecent(10);
    }

    private function service(): NotificationService
    {
        $component = Factory::getApplication()->bootComponent('com_decaronotifications');
        if (!method_exists($component, 'getNotificationService')) {
            throw new RuntimeException('Notifications service is unavailable.');
        }
        return $component->getNotificationService();
    }
}
