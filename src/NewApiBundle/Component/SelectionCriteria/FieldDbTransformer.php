<?php
declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Repository\CountrySpecificRepository;
use BeneficiaryBundle\Repository\VulnerabilityCriterionRepository;
use CommonBundle\Repository\LocationRepository;
use Doctrine\ORM\EntityNotFoundException;
use NewApiBundle\Component\Assistance\SelectionCriteriaFactory;
use NewApiBundle\Entity\Assistance\SelectionCriteria;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\Enum\SelectionCriteriaField;
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

    public function __construct(
        CountrySpecificRepository        $countrySpecificRepository,
        VulnerabilityCriterionRepository $vulnerabilityCriterionRepository
    ) {
        $this->countrySpecificRepository = $countrySpecificRepository;
        $this->vulnerabilityCriterionRepository = $vulnerabilityCriterionRepository;
    }

    /**
     * @param \NewApiBundle\Entity\Assistance\SelectionCriteria $criterion
     *
     * @return array
     * @deprecated rewrite into SelectionCriteriaMapper (maybe multiple mappers)
     */
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

        if (SelectionCriteriaTarget::HOUSEHOLD === $criterion->getTarget() && 'currentLocation' === $criterion->getFieldString()) {
            return [
                'group' => $criterion->getGroupNumber(),
                'target' => $criterion->getTarget(),
                'field' => 'location',
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
}
