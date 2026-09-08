<?php
namespace Xdecaro\Component\Decaronotifications\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Xdecaro\Component\Decaronotifications\Administrator\Value\RecipientReference;

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
        return (!$user || $user->guest) ? 0 : $this->component()->getNotificationService()->countUnreadForUser((int) $user->id);
    }

    /** @return array{enabled:bool,digest:string} */
    public function getGlobalPreference(): array
    {
        $user = Factory::getApplication()->getIdentity();
        if (!$user || $user->guest) {
            return ['enabled' => true, 'digest' => 'immediate'];
        }
        $preferences = $this->component()->getPreferenceService()->getPreferences(RecipientReference::forUser((int) $user->id));
        foreach ($preferences as $preference) {
            if ($preference->category === '*' && $preference->channel === 'internal') {
                return ['enabled' => (bool) $preference->enabled, 'digest' => (string) $preference->digest];
            }
        }
        return ['enabled' => true, 'digest' => 'immediate'];
    }

    private function component()
    {
        return Factory::getApplication()->bootComponent('com_decaronotifications');
    }
}
