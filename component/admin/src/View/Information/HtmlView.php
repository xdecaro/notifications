<?php
namespace Xdecaro\Component\Notifications\Administrator\View\Information;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use xdecaro\Core\Asset\AssetService;

final class HtmlView extends BaseHtmlView
{
    public $product = [];
    public $environment = [];
    public $includedExtensions = [];
    public $updateInfo = [];
    public $connectedComponents = [];
    public $diagnostics = [];

    public function display($tpl = null): void
    {
        $this->product = (array) $this->get('Product');
        $this->environment = (array) $this->get('Environment');
        $this->includedExtensions = (array) $this->get('IncludedExtensions');
        $this->updateInfo = (array) $this->get('UpdateInfo');
        $this->connectedComponents = (array) $this->get('ConnectedComponents');
        $this->diagnostics = (array) $this->get('Diagnostics');
        if (class_exists(AssetService::class)) { (new AssetService())->useComponents($this->getDocument()->getWebAssetManager()); }
        ToolbarHelper::title(Text::_('COM_XDECARONOTIFICATIONS_INFORMATION'));
        ToolbarHelper::preferences('com_xdecaronotifications');
        parent::display($tpl);
    }
}
