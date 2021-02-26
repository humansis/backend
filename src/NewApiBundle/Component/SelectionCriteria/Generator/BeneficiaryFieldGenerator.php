<?php
declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria\Generator;

use BeneficiaryBundle\Enum\ResidencyStatus;
use BeneficiaryBundle\Repository\VulnerabilityCriterionRepository;
use NewApiBundle\Component\SelectionCriteria\FieldGeneratorInterface;
use NewApiBundle\Component\SelectionCriteria\Structure\Field;

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
        yield new Field('gender', ['='], 'gender', [self::class, 'validateGender']);
        yield new Field('dateOfBirth', ['=', '<', '>', '<=', '>='], 'date', [self::class, 'validateDate']);
        yield new Field('residency_status', ['='], 'residencyStatus', [self::class, 'validateResidencyStatus']);
        yield new Field('hasNotBeenInDistributionsSince', ['='], 'boolean');

        foreach ($this->vulnerabilityCriterionRepository->findAllActive() as $vulnerabilityCriterion) {
            yield new Field($vulnerabilityCriterion->getFieldString(), ['='], 'boolean');
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
