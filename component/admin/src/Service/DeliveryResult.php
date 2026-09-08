<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;

final class DeliveryResult
{
    /** @var bool */
    private $success;

    /** @var string|null */
    private $providerReference;

    /** @var string|null */
    private $errorCode;

    /** @var string|null */
    private $errorMessage;

    /** @var int|null */
    private $retryAfterSeconds;

    private function __construct(
        bool $success,
        ?string $providerReference,
        ?string $errorCode,
        ?string $errorMessage,
        ?int $retryAfterSeconds
    ) {
        $this->success             = $success;
        $this->providerReference   = $providerReference;
        $this->errorCode           = $errorCode;
        $this->errorMessage        = $errorMessage;
        $this->retryAfterSeconds   = $retryAfterSeconds;
    }

    public static function delivered(?string $providerReference = null): self
    {
        return new self(true, $providerReference, null, null, null);
    }

    public static function failed(
        string $errorCode,
        string $errorMessage,
        ?int $retryAfterSeconds = null
    ): self {
        $errorCode    = trim($errorCode);
        $errorMessage = trim($errorMessage);

        if ($errorCode === '' || strlen($errorCode) > 64 || !preg_match('/^[A-Za-z0-9._-]+$/', $errorCode)) {
            throw new InvalidArgumentException('Invalid delivery error code.');
        }

        if ($errorMessage === '') {
            throw new InvalidArgumentException('Delivery error message is required.');
        }

        if ($retryAfterSeconds !== null && $retryAfterSeconds < 1) {
            throw new InvalidArgumentException('retryAfterSeconds must be positive when supplied.');
        }

        return new self(false, null, $errorCode, $errorMessage, $retryAfterSeconds);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getProviderReference(): ?string
    {
        return $this->providerReference;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getRetryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}
