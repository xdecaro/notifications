<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Xdecaro\Component\Notifications\Administrator\Extension\NotificationsComponent;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('Xdecaro\\Component\\Notifications'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('Xdecaro\\Component\\Notifications'));

        $container->share(CoreIntegrationService::class, static fn (): CoreIntegrationService => new CoreIntegrationService());
        $container->share(PreferenceService::class, static fn (Container $container): PreferenceService => new PreferenceService($container->get(DatabaseInterface::class)));
        $container->share(ChannelRegistry::class, static function (): ChannelRegistry {
            $registry = new ChannelRegistry();
            $registry->register(new InAppChannel());
            return $registry;
        });
        $container->share(ChannelDiscoveryService::class, static fn (Container $container): ChannelDiscoveryService => new ChannelDiscoveryService($container->get(ChannelRegistry::class)));
        $container->share(DeliveryService::class, static fn (Container $container): DeliveryService => new DeliveryService($container->get(DatabaseInterface::class), $container->get(PreferenceService::class), $container->get(ChannelRegistry::class)));
        $container->share(NotificationService::class, static fn (Container $container): NotificationService => new NotificationService($container->get(DatabaseInterface::class)));
        $container->share(MaintenanceService::class, static fn (Container $container): MaintenanceService => new MaintenanceService($container->get(DatabaseInterface::class)));

        $container->set(ComponentInterface::class, static function (Container $container): ComponentInterface {
            $component = new NotificationsComponent($container->get(ComponentDispatcherFactoryInterface::class), $container->get(MVCFactoryInterface::class));
            $component->setNotificationService($container->get(NotificationService::class));
            $component->setPreferenceService($container->get(PreferenceService::class));
            $component->setDeliveryService($container->get(DeliveryService::class));
            $component->setChannelRegistry($container->get(ChannelRegistry::class));
            $component->setMaintenanceService($container->get(MaintenanceService::class));
            $container->get(ChannelDiscoveryService::class)->discover();
            return $component;
        });
    }
};
