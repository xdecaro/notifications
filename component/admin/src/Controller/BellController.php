<?php
namespace Xdecaro\Component\Notifications\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use RuntimeException;
use Xdecaro\Component\Notifications\Administrator\Extension\NotificationsComponent;

final class BellController extends BaseController
{
    public function poll(): void
    {
        $app = Factory::getApplication();
        [$userId, $recipientId] = $this->requireAdministrator();
        $this->loadLanguage();
        $service = $this->getNotificationsComponent()->getNotificationService();

        echo new JsonResponse($this->buildBellData($service, $recipientId));

        $app->close();
    }

    public function markRead(): void
    {
        $app = Factory::getApplication();
        [$userId, $recipientId] = $this->requireAdministrator();

        if (!Session::checkToken('post')) {
            throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $notificationId = $app->getInput()->post->getInt('notification_id');

        if ($notificationId < 1) {
            throw new RuntimeException('Invalid notification ID.', 400);
        }

        $this->loadLanguage();
        $service = $this->getNotificationsComponent()->getNotificationService();
        $changed = $service->markReadForRecipient($notificationId, 'user', $recipientId);
        $data = $this->buildBellData($service, $recipientId);
        $data['changed'] = $changed;

        echo new JsonResponse($data);

        $app->close();
    }

    public function markAllRead(): void
    {
        $app = Factory::getApplication();
        [$userId, $recipientId] = $this->requireAdministrator();

        if (!Session::checkToken('post')) {
            throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $this->loadLanguage();
        $service = $this->getNotificationsComponent()->getNotificationService();
        $marked = 0;

        do {
            $items = $service->getForRecipient('user', $recipientId, [
                'state' => 'unread',
                'include_expired' => true,
                'limit' => 100,
            ]);

            foreach ($items as $item) {
                $notificationId = (int) ($item['id'] ?? 0);

                if ($notificationId > 0 && $service->markReadForRecipient($notificationId, 'user', $recipientId)) {
                    $marked++;
                }
            }
        } while (count($items) === 100);

        $data = $this->buildBellData($service, $recipientId);
        $data['marked'] = $marked;

        echo new JsonResponse($data);

        $app->close();
    }

    /**
     * Legacy endpoint retained for compatibility with already-loaded 1.1.8/1.1.9 pages.
     * The current bell UI no longer calls this action.
     */
    public function archiveAll(): void
    {
        $app = Factory::getApplication();
        [$userId, $recipientId] = $this->requireAdministrator();

        if (!Session::checkToken('post')) {
            throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $service = $this->getNotificationsComponent()->getNotificationService();
        $archived = 0;

        do {
            $items = $service->getForRecipient('user', $recipientId, [
                'state' => ['unread', 'read'],
                'include_expired' => true,
                'limit' => 100,
            ]);

            foreach ($items as $item) {
                $notificationId = (int) ($item['id'] ?? 0);

                if ($notificationId > 0 && $service->archiveForRecipient($notificationId, 'user', $recipientId)) {
                    $archived++;
                }
            }
        } while (count($items) === 100);

        echo new JsonResponse([
            'archived' => $archived,
            'unread' => 0,
            'items' => [],
        ]);

        $app->close();
    }

    /** @return array{0:int,1:string} */
    private function requireAdministrator(): array
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $userId = (int) ($user->id ?? 0);

        if ($userId < 1 || !$user->authorise('core.login.admin')) {
            throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        return [$userId, (string) $userId];
    }

    private function loadLanguage(): void
    {
        Factory::getApplication()->getLanguage()->load(
            'com_xdecaronotifications',
            JPATH_ADMINISTRATOR . '/components/com_xdecaronotifications'
        );
    }

    /** @return array{unread:int,items:array<int,array<string,mixed>>} */
    private function buildBellData($service, string $recipientId): array
    {
        $items = $service->getForRecipient('user', $recipientId, [
            'state' => 'unread',
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

        return [
            'unread' => $service->getUnreadCount('user', $recipientId),
            'items' => $safeItems,
        ];
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
