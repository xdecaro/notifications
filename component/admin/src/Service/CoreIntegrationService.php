<?php
namespace xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use xdecaro\Core\Integration\Capability;
use xdecaro\Core\Integration\EntityReference;

final class CoreIntegrationService
{
    public function isAvailable(): bool
    {
        return class_exists(Capability::class) && class_exists(EntityReference::class);
    }

    /** @return array<int,Capability> */
    public function getCapabilities(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        return [
            new Capability('com_xdecaronotifications', 'notifications.publish', '1'),
            new Capability('com_xdecaronotifications', 'notifications.preferences', '1'),
            new Capability('com_xdecaronotifications', 'notifications.delivery_status', '1'),
        ];
    }

    /** @param int|string $id */
    public function notificationReference($id): ?EntityReference
    {
        return $this->isAvailable()
            ? new EntityReference('com_xdecaronotifications', 'notification', $id)
            : null;
    }
}
