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
        [$email, $name] = $this->resolveRecipient($notification, $context);

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
            $mailer->send();

            return DeliveryResult::delivered('joomla-mail');
        } catch (Throwable $exception) {
            $message = trim($exception->getMessage());
            return DeliveryResult::failed('mail_send_failed', $message !== '' ? $message : 'Joomla mailer failed to send the notification.', 300);
        }
    }

    /** @return array{0:string,1:string} */
    private function resolveRecipient(array $notification, array $context): array
    {
        $email = trim((string) ($context['email'] ?? ''));
        $name = $this->cleanHeader((string) ($context['recipient_name'] ?? ''));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [$email, $name];
        }

        if ((string) ($notification['recipient_type'] ?? '') !== 'user') {
            return ['', ''];
        }

        $id = (string) ($notification['recipient_id'] ?? '');
        if ($id === '' || !ctype_digit($id) || (int) $id < 1) {
            return ['', ''];
        }

        try {
            $user = $this->userFactory->loadUserById((int) $id);
        } catch (Throwable $exception) {
            return ['', ''];
        }

        if ((int) $user->id < 1 || (int) $user->block === 1 || !filter_var((string) $user->email, FILTER_VALIDATE_EMAIL)) {
            return ['', ''];
        }

        return [(string) $user->email, $this->cleanHeader((string) $user->name)];
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
