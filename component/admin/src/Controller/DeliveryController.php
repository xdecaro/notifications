<?php
namespace Xdecaro\Component\Notifications\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use RuntimeException;
use Xdecaro\Component\Notifications\Administrator\Extension\NotificationsComponent;

final class DeliveryController extends BaseController
{
    public function process(): void
    {
        if (!Session::checkToken()) {
            throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_xdecaronotifications')) {
            throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $component = Factory::getApplication()->bootComponent('com_xdecaronotifications');
        if (!$component instanceof NotificationsComponent) {
            throw new RuntimeException('Notifications component service is unavailable.');
        }

        $stats = $component->getDeliveryService()->processPending(50, 5);
        $message = Text::sprintf(
            'COM_XDECARONOTIFICATIONS_QUEUE_RESULT',
            $stats['processed'],
            $stats['delivered'],
            $stats['failed'],
            $stats['missing_adapter'],
            $stats['skipped']
        );

        $this->setRedirect(
            Route::_('index.php?option=com_xdecaronotifications&view=deliveries', false),
            $message
        );
    }
}
