<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

if ($this->guest) : ?>
<div class="alert alert-info"><?php echo Text::_('COM_XDECARONOTIFICATIONS_LOGIN_REQUIRED'); ?> <a href="<?php echo Route::_('index.php?option=com_users&view=login'); ?>"><?php echo Text::_('JLOGIN'); ?></a></div>
<?php return; endif; ?>
<div class="xdecaro-scope decaronotifications-center">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h1 class="h3 mb-1"><?php echo Text::_('COM_XDECARONOTIFICATIONS_MY_NOTIFICATIONS'); ?></h1><div class="text-body-secondary"><?php echo Text::sprintf('COM_XDECARONOTIFICATIONS_UNREAD_COUNT', (int) $this->unreadCount); ?></div></div></div>
  <?php if (!$this->items) : ?><div class="card"><div class="card-body text-center py-5 text-body-secondary"><?php echo Text::_('COM_XDECARONOTIFICATIONS_NO_USER_NOTIFICATIONS'); ?></div></div><?php endif; ?>
  <div class="d-grid gap-3">
    <?php foreach ($this->items as $item) : ?><article class="card<?php echo $item->read_at ? '' : ' border-primary'; ?>"><div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2"><div><div class="small text-body-secondary"><?php echo $this->escape($item->category); ?></div><h2 class="h5 mb-0"><?php echo $this->escape($item->title); ?></h2></div><span class="badge bg-secondary"><?php echo $this->escape($item->priority); ?></span></div>
      <p><?php echo nl2br($this->escape($item->message)); ?></p><div class="small text-body-secondary mb-3"><?php echo $this->escape($item->created); ?></div>
      <div class="d-flex flex-wrap gap-2">
        <?php if ($item->action_url) : ?><a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars((string) $item->action_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $this->escape($item->action_label ?: Text::_('COM_XDECARONOTIFICATIONS_OPEN')); ?></a><?php endif; ?>
        <form method="post" action="<?php echo Route::_('index.php?option=com_xdecaronotifications&task=notification.' . ($item->read_at ? 'unread' : 'read')); ?>"><input type="hidden" name="id" value="<?php echo (int) $item->id; ?>"><button class="btn btn-sm btn-outline-secondary" type="submit"><?php echo Text::_($item->read_at ? 'COM_XDECARONOTIFICATIONS_MARK_UNREAD' : 'COM_XDECARONOTIFICATIONS_MARK_READ'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form>
        <form method="post" action="<?php echo Route::_('index.php?option=com_xdecaronotifications&task=notification.archive'); ?>"><input type="hidden" name="id" value="<?php echo (int) $item->id; ?>"><button class="btn btn-sm btn-outline-secondary" type="submit"><?php echo Text::_('COM_XDECARONOTIFICATIONS_ARCHIVE'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form>
      </div>
    </div></article><?php endforeach; ?>
  </div>
  <div class="card mt-4"><div class="card-body"><h2 class="h5"><?php echo Text::_('COM_XDECARONOTIFICATIONS_PREFERENCES'); ?></h2><p class="text-body-secondary"><?php echo Text::_('COM_XDECARONOTIFICATIONS_INTERNAL_ONLY_NOTE'); ?></p>
    <form method="post" action="<?php echo Route::_('index.php?option=com_xdecaronotifications&task=preferences.save'); ?>" class="row g-3 align-items-end"><div class="col-md-8"><label class="form-label" for="notifications-enabled"><?php echo Text::_('COM_XDECARONOTIFICATIONS_INTERNAL_CHANNEL'); ?></label><select class="form-select" id="notifications-enabled" name="enabled"><option value="1"<?php echo $this->internalEnabled ? ' selected' : ''; ?>><?php echo Text::_('JENABLED'); ?></option><option value="0"<?php echo !$this->internalEnabled ? ' selected' : ''; ?>><?php echo Text::_('JDISABLED'); ?></option></select></div><div class="col-md-4"><button class="btn btn-primary w-100" type="submit"><?php echo Text::_('JSAVE'); ?></button></div><?php echo HTMLHelper::_('form.token'); ?></form>
  </div></div>
</div>
