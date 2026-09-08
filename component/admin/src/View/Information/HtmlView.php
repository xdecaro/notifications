<?php
namespace Xdecaro\Component\Notifications\Administrator\View\Information;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Xdecaro\Core\Asset\AssetService;
use Xdecaro\Core\Version as CoreVersion;

final class HtmlView extends BaseHtmlView
{
    public bool $coreAvailable = false;
    public ?string $coreVersion = null;

    public function display($tpl = null): void
    {
        $this->coreAvailable = class_exists(CoreVersion::class);
        $this->coreVersion = $this->coreAvailable ? CoreVersion::VERSION : null;
        if (class_exists(AssetService::class)) {
            (new AssetService())->useComponents($this->getDocument()->getWebAssetManager());
        }
        ToolbarHelper::title(Text::_('COM_XDECARONOTIFICATIONS_INFORMATION'), 'info-circle');
        parent::display($tpl);
    }
}
