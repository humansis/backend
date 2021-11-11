<?php
declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria\Generator;

use NewApiBundle\Component\SelectionCriteria\FieldGeneratorInterface;
use NewApiBundle\Component\SelectionCriteria\Structure\Field;

class HouseholdHeadFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public function generate(?string $countryIso3)
    {
        yield new Field('gender', 'Gender', ['='], 'gender', [self::class, 'validateGender']);
        yield new Field('headOfHouseholdDateOfBirth', 'Date of Birth', ['=', '<', '>', '<=', '>='], 'date', [self::class, 'validateDate']);
        yield new Field('disabledHeadOfHousehold', 'Disabled', ['='], 'boolean');
        yield new Field('hasValidSmartcard', 'Has valid card', ['='], 'boolean');
    }

    /**
     * @inheritdoc
     */
    public function supports(string $target): bool
    {
        return $target === \NewApiBundle\Enum\SelectionCriteriaTarget::HOUSEHOLD_HEAD;
    }

    public static function validateGender($value): bool
    {
        return in_array($value, ['M', 'F'], true);
    }

    public static function validateDate($value): bool
    {
        return false !== strtotime((string) $value);
    }
}
