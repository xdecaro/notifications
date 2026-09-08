<?php
namespace Xdecaro\Component\Notifications\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Access\Exception\NotAllowed;
use RuntimeException;
use Xdecaro\Component\Notifications\Administrator\Extension\NotificationsComponent;

final class NotificationController extends BaseController
{
    public function markRead(): void
    {
        $this->assertAuthorized();
        $this->getNotificationService()->markRead($this->input->getInt('id'));
        $this->setRedirect(
            Route::_('index.php?option=com_xdecaronotifications&view=notifications', false),
            Text::_('COM_XDECARONOTIFICATIONS_MARKED_READ')
        );
    }

    public function archive(): void
    {
        $this->assertAuthorized();
        $this->getNotificationService()->archive($this->input->getInt('id'));
        $this->setRedirect(
            Route::_('index.php?option=com_xdecaronotifications&view=notifications', false),
            Text::_('COM_XDECARONOTIFICATIONS_ARCHIVED')
        );
    }

    private function assertAuthorized(): void
    {
        if (!Session::checkToken()) {
            throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        if (!Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_xdecaronotifications')) {
            throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }

    private function getNotificationService()
    {
        $component = Factory::getApplication()->bootComponent('com_xdecaronotifications');

        if (!$component instanceof NotificationsComponent) {
            throw new RuntimeException('Notifications component service is unavailable.');
        }

        return $component->getNotificationService();
    }
}
