<?php

declare(strict_types=1);

namespace Component\Assistance;

use Component\SelectionCriteria\Generator\HouseholdFieldGenerator;
use Entity\CountrySpecific;
use Entity\HouseholdLocation;
use Entity\Location;
use Entity\VulnerabilityCriterion;
use Repository\CountrySpecificRepository;
use Repository\VulnerabilityCriterionRepository;
use Repository\LocationRepository;
use Doctrine\ORM\EntityNotFoundException;
use Component\Assistance\Domain\SelectionCriteria;
use Component\Assistance\DTO\CriteriaGroup;
use Component\SelectionCriteria\Loader\CriteriaConfigurationLoader;
use Entity\Assistance\SelectionCriteria as SelectionCriteriaEntity;
use Enum\PersonGender;
use Enum\SelectionCriteriaField;
use Enum\SelectionCriteriaTarget;
use InputType\Assistance\SelectionCriterionInputType;

class SelectionCriteriaFactory
{
    public function __construct(private readonly CriteriaConfigurationLoader $configurationLoader, private readonly CountrySpecificRepository $countrySpecificRepository, private readonly VulnerabilityCriterionRepository $vulnerabilityCriterionRepository, private readonly LocationRepository $locationRepository)
    {
    }

    public function create(SelectionCriterionInputType $input): SelectionCriteriaEntity
    {
        $criterium = new SelectionCriteriaEntity();
        $criterium->setConditionString($input->getCondition());
        $criterium->setFieldString($input->getField());
        $criterium->setTarget($input->getTarget());
        $criterium->setValueString((string) $input->getValue());
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
                /** @var Location $location */
                $location = $this->locationRepository->find($input->getValue());
                if (!$location) {
                    throw new EntityNotFoundException();
                }

                $criterium->setFieldString(SelectionCriteriaField::CURRENT_LOCATION);
                $criterium->setValueString((string) $location->getId());

                return $criterium;
            }
        }

        if (SelectionCriteriaField::GENDER === $input->getField()) {
            $genderEnum = PersonGender::valueFromAPI($input->getValue());
            $criterium->setValueString((PersonGender::MALE === $genderEnum) ? '1' : '0');
            $criterium->setFieldString(SelectionCriteriaField::GENDER);

            return $criterium;
        }

        if (
            SelectionCriteriaField::LOCATION_TYPE === $input->getField()
            && HouseholdFieldGenerator::isValueIndexOfHouseholdLocationTypeArray($input->getValue())
        ) {
            $criterium->setValueString(HouseholdLocation::LOCATION_TYPES[(int) $input->getValue()]);
        }

        return $criterium;
    }

    public function hydrate(SelectionCriteriaEntity $criteriaEntity): SelectionCriteria
    {
        switch ($criteriaEntity->getTableString()) {
            case SelectionCriteriaField::COUNTRY_SPECIFIC:
                $configuration = $this->configurationLoader->getCriterionConfiguration(
                    SelectionCriteriaField::COUNTRY_SPECIFIC
                );
                if (
                    is_string(
                        $criteriaEntity->getValueString()
                    )
                ) { //when creating new assistance, $criteriaEntity->getValueString() does not always return string
                    $returnType = $this->configurationLoader->guessReturnType($criteriaEntity->getValueString());
                    $configuration->setReturnType($returnType);
                }
                break;
            case SelectionCriteriaField::VULNERABILITY_CRITERIA:
                $configuration = $this->configurationLoader->getCriterionConfiguration(
                    SelectionCriteriaField::VULNERABILITY_CRITERIA
                );
                break;
            default:
                $configuration = $this->configurationLoader->getCriterionConfiguration(
                    $criteriaEntity->getFieldString()
                );
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
