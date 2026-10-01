<?php
namespace Xdecaro\Plugin\Notifications\Email\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Event\SubscriberInterface;
use Throwable;
use Xdecaro\Component\Notifications\Administrator\Event\RegisterChannelsEvent;
use Xdecaro\Component\Notifications\Administrator\Service\DeliveryChannelInterface;
use Xdecaro\Component\Notifications\Administrator\Service\DeliveryResult;

final class Email extends CMSPlugin implements SubscriberInterface, DeliveryChannelInterface
{
    /** @var UserFactoryInterface */
    private $userFactory;

    public function __construct(array $config, UserFactoryInterface $userFactory)
    {
        parent::__construct($config);
        $this->userFactory = $userFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [RegisterChannelsEvent::NAME => 'onRegisterChannels'];
    }

    public function onRegisterChannels(RegisterChannelsEvent $event): void
    {
        $event->getRegistry()->register($this);
    }

    public function getName(): string
    {
        return 'email';
    }

    public function deliver(array $notification, array $context = []): DeliveryResult
    {
        [$email, $name, $recipientError] = $this->resolveRecipient($notification, $context);

        if ($recipientError !== null) {
            return DeliveryResult::failed($recipientError, 'The requested email destination is not authorized for this recipient.');
        }

        if ($email === '') {
            return DeliveryResult::failed('recipient_email_missing', 'No valid email address is available for the notification recipient.');
        }

        $subject = $this->cleanHeader((string) ($context['subject'] ?? $notification['title'] ?? 'Notification'));
        if ($subject === '') {
            $subject = 'Notification';
        }

        $body = trim((string) ($notification['message'] ?? ''));
        $actionUrl = trim((string) ($notification['action_url'] ?? ''));
        if ($actionUrl !== '') {
            $body .= ($body === '' ? '' : "\n\n") . $actionUrl;
        }

        try {
            $mailer = $this->createMailer();
            $mailer->addRecipient($email, $name);
            $mailer->setSubject($subject);
            $mailer->setBody($body);
        } catch (Throwable $exception) {
            $message = trim($exception->getMessage());
            return DeliveryResult::failed(
                'mail_prepare_failed',
                $message !== '' ? $message : 'Joomla mailer could not prepare the notification.',
                300
            );
        }

        try {
            $sent = $mailer->send();
        } catch (Throwable $exception) {
            $message = trim($exception->getMessage());
            return DeliveryResult::outcomeUnknown(
                'mail_send_outcome_unknown',
                $message !== '' ? $message : 'Joomla mailer could not confirm whether the transport accepted the notification.'
            );
        }

        if ($sent !== true) {
            return DeliveryResult::outcomeUnknown(
                'mail_send_outcome_unknown',
                'Joomla mailer did not confirm transport acceptance.'
            );
        }

        // Joomla mail transport acceptance is not proof of delivery to the
        // recipient mailbox. Keep it explicitly at SUBMITTED.
        return DeliveryResult::submitted('joomla-mail');
    }

    /** @return array{0:string,1:string,2:string|null} */
    private function resolveRecipient(array $notification, array $context): array
    {
        $recipientType = (string) ($notification['recipient_type'] ?? '');
        $contextEmail  = trim((string) ($context['email'] ?? ''));

        if ($recipientType !== 'user') {
            if ($contextEmail !== '' && filter_var($contextEmail, FILTER_VALIDATE_EMAIL)) {
                return [
                    $contextEmail,
                    $this->cleanHeader((string) ($context['recipient_name'] ?? '')),
                    null,
                ];
            }

            return ['', '', null];
        }

        $id = (string) ($notification['recipient_id'] ?? '');
        if ($id === '' || !ctype_digit($id) || (int) $id < 1) {
            return ['', '', null];
        }

        try {
            $user = $this->userFactory->loadUserById((int) $id);
        } catch (Throwable $exception) {
            return ['', '', null];
        }

        if ((int) $user->id < 1 || (int) $user->block === 1 || !filter_var((string) $user->email, FILTER_VALIDATE_EMAIL)) {
            return ['', '', null];
        }

        $canonicalEmail = trim((string) $user->email);
        if ($contextEmail !== '') {
            if (!filter_var($contextEmail, FILTER_VALIDATE_EMAIL) || strcasecmp($contextEmail, $canonicalEmail) !== 0) {
                return ['', '', 'recipient_email_override_forbidden'];
            }
        }

        return [$canonicalEmail, $this->cleanHeader((string) $user->name), null];
    }

    private function createMailer()
    {
        if (interface_exists(MailerFactoryInterface::class)) {
            return Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
        }

        return Factory::getMailer();
    }

    private function cleanHeader(string $value): string
    {
        $value = trim(str_replace(["\r", "\n"], ' ', $value));
        return strlen($value) <= 255 ? $value : substr($value, 0, 255);
    }
}
