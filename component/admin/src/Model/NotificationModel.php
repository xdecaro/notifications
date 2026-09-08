<?php
namespace Xdecaro\Component\Notifications\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use RuntimeException;
use Xdecaro\Component\Notifications\Administrator\Service\NotificationService;

final class NotificationModel extends BaseDatabaseModel
{
    public function getItem(): ?object
    {
        return $this->service()->getById(Factory::getApplication()->input->getInt('id'));
    }

    /** @return array<int,object> */
    public function getRecipients(): array
    {
        $id = Factory::getApplication()->input->getInt('id');
        return $id > 0 ? $this->service()->getRecipients($id) : [];
    }

    private function service(): NotificationService
    {
        $component = Factory::getApplication()->bootComponent('com_xdecaronotifications');
        if (!method_exists($component, 'getNotificationService')) {
            throw new RuntimeException('Notifications service is unavailable.');
        }
        return $component->getNotificationService();
    }
}
