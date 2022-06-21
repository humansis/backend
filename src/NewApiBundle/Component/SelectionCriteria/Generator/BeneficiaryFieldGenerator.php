<?php
declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria\Generator;

use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Enum\ResidencyStatus;
use BeneficiaryBundle\Repository\VulnerabilityCriterionRepository;
use NewApiBundle\Component\SelectionCriteria\FieldGeneratorInterface;
use NewApiBundle\Component\SelectionCriteria\Structure\Field;
use NewApiBundle\Enum\SelectionCriteriaField;

class BeneficiaryFieldGenerator implements FieldGeneratorInterface
{
    /** @var VulnerabilityCriterionRepository */
    private $vulnerabilityCriterionRepository;

    public function __construct(VulnerabilityCriterionRepository $vulnerabilityCriterionRepository)
    {
        $this->vulnerabilityCriterionRepository = $vulnerabilityCriterionRepository;
    }

    /**
     * @inheritdoc
     */
    public function generate(?string $countryIso3)
    {
        yield new Field(SelectionCriteriaField::GENDER, 'Gender', ['='], 'gender', [self::class, 'validateGender']);
        yield new Field(SelectionCriteriaField::DATE_OF_BIRTH, 'Date of Birth', ['=', '<', '>', '<=', '>='], 'date', [self::class, 'validateDate']);
        yield new Field(SelectionCriteriaField::RESIDENCY_STATUS, 'Residency Status', ['='], 'residencyStatus', [self::class, 'validateResidencyStatus']);
        yield new Field(SelectionCriteriaField::HAS_NOT_BEEN_IN_DISTRIBUTIONS_SINCE, 'Has Not Been In Distribution Since', ['='], 'date', [self::class, 'validateDate']);

        foreach ($this->vulnerabilityCriterionRepository->findAllActive() as $vulnerabilityCriterion) {
            yield new Field($vulnerabilityCriterion->getFieldString(), VulnerabilityCriterion::all()[$vulnerabilityCriterion->getFieldString()], ['='], 'boolean');
        }
    }

    /**
     * @inheritdoc
     */
    public function supports(string $target): bool
    {
        return $target === \NewApiBundle\Enum\SelectionCriteriaTarget::BENEFICIARY;
    }

    public static function validateGender($value): bool
    {
        return in_array($value, ['M', 'F'], true);
    }

    public static function validateDate($value): bool
    {
        return false !== strtotime((string) $value);
    }

    public static function validateResidencyStatus($value): bool
    {
        return in_array($value, ResidencyStatus::all(), true);
    }
}
