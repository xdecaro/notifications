<?php
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Xdecaro\Component\Notifications\Administrator\Extension\NotificationsComponent;

$user = $app->getIdentity();
$userId = (int) ($user->id ?? 0);

if ($userId < 1 || !$user->authorise('core.login.admin')) {
    return;
}

$countUnread = 0;
$items = [];
$canManage = $user->authorise('core.manage', 'com_xdecaronotifications');

try {
    $component = $app->bootComponent('com_xdecaronotifications');

    if ($component instanceof NotificationsComponent) {
        $service = $component->getNotificationService();
        $recipientId = (string) $userId;
        $countUnread = $service->getUnreadCount('user', $recipientId);
        $items = $service->getForRecipient('user', $recipientId, [
            'state' => ['unread', 'read'],
            'limit' => 5,
        ]);
        $app->getLanguage()->load(
            'com_xdecaronotifications',
            JPATH_ADMINISTRATOR . '/components/com_xdecaronotifications'
        );
    }
} catch (Throwable $exception) {
    // The global administrator chrome must remain usable if Notifications is temporarily unavailable.
    $countUnread = 0;
    $items = [];
}

require ModuleHelper::getLayoutPath('mod_xdecaronotifications', $params->get('layout', 'default'));
