<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$search   = (string) $this->state->get('filter.search');
$state    = (string) $this->state->get('filter.state');
$priority = (string) $this->state->get('filter.priority');
$category = (string) $this->state->get('filter.category');
?>
<div class="xdecaro-scope">
    <form action="<?php echo Route::_('index.php?option=com_xdecaronotifications&view=notifications'); ?>" method="get" id="adminForm" name="adminForm">
        <input type="hidden" name="option" value="com_xdecaronotifications">
        <input type="hidden" name="view" value="notifications">

        <div class="xdecaro-card mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-5">
                    <label class="form-label" for="filter_search"><?php echo Text::_('JSEARCH_FILTER'); ?></label>
                    <input class="form-control" type="search" id="filter_search" name="filter_search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_XDECARONOTIFICATIONS_SEARCH_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="filter_state"><?php echo Text::_('COM_XDECARONOTIFICATIONS_STATE'); ?></label>
                    <select class="form-select" id="filter_state" name="filter_state">
                        <option value=""><?php echo Text::_('JALL'); ?></option>
                        <option value="unread"<?php echo $state === 'unread' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECARONOTIFICATIONS_STATE_UNREAD'); ?></option>
                        <option value="read"<?php echo $state === 'read' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECARONOTIFICATIONS_STATE_READ'); ?></option>
                        <option value="archived"<?php echo $state === 'archived' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECARONOTIFICATIONS_STATE_ARCHIVED'); ?></option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="filter_priority"><?php echo Text::_('COM_XDECARONOTIFICATIONS_PRIORITY'); ?></label>
                    <select class="form-select" id="filter_priority" name="filter_priority">
                        <option value=""><?php echo Text::_('JALL'); ?></option>
                        <option value="low"<?php echo $priority === 'low' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECARONOTIFICATIONS_PRIORITY_LOW'); ?></option>
                        <option value="normal"<?php echo $priority === 'normal' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECARONOTIFICATIONS_PRIORITY_NORMAL'); ?></option>
                        <option value="high"<?php echo $priority === 'high' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECARONOTIFICATIONS_PRIORITY_HIGH'); ?></option>
                        <option value="critical"<?php echo $priority === 'critical' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECARONOTIFICATIONS_PRIORITY_CRITICAL'); ?></option>
                    </select>
                </div>
                <div class="col-12 col-lg-2">
                    <label class="form-label" for="filter_category"><?php echo Text::_('COM_XDECARONOTIFICATIONS_CATEGORY'); ?></label>
                    <input class="form-control" type="text" id="filter_category" name="filter_category" value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-12 col-lg-1 d-grid">
                    <button class="btn btn-primary" type="submit"><?php echo Text::_('JFILTER'); ?></button>
                </div>
            </div>
        </div>

        <?php if (!$this->items) : ?>
            <div class="alert alert-info" role="status"><?php echo Text::_('COM_XDECARONOTIFICATIONS_NO_NOTIFICATIONS'); ?></div>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <caption class="visually-hidden"><?php echo Text::_('COM_XDECARONOTIFICATIONS_NOTIFICATIONS'); ?></caption>
                    <thead>
                        <tr>
                            <th scope="col"><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_RECIPIENT'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_CATEGORY'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_PRIORITY'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_STATE'); ?></th>
                            <th scope="col"><?php echo Text::_('JDATE'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->items as $item) : ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <div class="small text-body-secondary mt-1"><?php echo nl2br(htmlspecialchars((string) $item->message, ENT_QUOTES, 'UTF-8')); ?></div>
                                <?php if ((string) $item->source_component !== '') : ?>
                                    <div class="small text-body-secondary mt-1">
                                        <?php echo Text::_('COM_XDECARONOTIFICATIONS_SOURCE'); ?>:
                                        <?php echo htmlspecialchars(trim((string) $item->source_component . ' / ' . (string) $item->source_entity . ' / ' . (string) $item->source_id, ' /'), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars((string) $item->recipient_type . ':' . (string) $item->recipient_id, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars((string) $item->category, ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo Text::_('COM_XDECARONOTIFICATIONS_PRIORITY_' . strtoupper((string) $item->priority)); ?></td>
                            <td><?php echo Text::_('COM_XDECARONOTIFICATIONS_STATE_' . strtoupper((string) $item->state)); ?></td>
                            <td><?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC5')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center">
                <?php echo $this->pagination->getListFooter(); ?>
            </div>
        <?php endif; ?>
    </form>
</div>
