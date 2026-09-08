<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

final class InAppChannel implements DeliveryChannelInterface
{
    public function getName(): string
    {
        return 'in_app';
    }

    public function deliver(array $notification, array $context = []): DeliveryResult
    {
        // Persistence of the notification item is the in-app delivery itself.
        // This adapter exists so the delivery lifecycle/status remains uniform.
        return DeliveryResult::delivered('notification:' . (string) ($notification['id'] ?? ''));
    }
}
