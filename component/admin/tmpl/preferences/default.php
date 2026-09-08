<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$search  = (string) $this->state->get('filter.search');
$channel = (string) $this->state->get('filter.channel');
?>
<div class="xdecaro-scope">
    <div class="xdecaro-card mb-4">
        <h2 class="h5"><?php echo Text::_('COM_XDECARONOTIFICATIONS_PREFERENCE_RULE'); ?></h2>
        <p class="text-body-secondary"><?php echo Text::_('COM_XDECARONOTIFICATIONS_PREFERENCE_RULE_HELP'); ?></p>
        <form action="<?php echo Route::_('index.php?option=com_xdecaronotifications'); ?>" method="post">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="form-label" for="recipient_type"><?php echo Text::_('COM_XDECARONOTIFICATIONS_RECIPIENT_TYPE'); ?></label>
                    <input class="form-control" id="recipient_type" name="recipient_type" value="user" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label" for="recipient_id"><?php echo Text::_('COM_XDECARONOTIFICATIONS_RECIPIENT_ID'); ?></label>
                    <input class="form-control" id="recipient_id" name="recipient_id" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label" for="category"><?php echo Text::_('COM_XDECARONOTIFICATIONS_CATEGORY'); ?></label>
                    <input class="form-control" id="category" name="category" value="*" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label" for="channel"><?php echo Text::_('COM_XDECARONOTIFICATIONS_CHANNEL'); ?></label>
                    <input class="form-control" id="channel" name="channel" value="in_app" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label" for="enabled"><?php echo Text::_('JSTATUS'); ?></label>
                    <select class="form-select" id="enabled" name="enabled">
                        <option value="1"><?php echo Text::_('JENABLED'); ?></option>
                        <option value="0"><?php echo Text::_('JDISABLED'); ?></option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button class="btn btn-primary" type="submit"><?php echo Text::_('JSAVE'); ?></button>
                </div>
            </div>
            <input type="hidden" name="task" value="preference.save">
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
    </div>

    <form action="<?php echo Route::_('index.php?option=com_xdecaronotifications&view=preferences'); ?>" method="get" class="xdecaro-card mb-3">
        <input type="hidden" name="option" value="com_xdecaronotifications">
        <input type="hidden" name="view" value="preferences">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-8">
                <label class="form-label" for="filter_search"><?php echo Text::_('JSEARCH_FILTER'); ?></label>
                <input class="form-control" type="search" id="filter_search" name="filter_search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_XDECARONOTIFICATIONS_PREFERENCE_SEARCH_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-8 col-md-3">
                <label class="form-label" for="filter_channel"><?php echo Text::_('COM_XDECARONOTIFICATIONS_CHANNEL'); ?></label>
                <input class="form-control" id="filter_channel" name="filter_channel" value="<?php echo htmlspecialchars($channel, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-4 col-md-1 d-grid">
                <button class="btn btn-primary" type="submit"><?php echo Text::_('JFILTER'); ?></button>
            </div>
        </div>
    </form>

    <?php if (!$this->items) : ?>
        <div class="alert alert-info" role="status"><?php echo Text::_('COM_XDECARONOTIFICATIONS_NO_PREFERENCES'); ?></div>
    <?php else : ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <caption class="visually-hidden"><?php echo Text::_('COM_XDECARONOTIFICATIONS_PREFERENCES'); ?></caption>
                <thead>
                    <tr>
                        <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_RECIPIENT'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_CATEGORY'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_XDECARONOTIFICATIONS_CHANNEL'); ?></th>
                        <th scope="col"><?php echo Text::_('JSTATUS'); ?></th>
                        <th scope="col"><?php echo Text::_('JGLOBAL_FIELD_MODIFIED_LABEL'); ?></th>
                        <th scope="col" class="text-end"><?php echo Text::_('JGLOBAL_ACTIONS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($this->items as $item) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $item->recipient_type . ':' . (string) $item->recipient_id, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $item->category, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars((string) $item->channel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo (int) $item->enabled === 1 ? Text::_('JENABLED') : Text::_('JDISABLED'); ?></td>
                        <td><?php echo HTMLHelper::_('date', $item->modified, Text::_('DATE_FORMAT_LC5')); ?></td>
                        <td class="text-end">
                            <form action="<?php echo Route::_('index.php?option=com_xdecaronotifications'); ?>" method="post" class="d-inline">
                                <input type="hidden" name="task" value="preference.remove">
                                <input type="hidden" name="recipient_type" value="<?php echo htmlspecialchars((string) $item->recipient_type, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="recipient_id" value="<?php echo htmlspecialchars((string) $item->recipient_id, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars((string) $item->category, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="channel" value="<?php echo htmlspecialchars((string) $item->channel, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo HTMLHelper::_('form.token'); ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit"><?php echo Text::_('COM_XDECARONOTIFICATIONS_RESTORE_DEFAULT'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center"><?php echo $this->pagination->getListFooter(); ?></div>
    <?php endif; ?>
</div>
