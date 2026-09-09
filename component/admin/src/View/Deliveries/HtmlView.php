<?php
namespace Xdecaro\Component\Notifications\Administrator\View\Deliveries;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use RuntimeException;
use xdecaro\Core\Asset\AssetService;

final class HtmlView extends BaseHtmlView
{
    /** @var array<int,object> */
    public $items = [];

    public $pagination;
    public $state;

    public function display($tpl = null): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_xdecaronotifications')) {
            throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->items      = (array) $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state      = $this->get('State');

        if (class_exists(AssetService::class)) {
            (new AssetService())->useComponents($this->getDocument()->getWebAssetManager());
        }

        ToolbarHelper::title(Text::_('COM_XDECARONOTIFICATIONS_DELIVERIES'));
        ToolbarHelper::custom('delivery.process', 'play', 'play', 'COM_XDECARONOTIFICATIONS_PROCESS_QUEUE', false);

        parent::display($tpl);
    }
}
