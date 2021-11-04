<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Entity\ReliefPackage;
use NewApiBundle\Serializer\MapperInterface;

class ReliefPackageVendorMapper implements MapperInterface
{
    /** @var ReliefPackage */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof ReliefPackage && isset($context[self::VENDOR_APP]) && true === $context[self::VENDOR_APP];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof ReliefPackage) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.ReliefPackage::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getAssistanceId(): int
    {
        return $this->object->getAssistanceBeneficiary()->getAssistance()->getId();
    }

    public function getBeneficiaryId(): int
    {
        return $this->object->getAssistanceBeneficiary()->getBeneficiary()->getId();
    }

    public function getAmountToDistribute(): string
    {
        return $this->object->getAmountToDistribute();
    }

    public function getUnit(): string
    {
        return $this->object->getUnit();
    }

    public function getSmartCardSerialNumber(): ?string
    {
        return $this->object->getAssistanceBeneficiary()->getBeneficiary()->getSmartcardSerialNumber();
    }

    public function getFoodLimit(): ?int
    {
        $foodLimit = $this->object->getAssistanceBeneficiary()->getAssistance()->getFoodLimit();

        return $foodLimit ? (int) $foodLimit : null;
    }

    public function getNonfoodLimit(): ?int
    {
        $nonFoodLimit = $this->object->getAssistanceBeneficiary()->getAssistance()->getNonFoodLimit();

        return $nonFoodLimit ? (int) $nonFoodLimit : null;
    }

    public function getCashbackLimit(): ?int
    {
        $cashbackLimit = $this->object->getAssistanceBeneficiary()->getAssistance()->getCashbackLimit();

        return $cashbackLimit ? (int) $cashbackLimit : null;
    }

    public function getExpirationDate(): ?string
    {
        $expirationDate = $this->object->getAssistanceBeneficiary()->getAssistance()->getDateExpiration();

        return $expirationDate ? $expirationDate->format(\DateTimeInterface::ISO8601) : null;
    }
}
