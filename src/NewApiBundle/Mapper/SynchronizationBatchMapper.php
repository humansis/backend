<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Utils\AssistanceService;
use NewApiBundle\Entity\SynchronizationBatch;
use NewApiBundle\Serializer\MapperInterface;

class SynchronizationBatchMapper implements MapperInterface
{
    /** @var SynchronizationBatch */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof SynchronizationBatch && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof SynchronizationBatch) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.SynchronizationBatch::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getSource(): ?string
    {
        return $this->object->getSource();
    }

    public function getValidationType(): string
    {
        return $this->object->getValidationType();
    }

    public function getCreatedAt(): string
    {
        return $this->object->getCreatedAt()->format(\DateTimeInterface::ISO8601);
    }

    public function getCreatedBy(): int
    {
        return $this->object->getCreatedBy()->getId();
    }

    public function getState(): string
    {
        return $this->object->getState();
    }

    public function getRawData(): string
    {
        return json_encode($this->object->getRequestData());
    }

    public function getViolations(): ?string
    {
        return json_encode($this->object->getViolations());
    }

    public function getValidatedAt(): ?string
    {
        return $this->object->getValidatedAt() ? $this->object->getValidatedAt()->format(\DateTimeInterface::ISO8601) : null;
    }
}
