<?php
defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Xdecaro\Plugin\Task\Notifications\Extension\Notifications;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Notifications::class, static function (): Notifications {
                return new Notifications((array) PluginHelper::getPlugin('task', 'xdecaronotifications'));
            })
        );
    }
};
