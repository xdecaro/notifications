<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

final class InAppChannel implements IdempotentDeliveryChannelInterface
{
    public function getName(): string
    {
        return 'in_app';
    }

    public function deliver(array $notification, array $context = []): DeliveryResult
    {
        // Persistence of the notification item is the in-app delivery itself.
        // Reprocessing the same delivery is intrinsically idempotent because no
        // external side effect is performed by this adapter.
        return DeliveryResult::delivered('notification:' . (string) ($notification['id'] ?? ''));
    }
}
