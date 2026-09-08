<?php
namespace Xdecaro\Component\Decaronotifications\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class NotificationController extends BaseController
{
    public function read(): void
    {
        $this->changeState('read');
    }

    public function unread(): void
    {
        $this->changeState('unread');
    }

    public function archive(): void
    {
        $this->changeState('archive');
    }

    private function changeState(string $action): void
    {
        Session::checkToken('post') or jexit(Text::_('JINVALID_TOKEN'));
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $id = $app->input->post->getInt('id');

        if (!$user || $user->guest || $id <= 0) {
            $app->enqueueMessage(Text::_('COM_DECARONOTIFICATIONS_ACTION_DENIED'), 'warning');
            $app->redirect(Route::_('index.php?option=com_decaronotifications&view=notifications', false));
            return;
        }

        $component = $app->bootComponent('com_decaronotifications');
        if (!method_exists($component, 'getNotificationService')) {
            $app->enqueueMessage(Text::_('COM_DECARONOTIFICATIONS_SERVICE_UNAVAILABLE'), 'error');
            $app->redirect(Route::_('index.php?option=com_decaronotifications&view=notifications', false));
            return;
        }

        $service = $component->getNotificationService();
        if ($action === 'read') {
            $service->markReadForUser($id, (int) $user->id, true);
        } elseif ($action === 'unread') {
            $service->markReadForUser($id, (int) $user->id, false);
        } else {
            $service->archiveForUser($id, (int) $user->id, true);
        }

        $app->redirect(Route::_('index.php?option=com_decaronotifications&view=notifications', false));
    }
}
