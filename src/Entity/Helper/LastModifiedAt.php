<?php

declare(strict_types=1);

namespace Entity\Helper;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use RuntimeException;

trait LastModifiedAt
{
    /**
     * @var DateTimeInterface
     */
    #[ORM\Column(name: 'modified_at', type: 'datetime_immutable', nullable: false)]
    protected $lastModifiedAt;

    public function getLastModifiedAt(): DateTimeInterface
    {
        if (null === $this->lastModifiedAt) {
            throw new RuntimeException('This entity has not been persisted yet.');
        }

        return $this->lastModifiedAt;
    }

    #[ORM\PrePersist]
    public function setLastModifiedNow()
    {
        $this->lastModifiedAt = new DateTimeImmutable();
    }
}
