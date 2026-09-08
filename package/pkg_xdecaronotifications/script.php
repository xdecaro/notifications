<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class pkg_xdecaronotificationsInstallerScript
{
    public function postflight($type, $parent): void
    {
        if (!in_array((string) $type, ['install', 'update', 'discover_install'], true)) {
            return;
        }

        $this->enablePlugin('task', 'xdecaronotifications');
        $this->enablePlugin('xdecaronotifications', 'email');
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
}
