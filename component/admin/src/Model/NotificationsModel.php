<?php
namespace Xdecaro\Component\Notifications\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use RuntimeException;

final class NotificationsModel extends BaseDatabaseModel
{
    /** @return array<int,object> */
    public function getItems(): array
    {
        $component = Factory::getApplication()->bootComponent('com_xdecaronotifications');
        if (!method_exists($component, 'getNotificationService')) {
            throw new RuntimeException('Notifications service is unavailable.');
        }
        return $component->getNotificationService()->getAdminRecent(100);
    }
}
