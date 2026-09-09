<?php
namespace Xdecaro\Component\Notifications\Administrator\View\Notifications;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use xdecaro\Core\Asset\AssetService;

final class HtmlView extends BaseHtmlView
{
    /** @var array */
    public $items = [];

    /** @var mixed */
    public $pagination;

    /** @var mixed */
    public $state;

    public function display($tpl = null): void
    {
        $this->items      = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state      = $this->get('State');

        if (class_exists(AssetService::class)) {
            (new AssetService())->useComponents($this->getDocument()->getWebAssetManager());
        }

        ToolbarHelper::title(Text::_('COM_XDECARONOTIFICATIONS_NOTIFICATIONS'));

        parent::display($tpl);
    }
}
