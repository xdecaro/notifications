<?php
namespace Xdecaro\Component\Notifications\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Xdecaro\Component\Notifications\Administrator\Value\RecipientReference;

final class PreferencesController extends BaseController
{
    public function save(): void
    {
        Session::checkToken('post') or jexit(Text::_('JINVALID_TOKEN'));
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user || $user->guest) {
            $app->redirect(Route::_('index.php?option=com_users&view=login', false));
            return;
        }

        $enabled = $app->input->post->getInt('enabled', 0) === 1;
        $component = $app->bootComponent('com_xdecaronotifications');

        if (!method_exists($component, 'getPreferenceService')) {
            $app->enqueueMessage(Text::_('COM_XDECARONOTIFICATIONS_SERVICE_UNAVAILABLE'), 'error');
            $app->redirect(Route::_('index.php?option=com_xdecaronotifications&view=notifications', false));
            return;
        }

        $component->getPreferenceService()->setInternalEnabled(
            RecipientReference::forUser((int) $user->id),
            '*',
            $enabled
        );

        $app->enqueueMessage(Text::_('COM_XDECARONOTIFICATIONS_PREFERENCES_SAVED'), 'message');
        $app->redirect(Route::_('index.php?option=com_xdecaronotifications&view=notifications', false));
    }
}
