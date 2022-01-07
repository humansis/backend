<?php declare(strict_types=1);

namespace NewApiBundle\Entity\Helper;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use UserBundle\Entity\User;

trait ConcurrencyLock
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

}
