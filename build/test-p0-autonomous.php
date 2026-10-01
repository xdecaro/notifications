<?php

declare(strict_types=1);

namespace {
    define('_JEXEC', 1);
}

namespace Joomla\CMS\Plugin {
    class CMSPlugin
    {
        public function __construct(array $config = [])
        {
        }
    }
}

namespace Joomla\CMS\User {
    interface UserFactoryInterface
    {
        public function loadUserById(int $id);
    }
}

namespace Joomla\Event {
    interface SubscriberInterface
    {
        public static function getSubscribedEvents(): array;
    }
}

namespace Joomla\CMS\Mail {
    interface MailerFactoryInterface
    {
    }
}

namespace Joomla\CMS {
    final class Factory
    {
    }
}

namespace Xdecaro\Component\Notifications\Administrator\Event {
    final class RegisterChannelsEvent
    {
        public const NAME = 'onXdecaroNotificationsRegisterChannels';
    }
}

namespace NotificationsP0Test {
    use Joomla\CMS\User\UserFactoryInterface;
    use ReflectionMethod;
    use RuntimeException;
    use Xdecaro\Component\Notifications\Administrator\Service\DeliveryChannelInterface;
    use Xdecaro\Component\Notifications\Administrator\Service\DeliveryResult;
    use Xdecaro\Plugin\Notifications\Email\Extension\Email;

    $root = dirname(__DIR__);
    $failures = [];

    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (!$condition) {
            $failures[] = $message;
        }
    };

    require $root . '/component/admin/src/Service/DeliveryChannelInterface.php';
    require $root . '/component/admin/src/Service/DeliveryResult.php';

    $check(method_exists(DeliveryResult::class, 'submitted'), 'DeliveryResult::submitted() is missing.');
    $check(method_exists(DeliveryResult::class, 'outcomeUnknown'), 'DeliveryResult::outcomeUnknown() is missing.');
    $check(method_exists(DeliveryResult::class, 'getStatus'), 'DeliveryResult::getStatus() is missing.');

    if (method_exists(DeliveryResult::class, 'submitted') && method_exists(DeliveryResult::class, 'getStatus')) {
        $submitted = DeliveryResult::submitted('provider:accepted');
        $check($submitted->getStatus() === 'submitted', 'SUBMITTED result must expose status=submitted.');
        $check($submitted->isSuccess(), 'SUBMITTED must remain a successful adapter result for backward compatibility.');
    }

    if (method_exists(DeliveryResult::class, 'delivered') && method_exists(DeliveryResult::class, 'getStatus')) {
        $delivered = DeliveryResult::delivered('provider:delivered');
        $check($delivered->getStatus() === 'delivered', 'DELIVERED result must expose status=delivered.');
    }

    if (method_exists(DeliveryResult::class, 'failed') && method_exists(DeliveryResult::class, 'getStatus')) {
        $failed = DeliveryResult::failed('temporary_unavailable', 'Definitely not sent.', 60);
        $check($failed->getStatus() === 'failed', 'FAILED result must expose status=failed.');
        $check($failed->getRetryAfterSeconds() === 60, 'Certain failure must preserve retry delay.');
    }

    if (method_exists(DeliveryResult::class, 'outcomeUnknown') && method_exists(DeliveryResult::class, 'getStatus')) {
        $unknown = DeliveryResult::outcomeUnknown('provider_outcome_unknown', 'Provider outcome cannot be determined.');
        $check($unknown->getStatus() === 'outcome_unknown', 'OUTCOME_UNKNOWN result must expose status=outcome_unknown.');
        $check(!$unknown->isSuccess(), 'OUTCOME_UNKNOWN must not be reported as successful delivery.');
        $check($unknown->getRetryAfterSeconds() === null, 'OUTCOME_UNKNOWN must not request blind retry by default.');
    }

    $markerFile = $root . '/component/admin/src/Service/IdempotentDeliveryChannelInterface.php';
    $semanticsFile = $root . '/component/admin/src/Service/DeliverySemantics.php';
    $check(is_file($markerFile), 'IdempotentDeliveryChannelInterface is missing.');
    $check(is_file($semanticsFile), 'DeliverySemantics is missing.');

    if (is_file($markerFile) && is_file($semanticsFile)) {
        require $markerFile;
        require $semanticsFile;

        $idempotent = new class implements \Xdecaro\Component\Notifications\Administrator\Service\IdempotentDeliveryChannelInterface {
            public function getName(): string
            {
                return 'idempotent_test';
            }

            public function deliver(array $notification, array $context = []): DeliveryResult
            {
                return DeliveryResult::submitted('idempotent-test');
            }
        };

        $plain = new class implements DeliveryChannelInterface {
            public function getName(): string
            {
                return 'plain_test';
            }

            public function deliver(array $notification, array $context = []): DeliveryResult
            {
                return DeliveryResult::submitted('plain-test');
            }
        };

        $semantics = \Xdecaro\Component\Notifications\Administrator\Service\DeliverySemantics::class;
        $check($semantics::publicStatus('pending') === 'request_accepted', 'pending must map to REQUEST_ACCEPTED.');
        $check($semantics::publicStatus('processing') === 'request_accepted', 'processing must not imply provider acceptance.');
        $check($semantics::publicStatus('retry') === 'request_accepted', 'retry must map to REQUEST_ACCEPTED.');
        $check($semantics::publicStatus('submitted') === 'submitted', 'submitted mapping is wrong.');
        $check($semantics::publicStatus('delivered') === 'delivered', 'delivered mapping is wrong.');
        $check($semantics::publicStatus('failed') === 'failed', 'failed mapping is wrong.');
        $check($semantics::publicStatus('outcome_unknown') === 'outcome_unknown', 'outcome_unknown mapping is wrong.');
        $check($semantics::canSafelyRetryUnknown($idempotent), 'Idempotent provider must permit safe retry after unknown outcome.');
        $check(!$semantics::canSafelyRetryUnknown($plain), 'Provider without idempotency guarantee must not be retried after unknown outcome.');
        $key1 = $semantics::idempotencyKey(17, 'idempotent_test');
        $key2 = $semantics::idempotencyKey(17, 'idempotent_test');
        $check($key1 !== '' && $key1 === $key2, 'Provider idempotency key must be stable across retries.');
    }

    final class FakeUserFactory implements UserFactoryInterface
    {
        /** @var object */
        private $user;

        public function __construct(object $user)
        {
            $this->user = $user;
        }

        public function loadUserById(int $id)
        {
            return $this->user;
        }
    }

    require $root . '/plugins/xdecaronotifications/email/src/Extension/Email.php';

    $factory = new FakeUserFactory((object) [
        'id' => 42,
        'block' => 0,
        'email' => 'canonical@example.invalid',
        'name' => 'Canonical User',
    ]);
    $email = new Email([], $factory);
    $resolver = new ReflectionMethod($email, 'resolveRecipient');
    $resolver->setAccessible(true);

    $userResolved = $resolver->invoke($email, [
        'recipient_type' => 'user',
        'recipient_id' => '42',
    ], [
        'email' => 'attacker@example.invalid',
    ]);

    $check(($userResolved[0] ?? '') === '', 'Joomla user recipient must not be redirected to context[email].');
    $check(($userResolved[2] ?? '') === 'recipient_email_override_forbidden', 'Forbidden Joomla-user email override must be explicit.');

    $canonicalResolved = $resolver->invoke($email, [
        'recipient_type' => 'user',
        'recipient_id' => '42',
    ], [
        'email' => 'canonical@example.invalid',
    ]);
    $check(($canonicalResolved[0] ?? '') === 'canonical@example.invalid', 'Canonical Joomla email must remain usable.');
    $check(($canonicalResolved[2] ?? null) === null, 'Canonical Joomla email must not be treated as an override violation.');

    $externalResolved = $resolver->invoke($email, [
        'recipient_type' => 'external',
        'recipient_id' => 'person-7',
    ], [
        'email' => 'external@example.invalid',
        'recipient_name' => 'External Recipient',
    ]);
    $check(($externalResolved[0] ?? '') === 'external@example.invalid', 'Explicit email must remain supported for non-user recipients.');
    $check(($externalResolved[2] ?? null) === null, 'Valid non-user explicit email must be authorized by recipient type.');

    $notificationSource = file_get_contents($root . '/component/admin/src/Service/NotificationService.php');
    $installSql = file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
    $deliverySource = file_get_contents($root . '/component/admin/src/Service/DeliveryService.php');
    $emailSource = file_get_contents($root . '/plugins/xdecaronotifications/email/src/Extension/Email.php');

    $check(strpos($notificationSource, 'findByExternalKey($sourceComponent, $externalKey, $recipientType, $recipientId)') !== false, 'external_key lookup must include recipient type and id.');
    $check(strpos($installSql, '`source_component`, `external_key`, `recipient_type`, `recipient_id`') !== false, 'Clean-install unique key must include recipient identity.');
    $check(strpos($deliverySource, "'outcome_unknown'") !== false, 'DeliveryService must persist outcome_unknown.');
    $check(strpos($deliverySource, "'delivery_status'") !== false, 'Delivery status API must expose semantic delivery_status.');
    $check(strpos($emailSource, 'DeliveryResult::submitted(') !== false, 'Email transport acceptance must map to SUBMITTED, not DELIVERED.');

    if ($failures !== []) {
        fwrite(STDERR, "Notifications autonomous P0 test failures:\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    fwrite(STDOUT, "Notifications autonomous P0 PHP tests: PASS\n");
}
