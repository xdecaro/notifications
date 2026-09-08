<?php
namespace Xdecaro\Component\Notifications\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use LogicException;
use Xdecaro\Component\Notifications\Administrator\Service\NotificationService;

final class NotificationsComponent extends MVCComponent
{
    /** @var NotificationService|null */
    private $notificationService;

    public function setNotificationService(NotificationService $service): void
    {
        $this->notificationService = $service;
    }

    /**
     * Public component API for optional xdecaro consumers.
     *
     * Consumers should boot `com_xdecaronotifications` through Joomla instead
     * of instantiating this service or querying Notifications tables directly.
     */
    public function getNotificationService(): NotificationService
    {
        if ($this->notificationService === null) {
            throw new LogicException('Notifications service has not been initialized.');
        }

        return $this->notificationService;
    }
}
