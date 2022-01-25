<?php
declare(strict_types=1);

namespace NewApiBundle\Entity\Helper;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use RuntimeException;

trait CreatedAt
{
    /**
     * @var DateTimeInterface
     * @ORM\Column(name="created_at", type="datetime_immutable", nullable=false)
     */
    protected $createdAt;

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        if (null === $this->createdAt) {
            throw new RuntimeException('This entity has not been persisted yet.');
        }

        return $this->createdAt;
    }

    /**
     * @throws Exception
     * @ORM\PrePersist
     */
    public function setCreatedAt()
    {
        $this->createdAt = new DateTimeImmutable();
    }
}
