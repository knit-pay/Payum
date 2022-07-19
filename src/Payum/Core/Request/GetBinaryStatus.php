<?php

namespace Payum\Core\Request;

class GetBinaryStatus extends BaseGetStatus
{
    public const STATUS_PAYEDOUT = 4_194_304; //2^22

    public const STATUS_UNKNOWN = 2_097_152; //2^21

    public const STATUS_FAILED = 1_048_576; //2^20

    public const STATUS_SUSPENDED = 524288; // 2^19

    public const STATUS_EXPIRED = 262144; // 2^18

    public const STATUS_PENDING = 1024; // 2^10

    public const STATUS_CANCELED = 32; //2^5

    public const STATUS_REFUNDED = 16; // 2^4

    public const STATUS_AUTHORIZED = 8; // 2^3

    public const STATUS_CAPTURED = 4; // 2^2

    public const STATUS_NEW = 2; //2^1

    public function markCaptured(): void
    {
        $this->status = static::STATUS_CAPTURED;
    }

    public function isCaptured(): bool
    {
        return $this->isCurrentStatusEqualTo(static::STATUS_CAPTURED);
    }

    public function markAuthorized(): void
    {
        $this->status = static::STATUS_AUTHORIZED;
    }

    public function isAuthorized(): bool
    {
        return $this->isCurrentStatusEqualTo(static::STATUS_AUTHORIZED);
    }

    public function markPayedout(): void
    {
        $this->status = static::STATUS_PAYEDOUT;
    }

    public function isPayedout(): bool
    {
        return $this->isCurrentStatusEqualTo(static::STATUS_PAYEDOUT);
    }

    public function markRefunded(): void
    {
        $this->status = static::STATUS_REFUNDED;
    }

    public function isRefunded(): bool
    {
        return $this->isCurrentStatusEqualTo(static::STATUS_REFUNDED);
    }

    public function markSuspended(): void
    {
        $this->status = static::STATUS_SUSPENDED;
    }

    public function isSuspended(): bool
    {
        return $this->isCurrentStatusEqualTo(static::STATUS_SUSPENDED);
    }

    public function markExpired(): void
    {
        $this->status = static::STATUS_EXPIRED;
    }

    public function isExpired(): bool
    {
        return $this->isCurrentStatusEqualTo(static::STATUS_EXPIRED);
    }

    public function markCanceled(): void
    {
        $this->status = static::STATUS_CANCELED;
    }

    public function isCanceled(): bool
    {
        return $this->isCurrentStatusEqualTo(static::STATUS_CANCELED);
    }

    public function markPending(): void
    {
        $this->status = static::STATUS_PENDING;
    }

    public function isPending(): bool
    {
        return $this->isCurrentStatusEqualTo(static::STATUS_PENDING);
    }

    public function markFailed(): void
    {
        $this->status = static::STATUS_FAILED;
    }

    public function isFailed(): bool
    {
        return $this->isCurrentStatusEqualTo(static::STATUS_FAILED);
    }

    public function markNew(): void
    {
        $this->status = static::STATUS_NEW;
    }

    public function isNew(): bool
    {
        return $this->isCurrentStatusEqualTo(static::STATUS_NEW);
    }

    public function markUnknown(): void
    {
        $this->status = static::STATUS_UNKNOWN;
    }

    public function isUnknown(): bool
    {
        return $this->isCurrentStatusEqualTo(static::STATUS_UNKNOWN);
    }

    protected function isCurrentStatusEqualTo(int $expectedStatus): bool
    {
        return ($expectedStatus | $this->getValue()) === $expectedStatus;
    }
}
