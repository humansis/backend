<?php

declare(strict_types=1);

namespace Component\SelectionCriteria\Generator;

use Enum\ResidencyStatus;
use Enum\SelectionCriteriaTarget;
use Enum\VulnerabilityCriteria;
use Component\SelectionCriteria\FieldGeneratorInterface;
use Component\SelectionCriteria\Structure\Field;

class BeneficiaryFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public function generate(?string $countryIso3)
    {
        yield new Field('gender', 'Gender', ['='], 'gender', [self::class, 'validateGender']);
        yield new Field(
            'dateOfBirth',
            'Date of Birth',
            ['=', '<', '>', '<=', '>='],
            'date',
            [self::class, 'validateDate']
        );
        yield new Field(
            'residencyStatus',
            'Residency Status',
            ['='],
            'residencyStatus',
            [self::class, 'validateResidencyStatus']
        );
        yield new Field(
            'hasNotBeenInDistributionsSince',
            'Has Not Been In Distribution Since',
            ['='],
            'date',
            [self::class, 'validateDate']
        );

        foreach (VulnerabilityCriteria::values() as $vulnerabilityCriterion) {
            yield new Field(
                $vulnerabilityCriterion,
                VulnerabilityCriteria::all()[$vulnerabilityCriterion],
                ['='],
                'boolean'
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function supports(string $target): bool
    {
        return $target === SelectionCriteriaTarget::BENEFICIARY;
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
