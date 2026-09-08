<?php
namespace xdecaro\Component\Notifications\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use xdecaro\Core\Asset\AssetService;

final class HtmlView extends BaseHtmlView
{
    public function display($tpl = null): void
    {
        if (class_exists(AssetService::class)) {
            (new AssetService())->useComponents($this->getDocument()->getWebAssetManager());
        }

        parent::display($tpl);
    }
}
