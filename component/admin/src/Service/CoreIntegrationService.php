<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use Xdecaro\Core\Integration\Capability;
use Xdecaro\Core\Integration\EntityReference;
use Xdecaro\Core\Integration\IntegrationEvent;

final class CoreIntegrationService
{
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
            new Capability('com_xdecaronotifications', 'notifications.publish', '1'),
            new Capability('com_xdecaronotifications', 'notifications.state', '1'),
            new Capability('com_xdecaronotifications', 'notifications.unread_count', '1'),
        ];
    }

    /** @param int|string $id */
    public function notificationReference($id): ?EntityReference
    {
        return $this->isAvailable()
            ? new EntityReference('com_xdecaronotifications', 'notification', $id)
            : null;
    }

    /**
     * Adapt a Core IntegrationEvent into a persisted notification.
     *
     * Delivery is intentionally explicit: Core does not dispatch or persist the
     * event and this method does not run automatically on every Joomla request.
     */
    public function createFromEvent(
        NotificationService $notifications,
        IntegrationEvent $event,
        array $notification
    ): int {
        $source = $event->getSource();

        if ($source !== null) {
            $notification['source_component'] = $source->getComponent();
            $notification['source_entity']    = $source->getEntity();
            $notification['source_id']        = $source->getId();
        }

        $payload = isset($notification['payload']) && is_array($notification['payload'])
            ? $notification['payload']
            : [];
        $payload['_integration_event'] = $event->toArray();
        $notification['payload'] = $payload;

        return $notifications->create($notification);
    }
}
