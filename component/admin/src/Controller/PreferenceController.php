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

final class PreferenceController extends BaseController
{
    public function save(): void
    {
        $this->assertAuthorized();
        $service = $this->getPreferenceService();

        $service->setPreference(
            $this->input->getCmd('recipient_type'),
            $this->input->getString('recipient_id'),
            $this->input->getString('category', '*'),
            $this->input->getString('channel'),
            $this->input->getInt('enabled', 1) === 1
        );

        $this->setRedirect(
            Route::_('index.php?option=com_xdecaronotifications&view=preferences', false),
            Text::_('COM_XDECARONOTIFICATIONS_PREFERENCE_SAVED')
        );
    }

    public function remove(): void
    {
        $this->assertAuthorized();
        $this->getPreferenceService()->removePreference(
            $this->input->getCmd('recipient_type'),
            $this->input->getString('recipient_id'),
            $this->input->getString('category', '*'),
            $this->input->getString('channel')
        );

        $this->setRedirect(
            Route::_('index.php?option=com_xdecaronotifications&view=preferences', false),
            Text::_('COM_XDECARONOTIFICATIONS_PREFERENCE_REMOVED')
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

    private function getPreferenceService()
    {
        $component = Factory::getApplication()->bootComponent('com_xdecaronotifications');
        if (!$component instanceof NotificationsComponent) {
            throw new RuntimeException('Notifications component service is unavailable.');
        }

        return $component->getPreferenceService();
    }
}
