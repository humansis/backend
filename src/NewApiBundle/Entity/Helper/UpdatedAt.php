<?php
declare(strict_types=1);

namespace NewApiBundle\Entity\Helper;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * Describes time when the information was made and stored In contrast with ExecutedAt which describes when event really happened in real world.
 * @ORM\HasLifecycleCallbacks()
 */
trait UpdatedAt
{
    /**
     * @var DateTimeInterface|null
     * @ORM\Column(name="updated_at", type="datetime_immutable", nullable=true)
     */
    protected $updatedAt;

    /**
     * @return DateTimeInterface|null
     */
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @throws Exception
     * @ORM\PreUpdate
     */
    public function setUpdatedAt()
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
