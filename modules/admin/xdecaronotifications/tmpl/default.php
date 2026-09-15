<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

if ($app->getInput()->getBool('hidemainmenu')) {
    return;
}

HTMLHelper::_('bootstrap.dropdown', '.xdecaro-notifications-toggle');
HTMLHelper::_('script', 'mod_xdecaronotifications/admin-bell.js', ['version' => 'auto', 'relative' => true], ['defer' => true]);

$pollUrl = Route::_('index.php?option=com_xdecaronotifications&task=bell.poll&format=json', false);
$centerUrl = Route::_('index.php?option=com_xdecaronotifications&view=notifications');
?>
<div
    class="header-item-content dropdown"
    data-xdecaro-notifications-bell
    data-poll-url="<?php echo htmlspecialchars($pollUrl, ENT_QUOTES, 'UTF-8'); ?>"
    data-empty-label="<?php echo htmlspecialchars(Text::_('MOD_XDECARONOTIFICATIONS_EMPTY'), ENT_QUOTES, 'UTF-8'); ?>"
    data-bell-label="<?php echo htmlspecialchars(Text::_('MOD_XDECARONOTIFICATIONS_TITLE'), ENT_QUOTES, 'UTF-8'); ?>"
>
    <button
        class="dropdown-toggle d-flex align-items-center px-2 py-0 xdecaro-notifications-toggle"
        data-bs-toggle="dropdown"
        data-bs-auto-close="outside"
        type="button"
        title="<?php echo htmlspecialchars(Text::_('MOD_XDECARONOTIFICATIONS_TITLE'), ENT_QUOTES, 'UTF-8'); ?>"
        aria-label="<?php echo htmlspecialchars(Text::_('MOD_XDECARONOTIFICATIONS_TITLE'), ENT_QUOTES, 'UTF-8'); ?>"
    >
        <span class="header-item-icon">
            <span class="icon-bell" aria-hidden="true"></span>
            <small class="header-item-count" data-xdecaro-bell-count<?php echo $countUnread < 1 ? ' hidden' : ''; ?>><?php echo (int) $countUnread; ?></small>
        </span>
        <span class="header-item-text"><?php echo Text::_('MOD_XDECARONOTIFICATIONS_TITLE'); ?></span>
        <span class="icon-angle-down" aria-hidden="true"></span>
    </button>

    <div class="dropdown-menu dropdown-menu-end">
        <div class="dropdown-header d-flex align-items-center gap-2">
            <span class="icon-bell icon-fw" aria-hidden="true"></span>
            <strong><?php echo Text::_('MOD_XDECARONOTIFICATIONS_TITLE'); ?></strong>
        </div>

        <div data-xdecaro-bell-items>
            <?php if (!$items) : ?>
                <div class="dropdown-item-text text-body-secondary"><?php echo Text::_('MOD_XDECARONOTIFICATIONS_EMPTY'); ?></div>
            <?php else : ?>
                <?php foreach ($items as $item) : ?>
                    <?php
                    $actionUrl = (string) ($item['action_url'] ?? '');
                    $priority = (string) ($item['priority'] ?? 'normal');
                    $state = (string) ($item['state'] ?? 'unread');
                    $created = (string) ($item['created'] ?? '');
                    ?>
                    <?php if ($actionUrl !== '') : ?>
                        <a class="dropdown-item py-2" href="<?php echo htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php else : ?>
                        <div class="dropdown-item-text py-2">
                    <?php endif; ?>
                            <div class="fw-semibold text-wrap"><?php echo htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="small text-body-secondary text-wrap"><?php echo htmlspecialchars((string) ($item['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="small text-body-secondary mt-1">
                                <?php echo Text::_('COM_XDECARONOTIFICATIONS_PRIORITY_' . strtoupper($priority)); ?>
                                · <?php echo Text::_('COM_XDECARONOTIFICATIONS_STATE_' . strtoupper($state)); ?>
                                <?php if ($created !== '') : ?>
                                    · <?php echo HTMLHelper::_('date', $created, Text::_('DATE_FORMAT_LC5')); ?>
                                <?php endif; ?>
                            </div>
                    <?php if ($actionUrl !== '') : ?>
                        </a>
                    <?php else : ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($canManage) : ?>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="<?php echo $centerUrl; ?>">
                <span class="icon-list icon-fw" aria-hidden="true"></span>
                <?php echo Text::_('MOD_XDECARONOTIFICATIONS_OPEN_CENTER'); ?>
            </a>
        <?php endif; ?>
    </div>
</div>
