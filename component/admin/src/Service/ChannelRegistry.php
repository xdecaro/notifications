<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;

final class ChannelRegistry
{
    /** @var array<string,DeliveryChannelInterface> */
    private $channels = [];

    public function register(DeliveryChannelInterface $channel): void
    {
        $name = strtolower(trim($channel->getName()));

        if ($name === '' || strlen($name) > 64 || !preg_match('/^[a-z][a-z0-9_.-]*$/', $name)) {
            throw new InvalidArgumentException('Invalid delivery channel name.');
        }

        $this->channels[$name] = $channel;
    }

    public function has(string $name): bool
    {
        return isset($this->channels[strtolower(trim($name))]);
    }

    public function get(string $name): ?DeliveryChannelInterface
    {
        $name = strtolower(trim($name));

        return $this->channels[$name] ?? null;
    }

    /** @return array<int,string> */
    public function getNames(): array
    {
        $names = array_keys($this->channels);
        sort($names, SORT_STRING);

        return $names;
    }
}
