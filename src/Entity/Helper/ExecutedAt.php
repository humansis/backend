<?php

declare(strict_types=1);

namespace Entity\Helper;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use RuntimeException;

/**
 * Describes time when event really happened in real world. In contrast with CreatedAt which describes when the information was made and stored.
 */
trait ExecutedAt
{
    /**
     * @var DateTimeInterface
     * @ORM\Column(name="executed_at", type="datetime_immutable", nullable=false)
     */
    protected $executedAt;

    public function getExecutedAt(): DateTimeInterface
    {
        if (null === $this->executedAt) {
            throw new RuntimeException('This entity has not been persisted yet.');
        }

        return $this->executedAt;
    }

    public function setExecutedAt(DateTimeInterface $dateTime): void
    {
        $this->executedAt = $dateTime;
    }
}
