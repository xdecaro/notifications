<?php
namespace Xdecaro\Component\Decaronotifications\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use RuntimeException;

final class NotificationModel extends BaseDatabaseModel
{
    public function getItem(): ?object
    {
        $id = Factory::getApplication()->input->getInt('id');
        return $this->service()->getById($id);
    }

    /** @return array<int,object> */
    public function getRecipients(): array
    {
        $id = Factory::getApplication()->input->getInt('id');
        return $id > 0 ? $this->service()->getRecipients($id) : [];
    }

    private function service()
    {
        $component = Factory::getApplication()->bootComponent('com_decaronotifications');
        if (!method_exists($component, 'getNotificationService')) {
            throw new RuntimeException('Notifications service is unavailable.');
        }
        return $component->getNotificationService();
    }
}
