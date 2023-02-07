<?php

declare(strict_types=1);

namespace Component\SelectionCriteria\Generator;

use Entity\HouseholdLocation;
use Generator;
use Repository\CountrySpecificRepository;
use Component\SelectionCriteria\FieldGeneratorInterface;
use Component\SelectionCriteria\Structure\Field;
use Enum\SelectionCriteriaTarget;
use Enum\Livelihood;
use RuntimeException;

class HouseholdFieldGenerator implements FieldGeneratorInterface
{
    public function __construct(private readonly CountrySpecificRepository $countrySpecificRepository)
    {
    }

    /**
     * @inheritdoc
     */
    public function generate(?string $countryIso3)
    {
        yield from $this->getStaticFields();
        foreach (
            $this->countrySpecificRepository
                ->findBy(['countryIso3' => $countryIso3], ['id' => 'asc']) as $countrySpecific
        ) {
            $type = $this->transformCountrySpecificType($countrySpecific->getType());

            switch ($type) {
                case "double":
                    $conditionList = ['=', '<', '>', '<=', '>='];
                    /*
                     * Nelze pouzit implicitni validaci.
                     * Implicitni validace pouzije podle hodnoty type kontrolu pouzitim is_double().
                     * Pro cislo zadane bez desetinne tecky vraci is_double() false
                     * is_double(1) vraci false, is_double(1.1) vraci true
                     */
                    $validator = fn($value) => is_numeric($value);
                    break;

                case "string":
                default:
                    $conditionList = ['='];
                    $validator = fn($value) => is_string($value);
                    break;
            }

            yield new Field(
                $countrySpecific->getFieldString(),
                $countrySpecific->getFieldString(),
                $conditionList,
                $type,
                $validator
            );
        }
    }

    private function getStaticFields(): Generator
    {
        //yield new Field('copingStrategiesIndex', 'Coping Strategies Index', ['=', '<', '>', '<=', '>='], 'integer');
        //yield new Field('foodConsumptionScore', 'Food Consumption Score', ['=', '<', '>', '<=', '>='], 'integer');
        yield new Field('livelihood', 'Livelihood', ['='], 'livelihood', [self::class, 'validateLivelihood']);
        yield new Field('income', 'Income', ['=', '<', '>', '<=', '>='], 'integer');
        yield new Field('householdSize', 'Household Size', ['=', '<', '>', '<=', '>='], 'integer');
        yield new Field('location', 'Location', ['='], 'location', 'is_int');
        yield new Field('locationType', 'Location Type', ['='], 'locationType', [self::class, 'validateLocationType']);
    }

    /**
     * @inheritdoc
     */
    public function supports(string $target): bool
    {
        return $target === SelectionCriteriaTarget::HOUSEHOLD;
    }

    public function transformCountrySpecificType($type): string
    {
        if ('number' === $type) {
            return 'double';
        } elseif ('text' === $type) {
            return 'string';
        } else {
            throw new RuntimeException('Invalid CountrySpecific type ' . $type);
        }
    }

    public static function validateLivelihood($value): bool
    {
        return in_array($value, Livelihood::values(), true);
    }

    public static function validateLocationType($value): bool
    {
        return self::isValueIndexOfHouseholdLocationTypeArray($value) || in_array($value, HouseholdLocation::LOCATION_TYPES, true);
    }

    public static function isValueIndexOfHouseholdLocationTypeArray(string|int $value): bool
    {
        if (!ctype_digit($value) && !is_int($value)) {
            return false;
        }

        return array_key_exists((int) $value, HouseholdLocation::LOCATION_TYPES);
    }
}
