<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\CountrySpecificAnswer;
use NewApiBundle\Entity\Household;
use NewApiBundle\Entity\HouseholdLocation;
use NewApiBundle\Serializer\MapperInterface;

class HouseholdMapper implements MapperInterface
{
    /** @var Household */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Household && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Household) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Household::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getLivelihood(): ?string
    {
        return $this->object->getLivelihood();
    }

    /**
     * @return string[]
     */
    public function getAssets(): iterable
    {
        return array_values(array_map(function ($item) {
            return (string) $item;
        }, $this->object->getAssets()));
    }

    public function getShelterStatus(): ?string
    {
        return $this->object->getShelterStatus() ? (string) $this->object->getShelterStatus() : null;
    }

    /**
     * @return int[]
     */
    public function getProjectIds(): iterable
    {
        return array_values(array_map(function ($item) {
            return $item->getId();
        }, $this->object->getProjects()->toArray()));
    }

    public function getNotes(): ?string
    {
        return $this->object->getNotes();
    }

    public function getLongitude(): ?string
    {
        return $this->object->getLongitude();
    }

    public function getLatitude(): ?string
    {
        return $this->object->getLatitude();
    }

    public function getHouseholdHeadId(): int
    {
        foreach ($this->object->getBeneficiaries() as $beneficiary) {
            /** @var Beneficiary $beneficiary */
            if ($beneficiary->isHead()) {
                return $beneficiary->getId();
            }
        }

        throw new \LogicException('Household #'.$this->object->getId().' does not have HH head.');
    }

    public function getCountrySpecificAnswerIds(): iterable
    {
        return array_values(array_map(function ($item) {
            return $item->getId();
        }, $this->object->getCountrySpecificAnswers()->toArray()));
    }

    /**
     * @return int[]
     */
    public function getBeneficiaryIds(): iterable
    {
        return array_values(array_map(function ($item) {
            return $item->getId();
        }, $this->object->getBeneficiaries()->toArray()));
    }

    public function getIncomeLevel(): ?int
    {
        return $this->object->getIncome();
    }

    public function getFoodConsumptionScore(): ?int
    {
        return $this->object->getFoodConsumptionScore();
    }

    public function getCopingStrategiesIndex(): ?int
    {
        return $this->object->getCopingStrategiesIndex();
    }

    public function getDebtLevel(): ?int
    {
        return $this->object->getDebtLevel();
    }

    public function getSupportDateReceived(): ?string
    {
        return $this->object->getSupportDateReceived() ? $this->object->getSupportDateReceived()->format(\DateTime::ISO8601) : null;
    }

    /**
     * @return string[]
     */
    public function getSupportReceivedTypes(): iterable
    {
        return array_values(array_map(function ($item) {
            return (string) $item;
        }, $this->object->getSupportReceivedTypes()));
    }

    public function getSupportOrganizationName(): ?string
    {
        return $this->object->getSupportOrganizationName();
    }

    public function getIncomeSpentOnFood(): ?int
    {
        return $this->object->getIncomeSpentOnFood();
    }

    public function getHouseholdIncome(): ?int
    {
        return $this->object->getHouseholdIncome();
    }

    public function getCampAddressId(): ?int
    {
        foreach ($this->object->getHouseholdLocations() as $householdLocation) {
            /** @var HouseholdLocation $householdLocation */
            if (HouseholdLocation::LOCATION_TYPE_CAMP === $householdLocation->getType()) {
                return $householdLocation->getId();
            }
        }

        return null;
    }

    public function getResidenceAddressId(): ?int
    {
        foreach ($this->object->getHouseholdLocations() as $householdLocation) {
            /** @var HouseholdLocation $householdLocation */
            if (HouseholdLocation::LOCATION_TYPE_RESIDENCE === $householdLocation->getType()) {
                return $householdLocation->getId();
            }
        }

        return null;
    }

    public function getTemporarySettlementAddressId(): ?int
    {
        foreach ($this->object->getHouseholdLocations() as $householdLocation) {
            /** @var HouseholdLocation $householdLocation */
            if (HouseholdLocation::LOCATION_TYPE_SETTLEMENT === $householdLocation->getType()) {
                return $householdLocation->getId();
            }
        }

        return null;
    }

    public function getProxyEnGivenName(): ?string
    {
        if (null === $this->object->getProxy()) {
            return null;
        }

        return $this->object->getProxy()->getEnGivenName();
    }

    public function getProxyEnFamilyName(): ?string
    {
        if (null === $this->object->getProxy()) {
            return null;
        }

        return $this->object->getProxy()->getEnFamilyName();
    }

    public function getProxyEnParentsName(): ?string
    {
        if (null === $this->object->getProxy()) {
            return null;
        }

        return $this->object->getProxy()->getEnParentsName();
    }

    public function getProxyLocalGivenName(): ?string
    {
        if (null === $this->object->getProxy()) {
            return null;
        }

        return $this->object->getProxy()->getLocalGivenName();
    }

    public function getProxyLocalFamilyName(): ?string
    {
        if (null === $this->object->getProxy()) {
            return null;
        }

        return $this->object->getProxy()->getLocalFamilyName();
    }

    public function getProxyLocalParentsName(): ?string
    {
        if (null === $this->object->getProxy()) {
            return null;
        }

        return $this->object->getProxy()->getLocalParentsName();
    }

    public function getProxyNationalIdCardId(): ?int
    {
        if (null === $this->object->getProxy()) {
            return null;
        }

        if ($this->object->getProxy()->getNationalIds()->count() === 0) {
            return null;
        }

        return $this->object->getProxy()->getNationalIds()->current()->getId();
    }

    public function getProxyPhoneId(): ?int
    {
        if (null === $this->object->getProxy()) {
            return null;
        }

        if ($this->object->getProxy()->getPhones()->count() === 0) {
            return null;
        }

        return $this->object->getProxy()->getPhones()->current()->getId();
    }
}
