<?php

declare(strict_types=1);

namespace Component\SelectionCriteria\Generator;

use Entity\VulnerabilityCriterion;
use Component\SelectionCriteria\FieldGeneratorInterface;
use Component\SelectionCriteria\Structure\Field;
use Enum\EnumValueNoFoundException;
use Enum\PersonGender;
use Enum\SelectionCriteriaTarget;

class HouseholdHeadFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public function generate(?string $countryIso3)
    {
        yield new Field('gender', 'Gender', ['='], 'gender', [self::class, 'validateGender']);
        yield new Field('headOfHouseholdDateOfBirth', 'Date of Birth', ['=', '<', '>', '<=', '>='], 'date', [self::class, 'validateDate']);
        yield new Field('disabledHeadOfHousehold', VulnerabilityCriterion::all()[VulnerabilityCriterion::CRITERION_DISABLED], ['='], 'boolean');
        yield new Field('hasValidSmartcard', 'Has valid card', ['='], 'boolean');
    }

    /**
     * @inheritdoc
     */
    public function supports(string $target): bool
    {
        return $target === SelectionCriteriaTarget::HOUSEHOLD_HEAD;
    }

    public static function validateGender($value): bool
    {
        try {
            return PersonGender::valueFromAPI($value) ? true : false;
        } catch (EnumValueNoFoundException $e) {
            return false;
        }
    }

    public static function validateDate($value): bool
    {
        return false !== strtotime((string) $value);
    }
}
