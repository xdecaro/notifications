<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

if (!$this->item) : ?>
<div class="alert alert-warning"><?php echo Text::_('COM_DECARONOTIFICATIONS_NOT_FOUND'); ?></div>
<?php return; endif; ?>
<div class="xdecaro-scope">
  <div class="card mb-3"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
      <div><h2 class="h4 mb-1"><?php echo $this->escape($this->item->title); ?></h2><div class="text-body-secondary"><?php echo $this->escape($this->item->category); ?></div></div>
      <span class="badge bg-secondary align-self-start"><?php echo $this->escape($this->item->priority); ?></span>
    </div>
    <p class="mb-4"><?php echo nl2br($this->escape($this->item->message)); ?></p>
    <dl class="row mb-0">
      <dt class="col-sm-3"><?php echo Text::_('COM_DECARONOTIFICATIONS_SOURCE'); ?></dt><dd class="col-sm-9"><code><?php echo $this->escape($this->item->source_component); ?></code></dd>
      <dt class="col-sm-3"><?php echo Text::_('COM_DECARONOTIFICATIONS_EVENT'); ?></dt><dd class="col-sm-9"><?php echo $this->escape((string) $this->item->event_name); ?></dd>
      <dt class="col-sm-3"><?php echo Text::_('COM_DECARONOTIFICATIONS_EXTERNAL_KEY'); ?></dt><dd class="col-sm-9"><code><?php echo $this->escape((string) $this->item->external_key); ?></code></dd>
      <dt class="col-sm-3"><?php echo Text::_('JGLOBAL_CREATED_DATE'); ?></dt><dd class="col-sm-9"><?php echo $this->escape($this->item->created); ?></dd>
    </dl>
  </div></div>

  <div class="card"><div class="card-header"><strong><?php echo Text::_('COM_DECARONOTIFICATIONS_RECIPIENTS'); ?></strong></div><div class="table-responsive">
    <table class="table align-middle mb-0"><thead><tr><th><?php echo Text::_('COM_DECARONOTIFICATIONS_RECIPIENT'); ?></th><th><?php echo Text::_('COM_DECARONOTIFICATIONS_READ'); ?></th><th><?php echo Text::_('COM_DECARONOTIFICATIONS_ARCHIVED'); ?></th></tr></thead><tbody>
    <?php foreach ($this->recipients as $recipient) : ?><tr><td><code><?php echo $this->escape($recipient->recipient_key); ?></code></td><td><?php echo $recipient->read_at ? $this->escape($recipient->read_at) : '—'; ?></td><td><?php echo $recipient->archived_at ? $this->escape($recipient->archived_at) : '—'; ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </div></div>
</div>
