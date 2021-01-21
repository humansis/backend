<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\HouseholdLocation;
use NewApiBundle\Serializer\MapperInterface;

class CampAddressMapper implements MapperInterface
{
    /** @var HouseholdLocation */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return
            $object instanceof HouseholdLocation &&
            HouseholdLocation::LOCATION_TYPE_CAMP === $object->getType() &&
            isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof HouseholdLocation) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.HouseholdLocation::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getType(): string
    {
        return $this->object->getType();
    }

    public function getLocationGroup(): string
    {
        return $this->object->getLocationGroup();
    }

    public function getName(): string
    {
        return $this->object->getCampAddress()->getCamp()->getName();
    }

    public function getTentNumber(): string
    {
        return $this->object->getCampAddress()->getTentNumber();
    }

    public function getLocationId(): int
    {
        return $this->object->getCampAddress()->getCamp()->getLocation()->getId();
    }

    public function getAdm1Id(): ?int
    {
        return $this->object->getCampAddress()->getCamp()->getLocation()->getAdm1Id() ?: null;
    }

    public function getAdm2Id(): ?int
    {
        return $this->object->getCampAddress()->getCamp()->getLocation()->getAdm2Id() ?: null;
    }

    public function getAdm3Id(): ?int
    {
        return $this->object->getCampAddress()->getCamp()->getLocation()->getAdm3Id() ?: null;
    }

    public function getAdm4Id(): ?int
    {
        return $this->object->getCampAddress()->getCamp()->getLocation()->getAdm4Id() ?: null;
    }
}
