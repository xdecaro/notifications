<?php
namespace Xdecaro\Plugin\Task\Notifications\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use Throwable;
use Xdecaro\Component\Notifications\Administrator\Extension\NotificationsComponent;

final class Notifications extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    protected const TASKS_MAP = [
        'xdecaronotifications.queue' => [
            'langConstPrefix' => 'PLG_TASK_XDECARONOTIFICATIONS_QUEUE',
            'form' => 'queue',
            'method' => 'processQueue',
        ],
        'xdecaronotifications.maintenance' => [
            'langConstPrefix' => 'PLG_TASK_XDECARONOTIFICATIONS_MAINTENANCE',
            'form' => 'maintenance',
            'method' => 'runMaintenance',
        ],
    ];

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList' => 'advertiseRoutines',
            'onExecuteTask' => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }

    protected function processQueue(ExecuteTaskEvent $event): int
    {
        try {
            $component = $this->bootComponent();
            $global = ComponentHelper::getParams('com_xdecaronotifications');
            $params = $event->getArgument('params');
            $batch = max(1, min(100, (int) ($params->batch ?? $global->get('worker_batch', 25))));
            $maxAttempts = max(1, min(50, (int) ($params->max_attempts ?? $global->get('max_attempts', 5))));
            $stats = $component->getDeliveryService()->processPending($batch, $maxAttempts);

            $this->logTask(sprintf(
                'Notifications queue: processed=%d delivered=%d failed=%d missing_adapter=%d skipped=%d',
                $stats['processed'],
                $stats['delivered'],
                $stats['failed'],
                $stats['missing_adapter'],
                $stats['skipped']
            ));

            return Status::OK;
        } catch (Throwable $exception) {
            $this->logTask('Notifications queue error: ' . $exception->getMessage(), 'error');
            return Status::KNOCKOUT;
        }
    }

    protected function runMaintenance(ExecuteTaskEvent $event): int
    {
        try {
            $component = $this->bootComponent();
            $global = ComponentHelper::getParams('com_xdecaronotifications');
            $params = $event->getArgument('params');
            $archiveExpired = (int) ($params->archive_expired ?? $global->get('archive_expired', 1)) === 1;
            $retentionDays = max(7, min(3650, (int) ($params->retention_days ?? $global->get('attempt_retention_days', 90))));
            $stats = $component->getMaintenanceService()->run($archiveExpired, $retentionDays);

            $this->logTask(sprintf(
                'Notifications maintenance: archived_expired=%d purged_attempts=%d',
                $stats['archived_expired'],
                $stats['purged_attempts']
            ));

            return Status::OK;
        } catch (Throwable $exception) {
            $this->logTask('Notifications maintenance error: ' . $exception->getMessage(), 'error');
            return Status::KNOCKOUT;
        }
    }

    private function bootComponent(): NotificationsComponent
    {
        $component = Factory::getApplication()->bootComponent('com_xdecaronotifications');

        if (!$component instanceof NotificationsComponent) {
            throw new \RuntimeException('Notifications component is unavailable.');
        }

        return $component;
    }
}
