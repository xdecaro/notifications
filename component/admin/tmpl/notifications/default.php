<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="xdecaro-scope card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th><th><?php echo Text::_('COM_DECARONOTIFICATIONS_CATEGORY'); ?></th><th><?php echo Text::_('COM_DECARONOTIFICATIONS_SOURCE'); ?></th><th><?php echo Text::_('COM_DECARONOTIFICATIONS_PRIORITY'); ?></th><th><?php echo Text::_('COM_DECARONOTIFICATIONS_RECIPIENTS'); ?></th><th><?php echo Text::_('COM_DECARONOTIFICATIONS_READ'); ?></th><th><?php echo Text::_('JDATE'); ?></th></tr></thead>
      <tbody>
      <?php if (!$this->items) : ?><tr><td colspan="7" class="text-center py-5 text-body-secondary"><?php echo Text::_('COM_DECARONOTIFICATIONS_EMPTY'); ?></td></tr><?php endif; ?>
      <?php foreach ($this->items as $item) : ?>
        <tr>
          <td><a class="fw-semibold" href="<?php echo Route::_('index.php?option=com_decaronotifications&view=notification&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->title); ?></a></td>
          <td><?php echo $this->escape($item->category); ?></td>
          <td><code><?php echo $this->escape($item->source_component); ?></code></td>
          <td><span class="badge bg-secondary"><?php echo $this->escape($item->priority); ?></span></td>
          <td><?php echo (int) $item->recipient_count; ?></td>
          <td><?php echo (int) $item->read_count; ?></td>
          <td><?php echo $this->escape($item->created); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
