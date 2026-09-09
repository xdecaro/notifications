<?php
namespace Xdecaro\Component\Notifications\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use xdecaro\Core\Asset\AssetService;

final class HtmlView extends BaseHtmlView
{
    /** @var array */
    public $stats = [];

    /** @var array */
    public $recent = [];

    public function display($tpl = null): void
    {
        $this->stats  = (array) $this->get('Stats');
        $this->recent = (array) $this->get('Recent');

        if (class_exists(AssetService::class)) {
            (new AssetService())->useComponents($this->getDocument()->getWebAssetManager());
        }

        ToolbarHelper::title(Text::_('COM_XDECARONOTIFICATIONS_DASHBOARD'));

        parent::display($tpl);
    }
}
