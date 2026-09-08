<?php
namespace Xdecaro\Component\Notifications\Site\View\Notifications;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Xdecaro\Core\Asset\AssetService;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public int $unreadCount = 0;
    public bool $internalEnabled = true;
    public bool $guest = true;

    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();
        $this->guest = !$user || $user->guest;

        if (class_exists(AssetService::class)) {
            (new AssetService())->useComponents($this->getDocument()->getWebAssetManager());
        }

        if (!$this->guest) {
            $model = $this->getModel();
            $this->items = $model->getItems();
            $this->unreadCount = $model->getUnreadCount();
            $this->internalEnabled = $model->isInternalEnabled();
        }

        parent::display($tpl);
    }
}
