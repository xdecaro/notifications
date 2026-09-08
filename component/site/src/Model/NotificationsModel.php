<?php
namespace Xdecaro\Component\Notifications\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Xdecaro\Component\Notifications\Administrator\Value\RecipientReference;

final class NotificationsModel extends BaseDatabaseModel
{
    /** @return array<int,object> */
    public function getItems(): array
    {
        $user = Factory::getApplication()->getIdentity();
        if (!$user || $user->guest) {
            return [];
        }
        return $this->component()->getNotificationService()->getForUser((int) $user->id, 100);
    }

    public function getUnreadCount(): int
    {
        $user = Factory::getApplication()->getIdentity();
        return (!$user || $user->guest)
            ? 0
            : $this->component()->getNotificationService()->countUnreadForUser((int) $user->id);
    }

    public function isInternalEnabled(): bool
    {
        $user = Factory::getApplication()->getIdentity();
        if (!$user || $user->guest) {
            return true;
        }

        return $this->component()->getPreferenceService()->isInternalEnabled(
            RecipientReference::forUser((int) $user->id),
            'general',
            true
        );
    }

    private function component()
    {
        return Factory::getApplication()->bootComponent('com_xdecaronotifications');
    }
}
