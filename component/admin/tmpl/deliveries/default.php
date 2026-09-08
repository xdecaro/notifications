<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$search  = (string) $this->state->get('filter.search');
$state   = (string) $this->state->get('filter.state');
$channel = (string) $this->state->get('filter.channel');
?>
<div class="xdecaro-scope">
    <form action="<?php echo Route::_('index.php?option=com_xdecaronotifications&view=deliveries'); ?>" method="post" id="adminForm" name="adminForm">
        <div class="xdecaro-card mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-5">
                    <label class="form-label" for="filter_search"><?php echo Text::_('JSEARCH_FILTER'); ?></label>
                    <input class="form-control" type="search" id="filter_search" name="filter_search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_XDECARONOTIFICATIONS_DELIVERY_SEARCH_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-6 col-lg-3">
                    <label class="form-label" for="filter_state"><?php echo Text::_('COM_XDECARONOTIFICATIONS_STATE'); ?></label>
                    <select class="form-select" id="filter_state" name="filter_state">
                        <option value=""><?php echo Text::_('JALL'); ?></option>
                        <?php foreach (['pending', 'processing', 'retry', 'delivered', 'failed'] as $value) : ?>
                            <option value="<?php echo $value; ?>"<?php echo $state === $value ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECARONOTIFICATIONS_DELIVERY_STATE_' . strtoupper($value)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-lg-3">
                    <label class="form-label" for="filter_channel"><?php echo Text::_('COM_XDECARONOTIFICATIONS_CHANNEL'); ?></label>
                    <input class="form-control" type="text" id="filter_channel" name="filter_channel" value="<?php echo htmlspecialchars($channel, ENT_QUOTES, 'UTF-8'); ?>" placeholder="in_app">
                </div>
                <div class="col-12 col-lg-1 d-grid">
                    <button class="btn btn-primary" type="submit"><?php echo Text::_('JFILTER'); ?></button>
                </div>
            </div>
        </div>

        <?php if (!$this->items) : ?>
            <div class="alert alert-info" role="status"><?php echo Text::_('COM_XDECARONOTIFICATIONS_NO_DELIVERIES'); ?></div>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <caption class="visually-hidden"><?php echo Text::_('COM_XDECARONOTIFICATIONS_DELIVERIES'); ?></caption>
                    <thead>
                        <tr>
                            <th scope="col"><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_CHANNEL'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_STATE'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_ATTEMPTS'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_RECIPIENT'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_AVAILABLE_AT'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_LAST_ERROR'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->items as $item) : ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars((string) ($item->title ?? ('#' . $item->notification_id)), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <div class="small text-body-secondary">#<?php echo (int) $item->notification_id; ?> · <?php echo htmlspecialchars((string) $item->category, ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars((string) $item->channel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo Text::_('COM_XDECARONOTIFICATIONS_DELIVERY_STATE_' . strtoupper((string) $item->state)); ?></td>
                            <td><?php echo (int) $item->attempts; ?></td>
                            <td><?php echo htmlspecialchars((string) $item->recipient_type . ':' . (string) $item->recipient_id, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo HTMLHelper::_('date', $item->available_at, Text::_('DATE_FORMAT_LC5')); ?></td>
                            <td class="small"><?php echo htmlspecialchars((string) $item->last_error, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center"><?php echo $this->pagination->getListFooter(); ?></div>
        <?php endif; ?>

        <input type="hidden" name="task" value="">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
