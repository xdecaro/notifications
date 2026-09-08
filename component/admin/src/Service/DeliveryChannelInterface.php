<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

interface DeliveryChannelInterface
{
    public function getName(): string;

    /**
     * Deliver one notification through this channel.
     *
     * Implementations must not mutate Notifications tables directly. Return a
     * DeliveryResult and let DeliveryService own persistence and retry state.
     *
     * @param array<string,mixed> $notification
     * @param array<string,mixed> $context
     */
    public function deliver(array $notification, array $context = []): DeliveryResult;
}
