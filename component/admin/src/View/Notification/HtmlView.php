<?php
namespace Xdecaro\Component\Decaronotifications\Administrator\View\Notification;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Xdecaro\Core\Asset\AssetService;

final class HtmlView extends BaseHtmlView
{
    public ?object $item = null;
    public array $recipients = [];

    public function display($tpl = null): void
    {
        if (class_exists(AssetService::class)) {
            (new AssetService())->useComponents($this->getDocument()->getWebAssetManager());
        }
        $model = $this->getModel();
        $this->item = $model->getItem();
        $this->recipients = $model->getRecipients();
        ToolbarHelper::title(Text::_('COM_DECARONOTIFICATIONS_NOTIFICATION_DETAIL'), 'bell');
        ToolbarHelper::back(Text::_('JTOOLBAR_BACK'), Route::_('index.php?option=com_decaronotifications&view=notifications'));
        parent::display($tpl);
    }
}
