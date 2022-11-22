<?php

declare(strict_types=1);

namespace Utils\Concurrency;

use DateTimeInterface;

interface ConcurrencyLockableInterface
{
    public function getLockedAt(): ?DateTimeInterface;

    public function getLockedBy(): ?string;

    public function unlock(): void;

    public function lock(string $lockedBy): void;
}
