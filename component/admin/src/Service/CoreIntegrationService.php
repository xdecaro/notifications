<?php
namespace Xdecaro\Component\Decaronotifications\Administrator\Service;

defined('_JEXEC') or die;

use Xdecaro\Core\Integration\Capability;
use Xdecaro\Core\Integration\EntityReference;
use Xdecaro\Core\Integration\IntegrationEvent;

final class CoreIntegrationService
{
    public const MINIMUM_CORE_VERSION = '1.2.0';

    public function isAvailable(): bool
    {
        return class_exists(Capability::class)
            && class_exists(EntityReference::class)
            && class_exists(IntegrationEvent::class);
    }

    /** @return array<int,Capability> */
    public function getCapabilities(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        return [
            new Capability('com_decaronotifications', 'notifications.publish', '1'),
            new Capability('com_decaronotifications', 'notifications.preferences', '1'),
            new Capability('com_decaronotifications', 'notifications.delivery_status', '1'),
        ];
    }

    /** @param int|string $id */
    public function notificationReference($id): ?EntityReference
    {
        return $this->isAvailable()
            ? new EntityReference('com_decaronotifications', 'notification', $id)
            : null;
    }
}
