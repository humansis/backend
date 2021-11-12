<?php
declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Repository\CountrySpecificRepository;
use BeneficiaryBundle\Repository\VulnerabilityCriterionRepository;
use CommonBundle\Repository\LocationRepository;
use DistributionBundle\Entity\SelectionCriteria;
use Doctrine\ORM\EntityNotFoundException;
use NewApiBundle\Enum\SelectionCriteriaTarget;
use NewApiBundle\InputType\Assistance\SelectionCriterionInputType;

/**
 * Temporary helping service to map new selection criteria structure to old one.
 *
 * After BeneficiaryRepository will be refactorized, this service can be removed.
 */
class FieldDbTransformer
{
    /** @var CountrySpecificRepository */
    private $countrySpecificRepository;

    /** @var VulnerabilityCriterionRepository */
    private $vulnerabilityCriterionRepository;

    /** @var LocationRepository */
    private $locationRepository;

    public function __construct(
        CountrySpecificRepository $countrySpecificRepository,
        VulnerabilityCriterionRepository $vulnerabilityCriterionRepository,
        LocationRepository $locationRepository
    )
    {
        $this->countrySpecificRepository = $countrySpecificRepository;
        $this->vulnerabilityCriterionRepository = $vulnerabilityCriterionRepository;
        $this->locationRepository = $locationRepository;
    }

    public function toDbArray(SelectionCriterionInputType $input): array
    {
        if (SelectionCriteriaTarget::BENEFICIARY === $input->getTarget() && ($vulnerability = $this->getVulnerability($input->getField()))) {
            return [
                'condition_string' => $input->getValue(),
                'field_string' => $input->getField(),
                'target' => $input->getTarget(),
                'table_string' => 'vulnerabilityCriteria',
                'value_string' => null,
                'weight' => $input->getWeight(),
            ];
        }

        if (SelectionCriteriaTarget::HOUSEHOLD_HEAD === $input->getTarget() && 'disabledHeadOfHousehold' === $input->getField()) {
            return [
                'condition_string' => true,
                'field_string' => $input->getField(),
                'target' => $input->getTarget(),
                'table_string' => 'Personnal',
                'value_string' => null,
                'weight' => $input->getWeight(),
                'type' => 'other',
            ];
        }

        if (SelectionCriteriaTarget::HOUSEHOLD_HEAD === $input->getTarget() && 'hasValidSmartcard' === $input->getField()) {
            return [
                'condition_string' => true,
                'field_string' => $input->getField(),
                'target' => $input->getTarget(),
                'table_string' => 'Personnal',
                'value_string' => null,
                'value' => $input->getValue(),
                'weight' => $input->getWeight(),
                'type' => 'other',
            ];
        }

        if ((SelectionCriteriaTarget::BENEFICIARY === $input->getTarget() && 'hasNotBeenInDistributionsSince' === $input->getField()) ||
            (SelectionCriteriaTarget::HOUSEHOLD === $input->getTarget() && 'householdSize' === $input->getField())
        ) {
            return [
                'condition_string' => $input->getCondition(),
                'field_string' => $input->getField(),
                'target' => $input->getTarget(),
                'table_string' => 'Personnal',
                'value_string' => $input->getValue(),
                'weight' => $input->getWeight(),
                'type' => 'other',
            ];
        }

        if (SelectionCriteriaTarget::HOUSEHOLD === $input->getTarget() && ($countrySpecific = $this->getCountrySpecific($input->getField()))) {
            return [
                'condition_string' => $input->getCondition(),
                'field_string' => $input->getField(),
                'target' => $input->getTarget(),
                'table_string' => 'countrySpecific',
                'value_string' => $input->getValue(),
                'weight' => $input->getWeight(),
                'type' => $countrySpecific->getType(),
            ];
        }

        if (SelectionCriteriaTarget::HOUSEHOLD === $input->getTarget() && 'location' === $input->getField()) {
            /** @var \CommonBundle\Entity\Location $location */
            $location = $this->locationRepository->find($input->getValue());
            if (!$location) {
                throw new EntityNotFoundException();
            }
            $fieldString = '';
            if ($location->getAdm() instanceof \CommonBundle\Entity\Adm1) {
                $fieldString = 'currentAdm1';
            } elseif ($location->getAdm() instanceof \CommonBundle\Entity\Adm2) {
                $fieldString = 'currentAdm2';
            } elseif ($location->getAdm() instanceof \CommonBundle\Entity\Adm3) {
                $fieldString = 'currentAdm3';
            } elseif ($location->getAdm() instanceof \CommonBundle\Entity\Adm4) {
                $fieldString = 'currentAdm4';
            }

            return [
                'condition_string' => $input->getCondition(),
                'field_string' => $fieldString,
                'target' => $input->getTarget(),
                'table_string' => 'Personnal',
                'value_string' => $input->getValue(),
                'weight' => $input->getWeight(),
                'type' => 'other',
            ];
        }

        if (SelectionCriteriaTarget::HOUSEHOLD === $input->getTarget() && 'campName' === $input->getField()) {
            return [
                'condition_string' => $input->getCondition(),
                'field_string' => $input->getField(),
                'target' => $input->getTarget(),
                'table_string' => 'Personnal',
                'value_string' => $input->getValue(),
                'weight' => $input->getWeight(),
                'type' => 'other',
            ];
        }

        $value = $input->getValue();
        $field = $input->getField();
        if ('gender' === $input->getField()) {
            $value = ('M' === $input->getValue()) ? '1' : '0';
            if (SelectionCriteriaTarget::HOUSEHOLD_HEAD === $input->getTarget()) {
                $field = 'headOfHouseholdGender';
            }
        }

        return [
            'condition_string' => $input->getCondition(),
            'field_string' => $field,
            'target' => $input->getTarget(),
            'table_string' => 'Personnal',
            'value_string' => $value,
            'weight' => $input->getWeight(),
            'type' => 'table_field',
        ];
    }

    public function toResponseArray(SelectionCriteria $criterion)
    {
        if (SelectionCriteriaTarget::BENEFICIARY === $criterion->getTarget() && 'vulnerabilityCriteria' === $criterion->getTableString()) {
            return [
                'group' => $criterion->getGroupNumber(),
                'target' => $criterion->getTarget(),
                'field' => $criterion->getFieldString(),
                'condition' => '=',
                'value' => $criterion->getConditionString(),
                'weight' => $criterion->getWeight(),
            ];
        }

        if (SelectionCriteriaTarget::HOUSEHOLD_HEAD === $criterion->getTarget() && 'disabledHeadOfHousehold' === $criterion->getFieldString()) {
            return [
                'group' => $criterion->getGroupNumber(),
                'target' => $criterion->getTarget(),
                'field' => $criterion->getFieldString(),
                'condition' => '=',
                'value' => true,
                'weight' => $criterion->getWeight(),
            ];
        }

        if (SelectionCriteriaTarget::HOUSEHOLD_HEAD === $criterion->getTarget() && 'hasValidSmartcard' === $criterion->getFieldString()) {
            return [
                'group' => $criterion->getGroupNumber(),
                'target' => $criterion->getTarget(),
                'field' => $criterion->getFieldString(),
                'condition' => '=',
                'value' => $criterion->getValueString(),
                'weight' => $criterion->getWeight(),
            ];
        }

        if ((SelectionCriteriaTarget::BENEFICIARY === $criterion->getTarget() && 'hasNotBeenInDistributionsSince' === $criterion->getFieldString()) ||
            (SelectionCriteriaTarget::HOUSEHOLD === $criterion->getTarget() && 'householdSize' === $criterion->getFieldString())
        ) {
            return [
                'group' => $criterion->getGroupNumber(),
                'target' => $criterion->getTarget(),
                'field' => $criterion->getFieldString(),
                'condition' => $criterion->getConditionString(),
                'value' => $criterion->getValueString(),
                'weight' => $criterion->getWeight(),
            ];
        }

        if (SelectionCriteriaTarget::HOUSEHOLD === $criterion->getTarget() && 'countrySpecific' === $criterion->getTableString()) {
            return [
                'group' => $criterion->getGroupNumber(),
                'target' => $criterion->getTarget(),
                'field' => $criterion->getFieldString(),
                'condition' => $criterion->getConditionString(),
                'value' => $criterion->getValueString(),
                'weight' => $criterion->getWeight(),
            ];
        }

        if (SelectionCriteriaTarget::HOUSEHOLD === $criterion->getTarget() && in_array($criterion->getTarget(), ['currentAdm1', 'currentAdm2', 'currentAdm3', 'currentAdm4'])) {
            return [
                'group' => $criterion->getGroupNumber(),
                'target' => $criterion->getTarget(),
                'field' => 'location',
                'condition' => $criterion->getConditionString(),
                'value' => $criterion->getValueString(),
                'weight' => $criterion->getWeight(),
            ];
        }

        if (SelectionCriteriaTarget::HOUSEHOLD === $criterion->getTarget() && 'campName' === $criterion->getFieldString()) {
            return [
                'group' => $criterion->getGroupNumber(),
                'target' => $criterion->getTarget(),
                'field' => $criterion->getFieldString(),
                'condition' => $criterion->getConditionString(),
                'value' => $criterion->getValueString(),
                'weight' => $criterion->getWeight(),
            ];
        }

        $value = $criterion->getValueString();
        $field = $criterion->getFieldString();
        if ('gender' === $criterion->getFieldString() || 'headOfHouseholdGender' === $criterion->getFieldString()) {
            $value = (1 == $criterion->getValueString()) ? 'M' : 'F';
            $field = 'gender';
        }

        return [
            'group' => $criterion->getGroupNumber(),
            'target' => $criterion->getTarget(),
            'field' => $field,
            'condition' => $criterion->getConditionString(),
            'value' => $value,
            'weight' => $criterion->getWeight(),
        ];
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
