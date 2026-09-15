<?php
namespace Xdecaro\Component\Notifications\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use RuntimeException;
use Xdecaro\Component\Notifications\Administrator\Extension\NotificationsComponent;

final class BellController extends BaseController
{
    public function poll(): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $userId = (int) ($user->id ?? 0);

        if ($userId < 1 || !$user->authorise('core.login.admin')) {
            throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $app->getLanguage()->load(
            'com_xdecaronotifications',
            JPATH_ADMINISTRATOR . '/components/com_xdecaronotifications'
        );

        $service = $this->getNotificationsComponent()->getNotificationService();
        $recipientId = (string) $userId;
        $items = $service->getForRecipient('user', $recipientId, [
            'state' => ['unread', 'read'],
            'limit' => 5,
        ]);

        $safeItems = [];

        foreach ($items as $item) {
            $priority = (string) ($item['priority'] ?? 'normal');
            $state = (string) ($item['state'] ?? 'unread');
            $created = (string) ($item['created'] ?? '');

            $safeItems[] = [
                'id' => (int) ($item['id'] ?? 0),
                'title' => (string) ($item['title'] ?? ''),
                'message' => (string) ($item['message'] ?? ''),
                'priority' => $priority,
                'priority_label' => Text::_('COM_XDECARONOTIFICATIONS_PRIORITY_' . strtoupper($priority)),
                'state' => $state,
                'state_label' => Text::_('COM_XDECARONOTIFICATIONS_STATE_' . strtoupper($state)),
                'created' => $created,
                'created_label' => $created !== '' ? HTMLHelper::_('date', $created, Text::_('DATE_FORMAT_LC5')) : '',
                'action_url' => (string) ($item['action_url'] ?? ''),
            ];
        }

        echo new JsonResponse([
            'unread' => $service->getUnreadCount('user', $recipientId),
            'items' => $safeItems,
        ]);

        $app->close();
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
