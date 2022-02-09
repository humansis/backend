<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Enum\ResidencyStatus;
use NewApiBundle\Component\Import\ValueObject\ImportBeneficiaryDuplicityCompare;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportHouseholdDuplicity;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\Serializer\MapperInterface;

class ImportBeneficiaryDuplicityCompareMapper implements MapperInterface
{
    /** @var ImportBeneficiaryDuplicityCompare */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof ImportBeneficiaryDuplicityCompare && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof ImportBeneficiaryDuplicityCompare) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.ImportBeneficiaryDuplicityCompare::class.', '.get_class($object).' given.');
    }

    private function compareScalarValue($databaseValue, $importValue): ?array
    {
        if ($databaseValue === $importValue) return null;
        return [
            'database' => $databaseValue,
            'import' => $importValue,
        ];
    }

    private function compareLists(array $databaseValues, array $importValues): ?array
    {
        $data = [
            'same' => array_intersect($databaseValues, $importValues),
            'database' => array_diff($databaseValues, $importValues),
            'import' => array_diff($importValues, $databaseValues),
        ];
        if (empty($data['database']) && empty($data['import'])) return null;
        return $data;
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

    public function getPhones(): ?array
    {
        $databasePhones = [];
        foreach ($this->object->getBeneficiary()->getPerson()->getPhones() as $phone) {
            $databasePhones[] = trim($phone->getPrefix().$phone->getNumber());
        }
        $importPhones = [];
        $importPhones[] = trim($this->object->getImportLine()->prefixPhone1.$this->object->getImportLine()->numberPhone1);
        $importPhones[] = trim($this->object->getImportLine()->prefixPhone2.$this->object->getImportLine()->numberPhone2);
        $importPhones = array_filter($importPhones, function ($number) {
            return !empty($number);
        });
        return $this->compareLists($databasePhones, $importPhones);
    }

    public function getVulnerability(): ?array
    {
        $databaseVulnerabilities = [];
        /** @var VulnerabilityCriterion $vulnerabilityCriterion */
        foreach ($this->object->getBeneficiary()->getVulnerabilityCriteria() as $vulnerabilityCriterion) {
            $databaseVulnerabilities[] = $vulnerabilityCriterion->getFieldString();
        }
        $importVulnerabilities = [];
        // FIXME
        //$this->object->getImportLine()->vulnerabilityCriteria;

        return $this->compareLists($databaseVulnerabilities, $importVulnerabilities);
    }

    public function getResidencyStatus(): ?array
    {
        return $this->compareScalarValue(
            $this->object->getBeneficiary()->getResidencyStatus(),
            ResidencyStatus::valueFromAPI($this->object->getImportLine()->residencyStatus)
        );
    }
}
