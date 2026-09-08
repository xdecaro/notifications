<?php
namespace Xdecaro\Component\Notifications\Administrator\Event;

defined('_JEXEC') or die;

use Joomla\Event\Event;
use Xdecaro\Component\Notifications\Administrator\Service\ChannelRegistry;

final class RegisterChannelsEvent extends Event
{
    public const NAME = 'onXdecaroNotificationsRegisterChannels';

    public function __construct(ChannelRegistry $registry)
    {
        parent::__construct(self::NAME, ['subject' => $registry]);
    }

    public function getRegistry(): ChannelRegistry
    {
        return $this->getArgument('subject');
    }
}
