<?php
namespace Xdecaro\Component\Decaronotifications\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Xdecaro\Component\Decaronotifications\Administrator\Extension\DecaronotificationsComponent;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('Xdecaro\\Component\\Decaronotifications'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('Xdecaro\\Component\\Decaronotifications'));

        $container->share(
            PreferenceService::class,
            static fn (Container $container): PreferenceService => new PreferenceService($container->get(DatabaseInterface::class))
        );

        $container->share(
            NotificationService::class,
            static fn (Container $container): NotificationService => new NotificationService(
                $container->get(DatabaseInterface::class),
                $container->get(PreferenceService::class)
            )
        );

        $container->share(CoreIntegrationService::class, static fn (): CoreIntegrationService => new CoreIntegrationService());

        $container->set(
            ComponentInterface::class,
            static fn (Container $container): ComponentInterface => new DecaronotificationsComponent(
                $container->get(ComponentDispatcherFactoryInterface::class),
                $container->get(MVCFactoryInterface::class),
                $container->get(NotificationService::class),
                $container->get(PreferenceService::class)
            )
        );
    }
};
