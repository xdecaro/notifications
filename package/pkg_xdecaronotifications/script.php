<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class pkg_xdecaronotificationsInstallerScript
{
    private const MODULE_ELEMENT = 'mod_xdecaronotifications';
    private const MODULE_MARKER = 'xdecaro_notifications_bell_provisioned';

    public function postflight($type, $parent): void
    {
        // Enable bundled plugins only on first installation/discovery. Updates
        // must preserve an administrator's explicit enabled/disabled choices.
        if (in_array((string) $type, ['install', 'discover_install'], true)) {
            $this->enablePlugin('task', 'xdecaronotifications');
            $this->enablePlugin('xdecaronotifications', 'email');
        }

        // The bell module is a package feature introduced in 1.1.0. Provision
        // its first status-bar instance, but never overwrite an administrator's
        // later position/published choices on subsequent updates.
        $this->ensureAdministratorBellModule();
    }

    private function enablePlugin(string $folder, string $element): void
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $enabled = 1;
        $type = 'plugin';

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = :enabled')
            ->where($db->quoteName('type') . ' = :type')
            ->where($db->quoteName('folder') . ' = :folder')
            ->where($db->quoteName('element') . ' = :element')
            ->bind(':enabled', $enabled, ParameterType::INTEGER)
            ->bind(':type', $type)
            ->bind(':folder', $folder)
            ->bind(':element', $element);

        $db->setQuery($query)->execute();
    }

    private function ensureAdministratorBellModule(): void
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $clientId = 1;
        $element = self::MODULE_ELEMENT;

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('published'),
                $db->quoteName('position'),
                $db->quoteName('params'),
            ])
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('module') . ' = :element')
            ->where($db->quoteName('client_id') . ' = :client_id')
            ->order($db->quoteName('id') . ' ASC')
            ->bind(':element', $element)
            ->bind(':client_id', $clientId, ParameterType::INTEGER);

        $rows = (array) $db->setQuery($query)->loadObjectList();

        foreach ($rows as $row) {
            $params = json_decode((string) ($row->params ?? ''), true);

            if (is_array($params) && !empty($params[self::MODULE_MARKER])) {
                return;
            }

            if (trim((string) ($row->position ?? '')) !== '' || (int) ($row->published ?? 0) !== 0) {
                // Existing administrator configuration: never overwrite it.
                return;
            }
        }

        $orderingQuery = $db->getQuery(true)
            ->select('COALESCE(MAX(' . $db->quoteName('ordering') . '), 0)')
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('client_id') . ' = :client_id')
            ->where($db->quoteName('position') . ' = :position');
        $statusPosition = 'status';
        $orderingQuery->bind(':client_id', $clientId, ParameterType::INTEGER)
            ->bind(':position', $statusPosition);
        $ordering = (int) $db->setQuery($orderingQuery)->loadResult() + 1;
        $params = json_encode([self::MODULE_MARKER => 1]);

        if ($rows) {
            $moduleId = (int) $rows[0]->id;
            $published = 1;
            $showTitle = 0;
            $access = 1;
            $title = 'Notifications';

            $update = $db->getQuery(true)
                ->update($db->quoteName('#__modules'))
                ->set($db->quoteName('title') . ' = :title')
                ->set($db->quoteName('ordering') . ' = :ordering')
                ->set($db->quoteName('position') . ' = :position')
                ->set($db->quoteName('published') . ' = :published')
                ->set($db->quoteName('showtitle') . ' = :showtitle')
                ->set($db->quoteName('access') . ' = :access')
                ->set($db->quoteName('params') . ' = :params')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':title', $title)
                ->bind(':ordering', $ordering, ParameterType::INTEGER)
                ->bind(':position', $statusPosition)
                ->bind(':published', $published, ParameterType::INTEGER)
                ->bind(':showtitle', $showTitle, ParameterType::INTEGER)
                ->bind(':access', $access, ParameterType::INTEGER)
                ->bind(':params', $params)
                ->bind(':id', $moduleId, ParameterType::INTEGER);

            $db->setQuery($update)->execute();
        } else {
            $module = (object) [
                'title' => 'Notifications',
                'note' => '',
                'content' => '',
                'ordering' => $ordering,
                'position' => $statusPosition,
                'published' => 1,
                'module' => self::MODULE_ELEMENT,
                'access' => 1,
                'showtitle' => 0,
                'params' => $params,
                'client_id' => 1,
                'language' => '*',
            ];

            $db->insertObject('#__modules', $module, 'id');
            $moduleId = (int) $module->id;
        }

        $this->assignModuleToAllPages($db, $moduleId);
    }

    private function assignModuleToAllPages(DatabaseInterface $db, int $moduleId): void
    {
        $delete = $db->getQuery(true)
            ->delete($db->quoteName('#__modules_menu'))
            ->where($db->quoteName('moduleid') . ' = :moduleid')
            ->bind(':moduleid', $moduleId, ParameterType::INTEGER);
        $db->setQuery($delete)->execute();

        $assignment = (object) [
            'moduleid' => $moduleId,
            'menuid' => 0,
        ];
        $db->insertObject('#__modules_menu', $assignment);
    }
}
