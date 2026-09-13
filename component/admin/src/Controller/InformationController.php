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

final class InformationController extends BaseController
{
    public function sendTestNotification(): void
    {
        $this->assertAuthorized();

        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $userId = (int) ($user->id ?? 0);

        if ($userId < 1) {
            throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $component = $this->getNotificationsComponent();
        $externalKey = sprintf(
            'diagnostic-test:%d:%s:%s',
            $userId,
            Factory::getDate()->format('YmdHis'),
            bin2hex(random_bytes(4))
        );

        $notificationId = $component->getNotificationService()->create([
            'external_key' => $externalKey,
            'source_component' => 'com_xdecaronotifications',
            'source_entity' => 'diagnostic',
            'source_id' => (string) $userId,
            'recipient_type' => 'user',
            'recipient_id' => (string) $userId,
            'category' => 'diagnostic',
            'priority' => 'normal',
            'title' => Text::_('COM_XDECARONOTIFICATIONS_TEST_NOTIFICATION_TITLE'),
            'message' => Text::_('COM_XDECARONOTIFICATIONS_TEST_NOTIFICATION_MESSAGE'),
            'created_by' => $userId,
        ]);

        $component->getDeliveryService()->queueForNotification($notificationId, ['in_app']);

        $this->setRedirect(
            Route::_('index.php?option=com_xdecaronotifications&view=information', false),
            Text::sprintf('COM_XDECARONOTIFICATIONS_TEST_NOTIFICATION_CREATED', $notificationId)
        );
    }

    private function assertAuthorized(): void
    {
        if (!Session::checkToken()) {
            throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_xdecaronotifications')) {
            throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }

    private function getNotificationsComponent(): NotificationsComponent
    {
        $component = Factory::getApplication()->bootComponent('com_xdecaronotifications');

        if (!$component instanceof NotificationsComponent) {
            throw new RuntimeException('Notifications component service is unavailable.');
        }

        return $component;
    }
}
