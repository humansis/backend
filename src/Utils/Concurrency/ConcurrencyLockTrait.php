<?php

declare(strict_types=1);

namespace Utils\Concurrency;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use RuntimeException;

trait ConcurrencyLockTrait
{
    /**
     * @var DateTimeInterface|null
     */
    #[ORM\Column(name: 'locked_at', type: 'datetime_immutable', nullable: true)]
    protected $lockedAt;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'locked_by', type: 'string', length: 23, nullable: true)]
    protected $lockedBy;

    public function getLockedAt(): ?DateTimeInterface
    {
        return $this->lockedAt;
    }

    public function getLockedBy(): ?string
    {
        return $this->lockedBy;
    }

    public function unlock(): void
    {
        $this->lockedAt = null;
        $this->lockedBy = null;
    }

    public function lock(string $lockedBy): void
    {
        if (null !== $this->lockedAt || null !== $this->lockedBy) {
            throw new RuntimeException(
                'Item #' . $this->getId(
                ) . ' is already locked by someone else. Locked at: "' . $this->lockedAt->format(
                    'Y-m-d H:i:s O e'
                ) . '" Locked by: "' . $this->lockedBy . '".'
            );
        }

        $this->lockedAt = new DateTimeImmutable();
        $this->lockedBy = $lockedBy;
    }
}
