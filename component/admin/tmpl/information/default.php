<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
?>
<div class="xdecaro-scope container-fluid px-0">
  <div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2 class="h5"><?php echo Text::_('COM_DECARONOTIFICATIONS_PRODUCT_ENVIRONMENT'); ?></h2><dl class="row mb-0"><dt class="col-5">Notifications</dt><dd class="col-7">0.2.0</dd><dt class="col-5">Joomla</dt><dd class="col-7"><?php echo $this->escape(JVERSION); ?></dd><dt class="col-5">PHP</dt><dd class="col-7"><?php echo $this->escape(PHP_VERSION); ?></dd></dl></div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2 class="h5"><?php echo Text::_('COM_DECARONOTIFICATIONS_INCLUDED_UPDATES'); ?></h2><p class="mb-2">com_decaronotifications</p><p class="mb-0 text-body-secondary"><?php echo Text::_('COM_DECARONOTIFICATIONS_PACKAGE_UPDATES'); ?></p></div></div></div>
  </div>
  <div class="card mb-3"><div class="card-body"><h2 class="h5"><?php echo Text::_('COM_DECARONOTIFICATIONS_CONNECTED_COMPONENTS'); ?></h2><p class="mb-0"><?php echo $this->coreAvailable ? Text::sprintf('COM_DECARONOTIFICATIONS_CORE_AVAILABLE', $this->escape((string) $this->coreVersion)) : Text::_('COM_DECARONOTIFICATIONS_CORE_OPTIONAL'); ?></p></div></div>
  <div class="card"><div class="card-body"><h2 class="h5"><?php echo Text::_('COM_DECARONOTIFICATIONS_DIAGNOSTICS'); ?></h2><ul class="mb-0"><li><?php echo Text::_('COM_DECARONOTIFICATIONS_DIAG_TABLES'); ?></li><li><?php echo Text::_('COM_DECARONOTIFICATIONS_DIAG_CORE_BOUNDARY'); ?></li><li><?php echo Text::_('COM_DECARONOTIFICATIONS_DIAG_CHANNEL_INTERNAL'); ?></li></ul></div></div>
</div>
