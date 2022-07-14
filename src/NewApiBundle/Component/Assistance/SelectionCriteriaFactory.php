<?php declare(strict_types=1);

namespace NewApiBundle\Component\Assistance;

use NewApiBundle\Entity\CountrySpecific;
use NewApiBundle\Entity\VulnerabilityCriterion;
use NewApiBundle\Repository\CountrySpecificRepository;
use NewApiBundle\Repository\VulnerabilityCriterionRepository;
use CommonBundle\Repository\LocationRepository;
use Doctrine\ORM\EntityNotFoundException;
use NewApiBundle\Component\Assistance\Domain\SelectionCriteria;
use NewApiBundle\Component\Assistance\DTO\CriteriaGroup;
use NewApiBundle\Component\SelectionCriteria\Loader\CriteriaConfigurationLoader;
use NewApiBundle\Entity\Assistance\SelectionCriteria as SelectionCriteriaEntity;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\Enum\SelectionCriteriaField;
use NewApiBundle\Enum\SelectionCriteriaTarget;
use NewApiBundle\InputType\Assistance\SelectionCriterionInputType;

class SelectionCriteriaFactory
{
    /** @var CriteriaConfigurationLoader $configurationLoader */
    private $configurationLoader;
    /** @var CountrySpecificRepository */
    private $countrySpecificRepository;
    /** @var VulnerabilityCriterionRepository */
    private $vulnerabilityCriterionRepository;
    /** @var LocationRepository */
    private $locationRepository;

    /**
     * @param CriteriaConfigurationLoader      $configurationLoader
     * @param CountrySpecificRepository        $countrySpecificRepository
     * @param VulnerabilityCriterionRepository $vulnerabilityCriterionRepository
     * @param LocationRepository               $locationRepository
     */
    public function __construct(
        CriteriaConfigurationLoader      $configurationLoader,
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
            if (SelectionCriteriaField::CURRENT_LOCATION === $input->getField()) {
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
                $configuration = $this->configurationLoader->getCriterionConfiguration(SelectionCriteriaField::COUNTRY_SPECIFIC);
                if (is_string($criteriaEntity->getValueString())) { //when creating new assistance, $criteriaEntity->getValueString() does not always return string
                    $returnType = $this->configurationLoader->guessReturnType($criteriaEntity->getValueString());
                    $configuration->setReturnType($returnType);
                }
                break;
            case SelectionCriteriaField::VULNERABILITY_CRITERIA:
                $configuration = $this->configurationLoader->getCriterionConfiguration(SelectionCriteriaField::VULNERABILITY_CRITERIA);
                break;
            default:
                $configuration = $this->configurationLoader->getCriterionConfiguration($criteriaEntity->getFieldString());
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
