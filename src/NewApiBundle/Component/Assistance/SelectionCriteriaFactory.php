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
            if (($vulnerability = $this->getVulnerability($input->getField()))) {
                $criterium->setConditionString(null);
                $criterium->setTableString('vulnerabilityCriteria');
                return $criterium;
            }
        }

        if (SelectionCriteriaTarget::HOUSEHOLD_HEAD === $input->getTarget()) {
            if ('disabledHeadOfHousehold' === $input->getField()) {
                $criterium->setValueString(null);
                return $criterium;
            }

            if ('hasValidSmartcard' === $input->getField()) {
                $criterium->setConditionString(null);
                $criterium->setValueString(null);
                return $criterium;
            }
        }

        if (SelectionCriteriaTarget::HOUSEHOLD === $input->getTarget()) {

            if ($countrySpecific = $this->getCountrySpecific($input->getField())) {
                $criterium->setTableString($countrySpecific->getFieldString());
                $criterium->setFieldString('countrySpecific');
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

        if ('gender' === $input->getField()) {
            $genderEnum = PersonGender::valueFromAPI($input->getValue());
            $criterium->setFieldString('headOfHouseholdGender');
            $criterium->setValueString((PersonGender::MALE === $genderEnum) ? '1' : '0');
            return $criterium;
        }

        return $criterium;
    }

    /**
     * @param string $condition
     * @param string $field
     * @param string $target
     * @param $valueString
     * @param int    $weight
     *
     * @return SelectionCriteriaEntity
     */
    public function createPersonnal(
        string $condition,
        string $field,
        string $target,
        $valueString,
        int $weight
    ): SelectionCriteriaEntity
    {
        $criteria = new SelectionCriteriaEntity();
        $criteria->setConditionString($condition);
        $criteria->setFieldString($field);
        $criteria->setTarget($target);
        $criteria->setValueString($valueString);
        $criteria->setWeight($weight);
        $criteria->setTableString('Personnal');
        return $criteria;
    }

    public function createCountrySpecific(
        string $condition,
        string $field,
        string $target,
               $valueString,
        int $weight
    ): SelectionCriteriaEntity
    {
        $criteria = new SelectionCriteriaEntity();
        $criteria->setConditionString($condition);
        $criteria->setFieldString($field);
        $criteria->setTarget($target);
        $criteria->setValueString($valueString);
        $criteria->setWeight($weight);
        $criteria->setTableString('countrySpecific');
        return $criteria;
    }

    public function createVulnerability(
        string $field,
        string $target,
               $valueString,
        int $weight
    ): SelectionCriteriaEntity
    {
        $criteria = new SelectionCriteriaEntity();
        $criteria->setConditionString($valueString);
        $criteria->setFieldString($field);
        $criteria->setTarget($target);
        $criteria->setValueString(null);
        $criteria->setWeight($weight);
        $criteria->setTableString('vulnerabilityCriteria');
        return $criteria;
    }

    public function hydrate(SelectionCriteriaEntity $criteriaEntity): SelectionCriteria
    {
        return new SelectionCriteria(
            $criteriaEntity,
            $this->configurationLoader->criteria[$criteriaEntity->getFieldString()]
        );
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
