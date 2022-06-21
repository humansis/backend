<?php
declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria\Generator;

use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use NewApiBundle\Component\SelectionCriteria\FieldGeneratorInterface;
use NewApiBundle\Component\SelectionCriteria\Structure\Field;
use NewApiBundle\Enum\EnumValueNoFoundException;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\Enum\SelectionCriteriaField;

class HouseholdHeadFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public function generate(?string $countryIso3)
    {
        yield new Field(SelectionCriteriaField::GENDER, 'Gender', ['='], 'gender', [self::class, 'validateGender']);
        yield new Field(SelectionCriteriaField::HEAD_OF_HOUSEHOLD_DATE_OF_BIRTH, 'Date of Birth', ['=', '<', '>', '<=', '>='], 'date', [self::class, 'validateDate']);
        yield new Field(SelectionCriteriaField::DISABLED_HEAD_OF_HOUSEHOLD, VulnerabilityCriterion::all()[VulnerabilityCriterion::CRITERION_DISABLED], ['='], 'boolean');
        yield new Field(SelectionCriteriaField::HAS_VALID_SMARTCARD, 'Has valid card', ['='], 'boolean');
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
