<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="xdecaro-scope container-fluid px-0">
  <div class="row g-3 mb-4">
    <?php foreach (['total','active','scheduled','expired','unread'] as $key) : ?>
      <div class="col-6 col-lg"><div class="card h-100"><div class="card-body">
        <div class="text-body-secondary small"><?php echo Text::_('COM_XDECARONOTIFICATIONS_STAT_' . strtoupper($key)); ?></div>
        <div class="fs-3 fw-semibold"><?php echo (int) ($this->stats[$key] ?? 0); ?></div>
      </div></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
      <strong><?php echo Text::_('COM_XDECARONOTIFICATIONS_RECENT'); ?></strong>
      <a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_xdecaronotifications&view=notifications'); ?>"><?php echo Text::_('COM_XDECARONOTIFICATIONS_VIEW_ALL'); ?></a>
    </div>
    <div class="table-responsive"><table class="table align-middle mb-0">
      <thead><tr><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th><th><?php echo Text::_('COM_XDECARONOTIFICATIONS_SOURCE'); ?></th><th><?php echo Text::_('COM_XDECARONOTIFICATIONS_PRIORITY'); ?></th><th><?php echo Text::_('COM_XDECARONOTIFICATIONS_RECIPIENTS'); ?></th><th><?php echo Text::_('JDATE'); ?></th></tr></thead>
      <tbody>
      <?php if (!$this->recent) : ?><tr><td colspan="5" class="text-center py-4 text-body-secondary"><?php echo Text::_('COM_XDECARONOTIFICATIONS_EMPTY'); ?></td></tr><?php endif; ?>
      <?php foreach ($this->recent as $item) : ?><tr>
        <td><a href="<?php echo Route::_('index.php?option=com_xdecaronotifications&view=notification&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->title); ?></a></td>
        <td><code><?php echo $this->escape($item->source_component); ?></code></td>
        <td><span class="badge bg-secondary"><?php echo $this->escape($item->priority); ?></span></td>
        <td><?php echo (int) $item->recipient_count; ?></td><td><?php echo $this->escape($item->created); ?></td>
      </tr><?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
</div>
