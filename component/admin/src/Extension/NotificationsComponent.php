<?php
namespace Xdecaro\Component\Notifications\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use LogicException;
use Xdecaro\Component\Notifications\Administrator\Service\ChannelRegistry;
use Xdecaro\Component\Notifications\Administrator\Service\DeliveryChannelInterface;
use Xdecaro\Component\Notifications\Administrator\Service\DeliveryService;
use Xdecaro\Component\Notifications\Administrator\Service\NotificationService;
use Xdecaro\Component\Notifications\Administrator\Service\PreferenceService;

final class NotificationsComponent extends MVCComponent
{
    /** @var NotificationService|null */
    private $notificationService;

    /** @var PreferenceService|null */
    private $preferenceService;

    /** @var DeliveryService|null */
    private $deliveryService;

    /** @var ChannelRegistry|null */
    private $channelRegistry;

    public function setNotificationService(NotificationService $service): void
    {
        $this->notificationService = $service;
    }

    public function setPreferenceService(PreferenceService $service): void
    {
        $this->preferenceService = $service;
    }

    public function setDeliveryService(DeliveryService $service): void
    {
        $this->deliveryService = $service;
    }

    public function setChannelRegistry(ChannelRegistry $registry): void
    {
        $this->channelRegistry = $registry;
    }

    public function getNotificationService(): NotificationService
    {
        if ($this->notificationService === null) {
            throw new LogicException('Notifications service has not been initialized.');
        }

        return $this->notificationService;
    }

    public function getPreferenceService(): PreferenceService
    {
        if ($this->preferenceService === null) {
            throw new LogicException('Notification preference service has not been initialized.');
        }

        return $this->preferenceService;
    }

    public function getDeliveryService(): DeliveryService
    {
        if ($this->deliveryService === null) {
            throw new LogicException('Notification delivery service has not been initialized.');
        }

        return $this->deliveryService;
    }

    /**
     * Optional delivery integrations register adapters here instead of writing
     * Notifications tables directly.
     */
    public function registerDeliveryChannel(DeliveryChannelInterface $channel): void
    {
        $this->getChannelRegistry()->register($channel);
    }

    public function getChannelRegistry(): ChannelRegistry
    {
        if ($this->channelRegistry === null) {
            throw new LogicException('Notification channel registry has not been initialized.');
        }

        return $this->channelRegistry;
    }
}
