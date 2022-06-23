<?php declare(strict_types=1);

namespace NewApiBundle\Component\Assistance;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Repository\CountrySpecificRepository;
use BeneficiaryBundle\Repository\VulnerabilityCriterionRepository;
use CommonBundle\Repository\LocationRepository;
use DistributionBundle\Utils\ConfigurationLoader;
use Doctrine\ORM\EntityNotFoundException;
use NewApiBundle\Component\Assistance\Domain\SelectionCriteria;
use NewApiBundle\Component\Assistance\DTO\CriteriaGroup;
use NewApiBundle\Entity\Assistance\SelectionCriteria as SelectionCriteriaEntity;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\Enum\SelectionCriteriaField;
use NewApiBundle\Enum\SelectionCriteriaTarget;
use NewApiBundle\InputType\Assistance\SelectionCriterionInputType;

class SelectionCriteriaFactory
{
    /** @var ConfigurationLoader $configurationLoader */
    private $configurationLoader;
    /** @var CountrySpecificRepository */
    private $countrySpecificRepository;
    /** @var VulnerabilityCriterionRepository */
    private $vulnerabilityCriterionRepository;
    /** @var LocationRepository */
    private $locationRepository;

    /**
     * @param ConfigurationLoader              $configurationLoader
     * @param CountrySpecificRepository        $countrySpecificRepository
     * @param VulnerabilityCriterionRepository $vulnerabilityCriterionRepository
     * @param LocationRepository               $locationRepository
     */
    public function __construct(
        ConfigurationLoader              $configurationLoader,
        CountrySpecificRepository        $countrySpecificRepository,
        VulnerabilityCriterionRepository $vulnerabilityCriterionRepository,
        LocationRepository               $locationRepository
    ) {
        $this->configurationLoader = $configurationLoader;
        $this->countrySpecificRepository = $countrySpecificRepository;
        $this->vulnerabilityCriterionRepository = $vulnerabilityCriterionRepository;
        $this->locationRepository = $locationRepository;
    }

    public function create(SelectionCriterionInputType $input): SelectionCriteriaEntity
    {
        $criterium = new SelectionCriteriaEntity();
        $criterium->setConditionString($input->getCondition());
        $criterium->setFieldString($input->getField());
        $criterium->setTarget($input->getTarget());
        $criterium->setValueString($input->getValue());
        $criterium->setWeight($input->getWeight());
        $criterium->setGroupNumber($input->getGroup());
        $criterium->setTableString('Personnal');

        if (SelectionCriteriaTarget::BENEFICIARY === $input->getTarget()) {
            if ($this->getVulnerability($input->getField())) {
                $criterium->setTableString(SelectionCriteriaField::VULNERABILITY_CRITERIA);
                return $criterium;
            }
        }

        if (SelectionCriteriaTarget::HOUSEHOLD === $input->getTarget()) {

            if ($countrySpecific = $this->getCountrySpecific($input->getField())) {
                $criterium->setFieldString($countrySpecific->getFieldString());
                $criterium->setTableString('countrySpecific');
                return $criterium;
            }
            if ('location' === $input->getField()) {
                /** @var \CommonBundle\Entity\Location $location */
                $location = $this->locationRepository->find($input->getValue());
                if (!$location) {
                    throw new EntityNotFoundException();
                }

                $criterium->setFieldString(SelectionCriteriaField::CURRENT_LOCATION);
                $criterium->setValueString($location->getId());
                return $criterium;
            }
        }

        if (SelectionCriteriaField::GENDER === $input->getField()) {
            $genderEnum = PersonGender::valueFromAPI($input->getValue());
            $criterium->setValueString((PersonGender::MALE === $genderEnum) ? '1' : '0');
            $criterium->setFieldString(SelectionCriteriaField::GENDER);
            return $criterium;
        }

        return $criterium;
    }

    public function hydrate(SelectionCriteriaEntity $criteriaEntity): SelectionCriteria
    {
        switch ($criteriaEntity->getTableString()) {
            case SelectionCriteriaField::COUNTRY_SPECIFIC:
                $configuration = $this->configurationLoader->criteria[SelectionCriteriaField::COUNTRY_SPECIFIC];
                break;
            case SelectionCriteriaField::VULNERABILITY_CRITERIA:
                $configuration = $this->configurationLoader->criteria[SelectionCriteriaField::VULNERABILITY_CRITERIA];
                break;
            default:
                $configuration = $this->configurationLoader->criteria[$criteriaEntity->getFieldString()];
        }

        return new SelectionCriteria($criteriaEntity, $configuration);
    }

    public function createGroups(iterable $inputTypes): iterable
    {
        $groups = [];
        foreach ($inputTypes as $inputType) {
            $criteriumRoot = $this->create($inputType);
            $criteriumRoot->setGroupNumber($inputType->getGroup());
            $criterium = $this->hydrate($criteriumRoot);
            $groups[$inputType->getGroup()][] = $criterium;
        }
        foreach ($groups as $groupNumber => $group) {
            yield new CriteriaGroup($groupNumber, $group);
        }
    }


    private function getCountrySpecific(string $fieldName): ?CountrySpecific
    {
        static $list = null;
        if (null === $list) {
            $list = [];
            $countrySpecifics = $this->countrySpecificRepository->findBy([]);
            foreach ($countrySpecifics as $countrySpecific) {
                $list[$countrySpecific->getFieldString()] = $countrySpecific;
            }
        }

        return $list[$fieldName] ?? null;
    }

    private function getVulnerability(string $fieldName): ?VulnerabilityCriterion
    {
        static $list = null;
        if (null === $list) {
            $list = [];
            $vulnerabilityCriteria = $this->vulnerabilityCriterionRepository->findBy(['active' => true]);
            foreach ($vulnerabilityCriteria as $criterion) {
                $list[$criterion->getFieldString()] = $criterion;
            }
        }

        return $list[$fieldName] ?? null;
    }
}
