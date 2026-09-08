<?php
namespace Xdecaro\Component\Decaronotifications\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Xdecaro\Component\Decaronotifications\Administrator\Service\NotificationService;
use Xdecaro\Component\Decaronotifications\Administrator\Service\PreferenceService;

final class DecaronotificationsComponent extends MVCComponent
{
    private NotificationService $notificationService;
    private PreferenceService $preferenceService;

    public function __construct(
        ComponentDispatcherFactoryInterface $dispatcherFactory,
        MVCFactoryInterface $mvcFactory,
        NotificationService $notificationService,
        PreferenceService $preferenceService
    ) {
        parent::__construct($dispatcherFactory, $mvcFactory);
        $this->notificationService = $notificationService;
        $this->preferenceService = $preferenceService;
    }

    public function getNotificationService(): NotificationService
    {
        return $this->notificationService;
    }

    public function getPreferenceService(): PreferenceService
    {
        return $this->preferenceService;
    }
}
