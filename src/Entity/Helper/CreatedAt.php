<?php

declare(strict_types=1);

namespace Entity\Helper;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use RuntimeException;

/**
 * Describes time when the information was made and stored In contrast with ExecutedAt which describes when event really happened in real world.
 */
trait CreatedAt
{
    /**
     * @var DateTimeInterface
     */
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: false)]
    protected $createdAt;

    public function getCreatedAt(): DateTimeInterface
    {
        if (null === $this->createdAt) {
            throw new RuntimeException('This entity has not been persisted yet.');
        }

        return $this->createdAt;
    }

    /**
     * @throws Exception
     */
    #[ORM\PrePersist]
    public function setCreatedAt()
    {
        $this->createdAt = new DateTimeImmutable();
    }
}
