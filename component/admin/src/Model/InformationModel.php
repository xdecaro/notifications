<?php
namespace Xdecaro\Component\Notifications\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Throwable;
use Xdecaro\Component\Notifications\Administrator\Extension\NotificationsComponent;

final class InformationModel extends BaseDatabaseModel
{
    public function getProduct(): array
    {
        $extension = $this->getExtension('component', 'com_xdecaronotifications');
        return ['name' => 'Notifications by xdecaro', 'version' => $extension['version'] ?? '1.0.0', 'installed' => $extension !== null, 'enabled' => $extension === null ? false : (bool) $extension['enabled']];
    }

    public function getEnvironment(): array
    {
        try { $databaseVersion = (string) $this->getDatabase()->getVersion(); } catch (Throwable $exception) { $databaseVersion = ''; }
        return ['joomla' => defined('JVERSION') ? (string) JVERSION : '', 'php' => PHP_VERSION, 'database' => $databaseVersion];
    }

    public function getIncludedExtensions(): array
    {
        return [
            $this->extensionRow('component', 'com_xdecaronotifications', '', 'com_xdecaronotifications'),
            $this->extensionRow('plugin', 'xdecaronotifications', 'task', 'plg_task_xdecaronotifications'),
            $this->extensionRow('plugin', 'email', 'xdecaronotifications', 'plg_xdecaronotifications_email'),
        ];
    }

    public function getUpdateInfo(): array
    {
        $package = $this->getExtension('package', 'pkg_xdecaronotifications');
        return ['package_installed' => $package !== null, 'package_version' => $package['version'] ?? '', 'feed' => 'https://raw.githubusercontent.com/xdecaro/notifications/main/updates/pkg_xdecaronotifications.xml'];
    }

    public function getConnectedComponents(): array
    {
        $db = $this->getDatabase();
        $type = 'component'; $self = 'com_xdecaronotifications'; $pattern = 'com_xdecaro%';
        $query = $db->getQuery(true)
            ->select([$db->quoteName('element'), $db->quoteName('manifest_cache'), $db->quoteName('enabled')])
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = :type')
            ->where($db->quoteName('element') . ' LIKE :pattern')
            ->where($db->quoteName('element') . ' <> :self')
            ->order($db->quoteName('element') . ' ASC')
            ->bind(':type', $type)->bind(':pattern', $pattern)->bind(':self', $self);
        $rows = (array) $db->setQuery($query)->loadAssocList();
        foreach ($rows as &$row) { $manifest = $this->decodeManifest((string) ($row['manifest_cache'] ?? '')); $row['version'] = (string) ($manifest['version'] ?? ''); $row['enabled'] = (bool) ($row['enabled'] ?? false); }
        unset($row);
        return $rows;
    }

    public function getDiagnostics(): array
    {
        $db = $this->getDatabase(); $prefix = $db->getPrefix(); $tables = (array) $db->getTableList();
        $expected = ['#__xdecaronotifications_items','#__xdecaronotifications_preferences','#__xdecaronotifications_deliveries','#__xdecaronotifications_delivery_attempts'];
        $tableStatus = [];
        foreach ($expected as $table) { $tableStatus[$table] = in_array(str_replace('#__', $prefix, $table), $tables, true); }
        $channels = [];
        try { $component = Factory::getApplication()->bootComponent('com_xdecaronotifications'); if ($component instanceof NotificationsComponent) { $channels = $component->getChannelRegistry()->getNames(); } } catch (Throwable $exception) { $channels = []; }
        return [
            'tables' => $tableStatus,
            'core_available' => class_exists('Xdecaro\\Core\\Integration\\Capability'),
            'channels' => $channels,
            'scheduler_plugin' => $this->extensionEnabled('plugin', 'xdecaronotifications', 'task'),
            'email_plugin' => $this->extensionEnabled('plugin', 'email', 'xdecaronotifications'),
            'queue_task' => $this->schedulerTaskEnabled('xdecaronotifications.queue', $tables),
            'maintenance_task' => $this->schedulerTaskEnabled('xdecaronotifications.maintenance', $tables),
        ];
    }

    private function getExtension(string $type, string $element, string $folder = ''): ?array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select([$db->quoteName('extension_id'),$db->quoteName('enabled'),$db->quoteName('manifest_cache')])->from($db->quoteName('#__extensions'))->where($db->quoteName('type') . ' = :type')->where($db->quoteName('element') . ' = :element')->bind(':type', $type)->bind(':element', $element);
        if ($folder !== '') { $query->where($db->quoteName('folder') . ' = :folder')->bind(':folder', $folder); }
        $row = $db->setQuery($query, 0, 1)->loadAssoc();
        if ($row === null) { return null; }
        $manifest = $this->decodeManifest((string) ($row['manifest_cache'] ?? ''));
        return ['extension_id' => (int) $row['extension_id'], 'enabled' => (int) $row['enabled'], 'version' => (string) ($manifest['version'] ?? '')];
    }

    private function extensionRow(string $type, string $element, string $folder, string $label): array
    {
        $extension = $this->getExtension($type, $element, $folder);
        return ['label' => $label, 'installed' => $extension !== null, 'enabled' => $extension !== null && (bool) $extension['enabled'], 'version' => $extension['version'] ?? ''];
    }

    private function extensionEnabled(string $type, string $element, string $folder = ''): bool
    {
        $extension = $this->getExtension($type, $element, $folder);
        return $extension !== null && (bool) $extension['enabled'];
    }

    private function schedulerTaskEnabled(string $type, array $tables): bool
    {
        $db = $this->getDatabase();
        if (!in_array($db->getPrefix() . 'scheduler_tasks', $tables, true)) { return false; }
        try {
            $state = 1;
            $query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__scheduler_tasks'))->where($db->quoteName('type') . ' = :type')->where($db->quoteName('state') . ' = :state')->bind(':type', $type)->bind(':state', $state, ParameterType::INTEGER);
            return (int) $db->setQuery($query)->loadResult() > 0;
        } catch (Throwable $exception) { return false; }
    }

    private function decodeManifest(string $manifest): array
    {
        if ($manifest === '') { return []; }
        $decoded = json_decode($manifest, true);
        return is_array($decoded) ? $decoded : [];
    }
}
