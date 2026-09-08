<?php
namespace Xdecaro\Component\Decaronotifications\Administrator\View\Notifications;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Xdecaro\Core\Asset\AssetService;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];

    public function display($tpl = null): void
    {
        if (class_exists(AssetService::class)) {
            (new AssetService())->useComponents($this->getDocument()->getWebAssetManager());
        }
        $this->items = $this->getModel()->getItems();
        ToolbarHelper::title(Text::_('COM_DECARONOTIFICATIONS_NOTIFICATIONS'), 'bell');
        parent::display($tpl);
    }
}
