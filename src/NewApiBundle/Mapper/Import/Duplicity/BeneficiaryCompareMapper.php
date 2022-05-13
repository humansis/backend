<?php declare(strict_types=1);

namespace NewApiBundle\Mapper\Import\Duplicity;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Enum\ResidencyStatus;
use NewApiBundle\Component\Import\ValueObject\BeneficiaryCompare;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportHouseholdDuplicity;
use NewApiBundle\Enum\HouseholdAssets;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\Enum\VulnerabilityCriteria;
use NewApiBundle\InputType\Helper\EnumsBuilder;
use NewApiBundle\Serializer\MapperInterface;

class BeneficiaryCompareMapper implements MapperInterface
{
    use CompareTrait;

    /** @var BeneficiaryCompare */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof BeneficiaryCompare && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof BeneficiaryCompare) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.BeneficiaryCompare::class.', '.get_class($object).' given.');
    }


    public function getHouseholdId(): ?array
    {
        return $this->compareScalarValue(
            $this->object->getBeneficiary()->getHouseholdId(),
            $this->object->getBeneficiaryDuplicity()->getHouseholdDuplicity()->getTheirs()->getId()
        );
    }

    public function getLocalFullName(): ?array
    {
        $person = $this->object->getBeneficiary()->getPerson();
        $localDatabaseName = $person->getLocalGivenName();
        if (!empty($person->getLocalParentsName())) {
            $localDatabaseName .= ' '.$person->getLocalParentsName();
        }
        if (!empty($person->getLocalFamilyName())) {
            $localDatabaseName .= ' '.$person->getLocalFamilyName();
        }

        $localImportName = $this->object->getImportLine()->localGivenName;
        if (!empty($this->object->getImportLine()->localParentsName)) {
            $localImportName .= ' '.$this->object->getImportLine()->localParentsName;
        }
        if (!empty($this->object->getImportLine()->localFamilyName)) {
            $localImportName .= ' '.$this->object->getImportLine()->localFamilyName;
        }

        return $this->compareScalarValue($localDatabaseName, $localImportName);
    }

    public function getEnglishFullName(): ?array
    {
        $person = $this->object->getBeneficiary()->getPerson();
        $enDatabaseName = $person->getEnGivenName();
        if (!empty($person->getEnParentsName())) {
            $enDatabaseName .= ' '.$person->getEnParentsName();
        }
        if (!empty($person->getEnFamilyName())) {
            $enDatabaseName .= ' '.$person->getEnFamilyName();
        }

        $englishImportName = $this->object->getImportLine()->englishGivenName;
        if (!empty($this->object->getImportLine()->englishParentsName)) {
            $englishImportName .= ' '.$this->object->getImportLine()->englishParentsName;
        }
        if (!empty($this->object->getImportLine()->englishFamilyName)) {
            $englishImportName .= ' '.$this->object->getImportLine()->englishFamilyName;
        }

        return $this->compareScalarValue($enDatabaseName, $englishImportName);
    }

    public function getGender(): ?array
    {
        return $this->compareScalarValue(
            $this->object->getBeneficiary()->getPerson()->getGender(),
            PersonGender::valueFromAPI($this->object->getImportLine()->gender)
        );
    }

    public function getDateOfBirth(): ?array
    {
        return $this->compareScalarValue(
            $this->object->getBeneficiary()->getPerson()->getDateOfBirth()->format('Y-m-d'),
            $this->object->getImportLine()->getDateOfBirth()->format('Y-m-d')
        );
    }

    public function getPhone1(): ?array
    {
        $phone = $this->object->getBeneficiary()->getPerson()->getPhones()->get(0);
        $databasePhone = $phone ? trim($phone->getPrefix().$phone->getNumber()) : null;
        $importPhone = trim($this->object->getImportLine()->prefixPhone1.$this->object->getImportLine()->numberPhone1);
        return $this->compareScalarValue($databasePhone, !empty($importPhone) ? $importPhone : null);
    }

    public function getPhone2(): ?array
    {
        $phone = $this->object->getBeneficiary()->getPerson()->getPhones()->get(1);
        $databasePhone = $phone ? trim($phone->getPrefix().$phone->getNumber()) : null;
        $importPhone = trim($this->object->getImportLine()->prefixPhone2.$this->object->getImportLine()->numberPhone2);
        return $this->compareScalarValue($databasePhone, !empty($importPhone) ? $importPhone : null);
    }

    public function getVulnerability(): ?array
    {
        $databaseVulnerabilities = [];
        /** @var VulnerabilityCriterion $vulnerabilityCriterion */
        foreach ($this->object->getBeneficiary()->getVulnerabilityCriteria() as $vulnerabilityCriterion) {
            $databaseVulnerabilities[] = $vulnerabilityCriterion->getFieldString();
        }
        $enumBuilder = new EnumsBuilder(VulnerabilityCriteria::class);
        $enumBuilder->setNullToEmptyArrayTransformation();
        $importedVulnerabilities = $enumBuilder->buildInputValues($this->object->getImportLine()->vulnerabilityCriteria);
        return $this->compareLists($databaseVulnerabilities, $importedVulnerabilities);
    }

    public function getResidencyStatus(): ?array
    {
        return $this->compareScalarValue(
            $this->object->getBeneficiary()->getResidencyStatus(),
            ResidencyStatus::valueFromAPI($this->object->getImportLine()->residencyStatus)
        );
    }
}
