<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;
use Throwable;

final class DeliveryService
{
    /** @var DatabaseInterface */
    private $db;

    /** @var PreferenceService */
    private $preferences;

    /** @var ChannelRegistry */
    private $channels;

    public function __construct(DatabaseInterface $db, PreferenceService $preferences, ChannelRegistry $channels)
    {
        $this->db          = $db;
        $this->preferences = $preferences;
        $this->channels    = $channels;
    }

    /** @param array<int,string> $channels @param array<string,mixed> $context @return array<string,int> */
    public function queueForNotification(int $notificationId, array $channels, array $context = [], bool $defaultEnabled = true): array
    {
        $notification = $this->getNotification($notificationId);
        $enabled = $this->preferences->filterEnabledChannels(
            (string) $notification['recipient_type'],
            (string) $notification['recipient_id'],
            (string) $notification['category'],
            $channels,
            $defaultEnabled
        );

        $result = [];
        foreach ($enabled as $channel) {
            $result[$channel] = $this->queue($notificationId, $channel, $context);
        }

        return $result;
    }

    /** @param array<string,mixed> $context */
    public function queue(int $notificationId, string $channel, array $context = [], $availableAt = null): int
    {
        if ($notificationId < 1) {
            throw new InvalidArgumentException('Invalid notification ID.');
        }

        $this->getNotification($notificationId);
        $channel       = $this->validateChannel($channel);
        $contextJson   = $this->encodeContext($context);
        $availableSql  = $this->normalizeSqlDate($availableAt) ?: Factory::getDate()->toSql();
        $now           = Factory::getDate()->toSql();
        $pendingState  = 'pending';

        $existing = $this->findDeliveryId($notificationId, $channel);
        if ($existing !== null) {
            return $existing;
        }

        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__xdecaronotifications_deliveries'))
            ->columns([
                $this->db->quoteName('notification_id'),
                $this->db->quoteName('channel'),
                $this->db->quoteName('state'),
                $this->db->quoteName('context'),
                $this->db->quoteName('attempts'),
                $this->db->quoteName('available_at'),
                $this->db->quoteName('created'),
                $this->db->quoteName('updated'),
            ])
            ->values(':notification_id,:channel,:state,:context,0,:available_at,:created,:updated')
            ->bind(':notification_id', $notificationId, ParameterType::INTEGER)
            ->bind(':channel', $channel)
            ->bind(':state', $pendingState)
            ->bind(':context', $contextJson)
            ->bind(':available_at', $availableSql)
            ->bind(':created', $now)
            ->bind(':updated', $now);

        try {
            $this->db->setQuery($query)->execute();
        } catch (RuntimeException $exception) {
            $existing = $this->findDeliveryId($notificationId, $channel);
            if ($existing !== null) {
                return $existing;
            }
            throw $exception;
        }

        return (int) $this->db->insertid();
    }

    /** @return array{processed:int,delivered:int,failed:int,missing_adapter:int,skipped:int} */
    public function processPending(int $limit = 25, int $maxAttempts = 5): array
    {
        $limit       = max(1, min(100, $limit));
        $maxAttempts = max(1, min(50, $maxAttempts));

        // A worker may terminate after claiming a delivery. Recover claims that
        // have been stuck for 15 minutes so they can safely re-enter the queue.
        $this->recoverStaleClaims(900);

        $rows = $this->getPendingCandidates($limit);
        $stats = [
            'processed'       => 0,
            'delivered'       => 0,
            'failed'          => 0,
            'missing_adapter' => 0,
            'skipped'         => 0,
        ];

        foreach ($rows as $row) {
            $deliveryId = (int) $row['id'];

            if ((int) $row['attempts'] >= $maxAttempts) {
                $this->markExhausted($deliveryId);
                $stats['failed']++;
                continue;
            }

            if (!$this->claim($deliveryId)) {
                $stats['skipped']++;
                continue;
            }

            $channel = $this->channels->get((string) $row['channel']);
            if ($channel === null) {
                $this->releaseClaim($deliveryId, 300, 'Delivery adapter is not registered.');
                $stats['missing_adapter']++;
                continue;
            }

            $stats['processed']++;
            $notification = $this->getNotification((int) $row['notification_id']);
            $context      = $this->decodeContext($row['context'] ?? null);

            try {
                $result = $channel->deliver($notification, $context);
            } catch (Throwable $exception) {
                $result = DeliveryResult::failed(
                    'channel_exception',
                    $exception->getMessage() !== '' ? $exception->getMessage() : get_class($exception),
                    300
                );
            }

            if ($result->isSuccess()) {
                $this->recordAttempt(
                    $deliveryId,
                    (string) $row['channel'],
                    'delivered',
                    $result->getProviderReference(),
                    null,
                    null,
                    null
                );
                $stats['delivered']++;
                continue;
            }

            $retryAfter = $result->getRetryAfterSeconds();
            if ((int) $row['attempts'] + 1 >= $maxAttempts) {
                $retryAfter = null;
            }

            $retryAt = $retryAfter !== null
                ? Factory::getDate('+' . max(1, $retryAfter) . ' seconds')->toSql()
                : null;

            $this->recordAttempt(
                $deliveryId,
                (string) $row['channel'],
                'failed',
                null,
                (string) $result->getErrorCode(),
                (string) $result->getErrorMessage(),
                $retryAt
            );
            $stats['failed']++;
        }

        return $stats;
    }

    /** @return array<int,array<string,mixed>> */
    public function getStatuses(int $notificationId): array
    {
        if ($notificationId < 1) {
            throw new InvalidArgumentException('Invalid notification ID.');
        }

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('id'),
                $this->db->quoteName('channel'),
                $this->db->quoteName('state'),
                $this->db->quoteName('attempts'),
                $this->db->quoteName('available_at'),
                $this->db->quoteName('delivered_at'),
                $this->db->quoteName('provider_reference'),
                $this->db->quoteName('last_error'),
                $this->db->quoteName('updated'),
            ])
            ->from($this->db->quoteName('#__xdecaronotifications_deliveries'))
            ->where($this->db->quoteName('notification_id') . ' = :notification_id')
            ->order($this->db->quoteName('channel') . ' ASC')
            ->bind(':notification_id', $notificationId, ParameterType::INTEGER);

        return (array) $this->db->setQuery($query)->loadAssocList();
    }

    /** @return array<int,array<string,mixed>> */
    private function getPendingCandidates(int $limit): array
    {
        $now          = Factory::getDate()->toSql();
        $pendingState = 'pending';
        $retryState   = 'retry';

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('id'),
                $this->db->quoteName('notification_id'),
                $this->db->quoteName('channel'),
                $this->db->quoteName('context'),
                $this->db->quoteName('attempts'),
            ])
            ->from($this->db->quoteName('#__xdecaronotifications_deliveries'))
            ->where('(' . $this->db->quoteName('state') . ' = :pending_state OR ' . $this->db->quoteName('state') . ' = :retry_state)')
            ->where($this->db->quoteName('available_at') . ' <= :now')
            ->order($this->db->quoteName('available_at') . ' ASC, ' . $this->db->quoteName('id') . ' ASC')
            ->bind(':pending_state', $pendingState)
            ->bind(':retry_state', $retryState)
            ->bind(':now', $now);

        return (array) $this->db->setQuery($query, 0, $limit)->loadAssocList();
    }

    private function recoverStaleClaims(int $timeoutSeconds): void
    {
        $timeoutSeconds  = max(60, min(86400, $timeoutSeconds));
        $cutoff          = Factory::getDate('-' . $timeoutSeconds . ' seconds')->toSql();
        $now             = Factory::getDate()->toSql();
        $retryState      = 'retry';
        $processingState = 'processing';
        $error           = 'Recovered stale delivery claim after worker interruption.';

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__xdecaronotifications_deliveries'))
            ->set($this->db->quoteName('state') . ' = :retry_state')
            ->set($this->db->quoteName('available_at') . ' = :available_at')
            ->set($this->db->quoteName('updated') . ' = :updated')
            ->set($this->db->quoteName('last_error') . ' = :last_error')
            ->where($this->db->quoteName('state') . ' = :processing_state')
            ->where($this->db->quoteName('updated') . ' < :cutoff')
            ->bind(':retry_state', $retryState)
            ->bind(':available_at', $now)
            ->bind(':updated', $now)
            ->bind(':last_error', $error)
            ->bind(':processing_state', $processingState)
            ->bind(':cutoff', $cutoff);

        $this->db->setQuery($query)->execute();
    }

    private function claim(int $deliveryId): bool
    {
        $now             = Factory::getDate()->toSql();
        $processingState = 'processing';
        $pendingState    = 'pending';
        $retryState      = 'retry';

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__xdecaronotifications_deliveries'))
            ->set($this->db->quoteName('state') . ' = :processing_state')
            ->set($this->db->quoteName('updated') . ' = :updated')
            ->where($this->db->quoteName('id') . ' = :id')
            ->where('(' . $this->db->quoteName('state') . ' = :pending_state OR ' . $this->db->quoteName('state') . ' = :retry_state)')
            ->where($this->db->quoteName('available_at') . ' <= :now')
            ->bind(':processing_state', $processingState)
            ->bind(':updated', $now)
            ->bind(':id', $deliveryId, ParameterType::INTEGER)
            ->bind(':pending_state', $pendingState)
            ->bind(':retry_state', $retryState)
            ->bind(':now', $now);

        $this->db->setQuery($query)->execute();

        return (int) $this->db->getAffectedRows() === 1;
    }

    private function releaseClaim(int $deliveryId, int $delaySeconds, string $reason): void
    {
        $available       = Factory::getDate('+' . max(1, $delaySeconds) . ' seconds')->toSql();
        $now             = Factory::getDate()->toSql();
        $reason          = $this->truncateNullable($reason, 1000);
        $pendingState    = 'pending';
        $processingState = 'processing';

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__xdecaronotifications_deliveries'))
            ->set($this->db->quoteName('state') . ' = :state')
            ->set($this->db->quoteName('available_at') . ' = :available_at')
            ->set($this->db->quoteName('updated') . ' = :updated')
            ->set($this->db->quoteName('last_error') . ' = :last_error')
            ->where($this->db->quoteName('id') . ' = :id')
            ->where($this->db->quoteName('state') . ' = :processing')
            ->bind(':state', $pendingState)
            ->bind(':available_at', $available)
            ->bind(':updated', $now)
            ->bind(':last_error', $reason)
            ->bind(':id', $deliveryId, ParameterType::INTEGER)
            ->bind(':processing', $processingState);

        $this->db->setQuery($query)->execute();
    }

    private function markExhausted(int $deliveryId): void
    {
        $now         = Factory::getDate()->toSql();
        $failedState = 'failed';
        $error       = 'Maximum delivery attempts reached.';

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__xdecaronotifications_deliveries'))
            ->set($this->db->quoteName('state') . ' = :state')
            ->set($this->db->quoteName('updated') . ' = :updated')
            ->set($this->db->quoteName('last_error') . ' = :last_error')
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':state', $failedState)
            ->bind(':updated', $now)
            ->bind(':last_error', $error)
            ->bind(':id', $deliveryId, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();
    }

    /** @return array<string,mixed> */
    private function getNotification(int $id): array
    {
        if ($id < 1) {
            throw new InvalidArgumentException('Invalid notification ID.');
        }

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('id'),
                $this->db->quoteName('source_component'),
                $this->db->quoteName('source_entity'),
                $this->db->quoteName('source_id'),
                $this->db->quoteName('recipient_type'),
                $this->db->quoteName('recipient_id'),
                $this->db->quoteName('category'),
                $this->db->quoteName('priority'),
                $this->db->quoteName('title'),
                $this->db->quoteName('message'),
                $this->db->quoteName('action_url'),
                $this->db->quoteName('payload'),
                $this->db->quoteName('created'),
                $this->db->quoteName('expires_at'),
            ])
            ->from($this->db->quoteName('#__xdecaronotifications_items'))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $row = $this->db->setQuery($query)->loadAssoc();
        if ($row === null) {
            throw new InvalidArgumentException('Notification does not exist.');
        }

        if (!empty($row['payload'])) {
            $decoded = json_decode((string) $row['payload'], true);
            $row['payload'] = is_array($decoded) ? $decoded : [];
        } else {
            $row['payload'] = [];
        }

        return $row;
    }

    private function findDeliveryId(int $notificationId, string $channel): ?int
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__xdecaronotifications_deliveries'))
            ->where($this->db->quoteName('notification_id') . ' = :notification_id')
            ->where($this->db->quoteName('channel') . ' = :channel')
            ->bind(':notification_id', $notificationId, ParameterType::INTEGER)
            ->bind(':channel', $channel);

        $value = $this->db->setQuery($query)->loadResult();

        return $value === null ? null : (int) $value;
    }

    private function recordAttempt(
        int $deliveryId,
        string $provider,
        string $attemptState,
        ?string $providerReference,
        ?string $errorCode,
        ?string $errorMessage,
        ?string $retryAt
    ): void {
        $now               = Factory::getDate()->toSql();
        $provider          = $this->validateChannel($provider);
        $providerReference = $this->truncateNullable($providerReference, 191);
        $errorCode         = $this->truncateNullable($errorCode, 64);
        $errorMessage      = $this->truncateNullable($errorMessage, 1000);
        $processingState   = 'processing';

        $this->db->transactionStart();

        try {
            $attempt = $this->db->getQuery(true)
                ->insert($this->db->quoteName('#__xdecaronotifications_delivery_attempts'))
                ->columns([
                    $this->db->quoteName('delivery_id'),
                    $this->db->quoteName('provider'),
                    $this->db->quoteName('state'),
                    $this->db->quoteName('provider_reference'),
                    $this->db->quoteName('error_code'),
                    $this->db->quoteName('error_message'),
                    $this->db->quoteName('created'),
                ])
                ->values(':delivery_id,:provider,:state,:provider_reference,:error_code,:error_message,:created')
                ->bind(':delivery_id', $deliveryId, ParameterType::INTEGER)
                ->bind(':provider', $provider)
                ->bind(':state', $attemptState)
                ->bind(':provider_reference', $providerReference)
                ->bind(':error_code', $errorCode)
                ->bind(':error_message', $errorMessage)
                ->bind(':created', $now);
            $this->db->setQuery($attempt)->execute();

            $deliveryState = $attemptState === 'delivered'
                ? 'delivered'
                : ($retryAt !== null ? 'retry' : 'failed');

            $sets = [
                $this->db->quoteName('state') . ' = :delivery_state',
                $this->db->quoteName('attempts') . ' = ' . $this->db->quoteName('attempts') . ' + 1',
                $this->db->quoteName('updated') . ' = :updated',
                $this->db->quoteName('provider_reference') . ' = :provider_reference',
                $this->db->quoteName('last_error') . ' = :last_error',
            ];

            if ($deliveryState === 'delivered') {
                $sets[] = $this->db->quoteName('delivered_at') . ' = :delivered_at';
            }
            if ($retryAt !== null) {
                $sets[] = $this->db->quoteName('available_at') . ' = :available_at';
            }

            $delivery = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__xdecaronotifications_deliveries'))
                ->set($sets)
                ->where($this->db->quoteName('id') . ' = :delivery_id')
                ->where($this->db->quoteName('state') . ' = :processing_state')
                ->bind(':delivery_state', $deliveryState)
                ->bind(':updated', $now)
                ->bind(':provider_reference', $providerReference)
                ->bind(':last_error', $errorMessage)
                ->bind(':delivery_id', $deliveryId, ParameterType::INTEGER)
                ->bind(':processing_state', $processingState);

            if ($deliveryState === 'delivered') {
                $delivery->bind(':delivered_at', $now);
            }
            if ($retryAt !== null) {
                $delivery->bind(':available_at', $retryAt);
            }

            $this->db->setQuery($delivery)->execute();
            if ((int) $this->db->getAffectedRows() !== 1) {
                throw new RuntimeException('Delivery claim was lost before persistence.');
            }

            $this->db->transactionCommit();
        } catch (Throwable $exception) {
            $this->db->transactionRollback();
            throw $exception;
        }
    }

    private function validateChannel(string $channel): string
    {
        $channel = strtolower(trim($channel));
        if ($channel === '' || strlen($channel) > 64 || !preg_match('/^[a-z][a-z0-9_.-]*$/', $channel)) {
            throw new InvalidArgumentException('Invalid notification channel.');
        }

        return $channel;
    }

    /** @param array<string,mixed> $context */
    private function encodeContext(array $context): ?string
    {
        if ($context === []) {
            return null;
        }

        $json = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new InvalidArgumentException('Delivery context cannot be encoded as JSON.');
        }

        return $json;
    }

    /** @return array<string,mixed> */
    private function decodeContext($context): array
    {
        if ($context === null || $context === '') {
            return [];
        }

        $decoded = json_decode((string) $context, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeSqlDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Factory::getDate($value)->toSql();
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Invalid delivery date.', 0, $exception);
        }
    }

    private function truncateNullable(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return strlen($value) <= $maxLength ? $value : substr($value, 0, $maxLength);
    }
}
