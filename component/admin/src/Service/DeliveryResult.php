<?php
namespace Xdecaro\Component\Notifications\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;

final class DeliveryResult
{
    private const STATUSES = ['submitted', 'delivered', 'failed', 'outcome_unknown'];

    /** @var string */
    private $status;

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
        string $status,
        bool $success,
        ?string $providerReference,
        ?string $errorCode,
        ?string $errorMessage,
        ?int $retryAfterSeconds
    ) {
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid delivery result status.');
        }

        $this->status              = $status;
        $this->success             = $success;
        $this->providerReference   = $providerReference;
        $this->errorCode           = $errorCode;
        $this->errorMessage        = $errorMessage;
        $this->retryAfterSeconds   = $retryAfterSeconds;
    }

    /**
     * The provider/transport accepted the operation, but end-recipient delivery
     * has not been independently confirmed.
     */
    public static function submitted(?string $providerReference = null): self
    {
        return new self('submitted', true, self::normalizeReference($providerReference), null, null, null);
    }

    /**
     * The channel can attest to actual delivery. Keep this stronger outcome
     * separate from mere transport acceptance.
     */
    public static function delivered(?string $providerReference = null): self
    {
        return new self('delivered', true, self::normalizeReference($providerReference), null, null, null);
    }

    /**
     * A failure known to have happened before delivery. retryAfterSeconds means
     * the channel considers a retry safe.
     */
    public static function failed(
        string $errorCode,
        string $errorMessage,
        ?int $retryAfterSeconds = null
    ): self {
        [$errorCode, $errorMessage, $retryAfterSeconds] = self::validateError(
            $errorCode,
            $errorMessage,
            $retryAfterSeconds
        );

        return new self('failed', false, null, $errorCode, $errorMessage, $retryAfterSeconds);
    }

    /**
     * The provider might already have performed the operation, but Notifications
     * cannot prove the final outcome. A retry delay is only actionable when the
     * channel also declares an idempotency guarantee.
     */
    public static function outcomeUnknown(
        string $errorCode,
        string $errorMessage,
        ?string $providerReference = null,
        ?int $retryAfterSeconds = null
    ): self {
        [$errorCode, $errorMessage, $retryAfterSeconds] = self::validateError(
            $errorCode,
            $errorMessage,
            $retryAfterSeconds
        );

        return new self(
            'outcome_unknown',
            false,
            self::normalizeReference($providerReference),
            $errorCode,
            $errorMessage,
            $retryAfterSeconds
        );
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Backward-compatible adapter success flag. Both SUBMITTED and DELIVERED
     * mean the channel completed without a known failure, but callers needing
     * delivery semantics must use getStatus().
     */
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

    /** @return array{0:string,1:string,2:int|null} */
    private static function validateError(
        string $errorCode,
        string $errorMessage,
        ?int $retryAfterSeconds
    ): array {
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

        return [$errorCode, $errorMessage, $retryAfterSeconds];
    }

    private static function normalizeReference(?string $providerReference): ?string
    {
        if ($providerReference === null) {
            return null;
        }

        $providerReference = trim($providerReference);
        return $providerReference === '' ? null : $providerReference;
    }
}
