<?php declare(strict_types=1);

namespace NewApiBundle\Utils\Concurrency;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

trait ConcurrencyLockTrait
{
    /**
     * @var DateTimeInterface|null
     * @ORM\Column(name="locked_at", type="datetime_immutable", nullable=true)
     */
    protected $lockedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="locked_by", type="string", nullable=true, length=23)
     */
    protected $lockedBy;

    /**
     * @return DateTimeInterface|null
     */
    public function getLockedAt(): ?DateTimeInterface
    {
        return $this->lockedAt;
    }

    /**
     * @return string|null
     */
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
            throw new \RuntimeException('Item #' . $this->getId() . ' is already locked by someone else. Locked at: "' . $this->lockedAt . '" Locked by: "' . $this->lockedBy . '".');
        }

        $this->lockedAt = new \DateTimeImmutable();
        $this->lockedBy = $lockedBy;
    }

}
