<?php
defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Xdecaro\Plugin\Notifications\Email\Extension\Email;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Email::class, static function (Container $container): Email {
                return new Email(
                    (array) PluginHelper::getPlugin('xdecaronotifications', 'email'),
                    $container->get(UserFactoryInterface::class)
                );
            })
        );
    }
};
