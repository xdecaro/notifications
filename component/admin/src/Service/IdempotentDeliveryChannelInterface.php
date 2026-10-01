<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

/**
 * Marker for channels that guarantee provider-side idempotency when
 * DeliveryService supplies context['idempotency_key'].
 *
 * Implementations must use that key unchanged for every external attempt of
 * the same delivery. This contract is specific to Notifications and does not
 * introduce a generic Core caller/authorization abstraction.
 */
interface IdempotentDeliveryChannelInterface extends DeliveryChannelInterface
{
}
