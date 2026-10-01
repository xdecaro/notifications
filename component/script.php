<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class com_xdecaronotificationsInstallerScript
{
    private const TABLE = '#__xdecaronotifications_items';
    private const LEGACY_INDEX = 'idx_notifications_external';
    private const RECIPIENT_INDEX = 'idx_notifications_external_recipient';

    public function postflight($type, $parent): void
    {
        if (!in_array((string) $type, ['install', 'update', 'discover_install'], true)) {
            return;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->ensureRecipientScopedExternalKey($db);
    }

    private function ensureRecipientScopedExternalKey(DatabaseInterface $db): void
    {
        $indexes = $this->loadIndexes($db);
        $recipientColumns = ['source_component', 'external_key', 'recipient_type', 'recipient_id'];
        $legacyColumns = ['source_component', 'external_key'];

        $recipientReady = $this->matchesUniqueIndex(
            $indexes[self::RECIPIENT_INDEX] ?? [],
            $recipientColumns
        );

        if (!$recipientReady) {
            // The 1.1.12 legacy UNIQUE constraint is stricter than the new one,
            // so every valid legacy dataset already satisfies this ADD. Add the
            // new protection first; only then remove the old restrictive index.
            $query = 'ALTER TABLE ' . $db->quoteName(self::TABLE)
                . ' ADD UNIQUE KEY ' . $db->quoteName(self::RECIPIENT_INDEX)
                . ' (' . implode(', ', array_map([$db, 'quoteName'], $recipientColumns)) . ')';
            $db->setQuery($query)->execute();
            $indexes = $this->loadIndexes($db);
        }

        if ($this->matchesUniqueIndex($indexes[self::LEGACY_INDEX] ?? [], $legacyColumns)) {
            $query = 'ALTER TABLE ' . $db->quoteName(self::TABLE)
                . ' DROP INDEX ' . $db->quoteName(self::LEGACY_INDEX);
            $db->setQuery($query)->execute();
        }
    }

    /** @return array<string,array<int,array{column:string,non_unique:int}>> */
    private function loadIndexes(DatabaseInterface $db): array
    {
        $rows = (array) $db->setQuery(
            'SHOW INDEX FROM ' . $db->quoteName(self::TABLE)
        )->loadAssocList();
        $indexes = [];

        foreach ($rows as $row) {
            $name = (string) ($row['Key_name'] ?? $row['key_name'] ?? '');
            $column = (string) ($row['Column_name'] ?? $row['column_name'] ?? '');
            $sequence = (int) ($row['Seq_in_index'] ?? $row['seq_in_index'] ?? 0);
            $nonUnique = (int) ($row['Non_unique'] ?? $row['non_unique'] ?? 1);

            if ($name === '' || $column === '' || $sequence < 1) {
                continue;
            }

            $indexes[$name][$sequence] = [
                'column' => $column,
                'non_unique' => $nonUnique,
            ];
        }

        foreach ($indexes as &$parts) {
            ksort($parts);
            $parts = array_values($parts);
        }
        unset($parts);

        return $indexes;
    }

    /**
     * @param array<int,array{column:string,non_unique:int}> $parts
     * @param array<int,string> $expectedColumns
     */
    private function matchesUniqueIndex(array $parts, array $expectedColumns): bool
    {
        if (count($parts) !== count($expectedColumns)) {
            return false;
        }

        foreach ($expectedColumns as $offset => $column) {
            if (($parts[$offset]['column'] ?? null) !== $column || (int) ($parts[$offset]['non_unique'] ?? 1) !== 0) {
                return false;
            }
        }

        return true;
    }
}
