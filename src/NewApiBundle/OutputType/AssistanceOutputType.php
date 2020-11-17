<?php
declare(strict_types=1);

namespace NewApiBundle\OutputType;

use DistributionBundle\Entity\Assistance;
use NewApiBundle\Serializer\OutputTypeInterface;

class AssistanceOutputType implements OutputTypeInterface
{
    /** @var Assistance */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object): bool
    {
        return $object instanceof Assistance;
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Assistance) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Assistance::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getDate(): int
    {
        return $this->object->getDateDistribution()->getTimestamp();
    }

    public function getTarget(): string
    {
        return $this->object->getTargetTypeString();
    }

    public function getType(): string
    {
        return $this->object->getAssistanceType();
    }

    public function getProvince(): string
    {
        return $this->object->getLocation()->getAdm1Name();
    }

    public function getDistrict(): string
    {
        return $this->object->getLocation()->getAdm2Name();
    }

    public function getCommune(): string
    {
        return $this->object->getLocation()->getAdm3Name();
    }

    public function getVillage(): string
    {
        return $this->object->getLocation()->getAdm4Name();
    }

    public function getCommodityIds(): array
    {
        return [0, 1]; //TODO implement
    }
}
