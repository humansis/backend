<?php declare(strict_types=1);

namespace NewApiBundle\Utils\Concurrency;

interface ConcurrencyLockableInterface
{
    public function getLockedAt(): ?\DateTimeInterface;
    public function getLockedBy(): ?string;
    public function unlock(): void;
}
