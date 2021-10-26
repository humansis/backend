<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Entity\DistributedItem;
use NewApiBundle\Serializer\MapperInterface;

class DistributedItemMapper implements MapperInterface
{
    /** @var DistributedItem */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof DistributedItem && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof DistributedItem) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.DistributedItem::class.', '.get_class($object).' given.');
    }

    public function getProjectId(): int
    {
        return $this->object->getProject()->getId();
    }

    public function getBeneficiaryId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }

    public function getAssistanceId(): int
    {
        return $this->object->getAssistance()->getId();
    }

    public function getDateDistribution(): ?string
    {
        return $this->object->getDateDistribution() ? $this->object->getDateDistribution()->format(\DateTimeInterface::ISO8601) : null;
    }

    public function getDateExpiration(): ?string
    {
        return $this->object->getDateDistribution() ? $this->object->getDateDistribution()->format(\DateTimeInterface::ISO8601) : null;
    }

    public function getCommodityId(): int
    {
        return $this->object->getCommodity()->getId();
    }

    public function getAmount(): float
    {
        return $this->object->getAmount();
    }

    public function getLocationId(): int
    {
        return $this->object->getLocation()->getId();
    }

    public function getAdm1Id(): ?int
    {
        return $this->object->getLocation()->getAdm1Id() ?: null;
    }

    public function getAdm2Id(): ?int
    {
        return $this->object->getLocation()->getAdm2Id() ?: null;
    }

    public function getAdm3Id(): ?int
    {
        return $this->object->getLocation()->getAdm3Id() ?: null;
    }

    public function getAdm4Id(): ?int
    {
        return $this->object->getLocation()->getAdm4Id() ?: null;
    }

    public function getCarrierNumber(): ?string
    {
        return $this->object->getCarrierNumber();
    }

    public function getType(): string
    {
        return $this->object->getBeneficiaryType();
    }

    public function getModalityType(): string
    {
        return $this->object->getModalityType();
    }

    public function getFieldOfficerId(): ?int
    {
        return $this->object->getFieldOfficer() ? $this->object->getFieldOfficer()->getId() : null;
    }
}
