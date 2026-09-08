<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Throwable;
use Xdecaro\Component\Notifications\Administrator\Event\RegisterChannelsEvent;

final class ChannelDiscoveryService
{
    /** @var ChannelRegistry */
    private $registry;

    /** @var bool */
    private $discovered = false;

    public function __construct(ChannelRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function discover(): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discovered = true;

        try {
            PluginHelper::importPlugin('xdecaronotifications');
            $event = new RegisterChannelsEvent($this->registry);
            Factory::getApplication()->getDispatcher()->dispatch(RegisterChannelsEvent::NAME, $event);
        } catch (Throwable $exception) {
            Log::add('Notification channel discovery failed: ' . $exception->getMessage(), Log::WARNING, 'com_xdecaronotifications');
        }
    }
}
