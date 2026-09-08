<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="xdecaro-scope">
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-body-secondary small"><?php echo Text::_('COM_XDECARONOTIFICATIONS_TOTAL'); ?></div>
                <div class="display-6 fw-semibold"><?php echo (int) ($this->stats['total'] ?? 0); ?></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-body-secondary small"><?php echo Text::_('COM_XDECARONOTIFICATIONS_UNREAD'); ?></div>
                <div class="display-6 fw-semibold"><?php echo (int) ($this->stats['unread'] ?? 0); ?></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-body-secondary small"><?php echo Text::_('COM_XDECARONOTIFICATIONS_CRITICAL'); ?></div>
                <div class="display-6 fw-semibold"><?php echo (int) ($this->stats['critical'] ?? 0); ?></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-body-secondary small"><?php echo Text::_('COM_XDECARONOTIFICATIONS_ARCHIVED_COUNT'); ?></div>
                <div class="display-6 fw-semibold"><?php echo (int) ($this->stats['archived'] ?? 0); ?></div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <strong><?php echo Text::_('COM_XDECARONOTIFICATIONS_RECENT'); ?></strong>
            <a class="btn btn-sm btn-primary" href="<?php echo Route::_('index.php?option=com_xdecaronotifications&view=notifications'); ?>">
                <?php echo Text::_('COM_XDECARONOTIFICATIONS_OPEN_CENTER'); ?>
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (!$this->recent) : ?>
                <div class="p-3 text-body-secondary"><?php echo Text::_('COM_XDECARONOTIFICATIONS_NO_NOTIFICATIONS'); ?></div>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <caption class="visually-hidden"><?php echo Text::_('COM_XDECARONOTIFICATIONS_RECENT'); ?></caption>
                        <thead>
                            <tr>
                                <th scope="col"><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                                <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_RECIPIENT'); ?></th>
                                <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_PRIORITY'); ?></th>
                                <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_STATE'); ?></th>
                                <th scope="col"><?php echo Text::_('JDATE'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->recent as $item) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <?php if ((string) $item->source_component !== '') : ?>
                                        <div class="small text-body-secondary"><?php echo htmlspecialchars((string) $item->source_component, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars((string) $item->recipient_type . ':' . (string) $item->recipient_id, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo Text::_('COM_XDECARONOTIFICATIONS_PRIORITY_' . strtoupper((string) $item->priority)); ?></td>
                                <td><?php echo Text::_('COM_XDECARONOTIFICATIONS_STATE_' . strtoupper((string) $item->state)); ?></td>
                                <td><?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC5')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
