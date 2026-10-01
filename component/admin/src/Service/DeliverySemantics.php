<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;

final class DeliverySemantics
{
    public const REQUEST_ACCEPTED = 'request_accepted';
    public const SUBMITTED = 'submitted';
    public const DELIVERED = 'delivered';
    public const FAILED = 'failed';
    public const OUTCOME_UNKNOWN = 'outcome_unknown';

    public static function publicStatus(string $state): string
    {
        $state = strtolower(trim($state));

        if (in_array($state, ['pending', 'processing', 'retry'], true)) {
            return self::REQUEST_ACCEPTED;
        }

        if (in_array($state, [self::SUBMITTED, self::DELIVERED, self::FAILED, self::OUTCOME_UNKNOWN], true)) {
            return $state;
        }

        throw new InvalidArgumentException('Unknown delivery state.');
    }

    public static function canSafelyRetryUnknown(DeliveryChannelInterface $channel): bool
    {
        return $channel instanceof IdempotentDeliveryChannelInterface;
    }

    public static function idempotencyKey(int $deliveryId, string $channel): string
    {
        if ($deliveryId < 1) {
            throw new InvalidArgumentException('Invalid delivery ID.');
        }

        $channel = strtolower(trim($channel));
        if ($channel === '' || !preg_match('/^[a-z][a-z0-9_.-]{0,63}$/', $channel)) {
            throw new InvalidArgumentException('Invalid notification channel.');
        }

        return 'xdecaronotifications:' . $deliveryId . ':' . $channel;
    }
}
