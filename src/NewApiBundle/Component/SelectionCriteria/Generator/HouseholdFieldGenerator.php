<?php
declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria\Generator;

use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Repository\CountrySpecificRepository;
use NewApiBundle\Component\SelectionCriteria\FieldGeneratorInterface;
use NewApiBundle\Component\SelectionCriteria\Structure\Field;
use NewApiBundle\Enum\SelectionCriteriaTarget;
use ProjectBundle\Enum\Livelihood;

class HouseholdFieldGenerator implements FieldGeneratorInterface
{
    /** @var CountrySpecificRepository */
    private $countrySpecificRepository;

    public function __construct(CountrySpecificRepository $countrySpecificRepository)
    {
        $this->countrySpecificRepository = $countrySpecificRepository;
    }

    /**
     * @inheritdoc
     */
    public function generate(?string $countryIso3)
    {
        yield from $this->getStaticFields();
        foreach ($this->countrySpecificRepository->findBy(['countryIso3' => $countryIso3], ['id'=>'asc']) as $countrySpecific) {
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
                    $validator = function ($value) {
                        return is_numeric($value);
                    };
                    break;

                case "string":
                default:
                    $conditionList = ['='];
                    $validator = function ($value) {
                        return is_string($value);
                    };
                    break;
            }

            yield new Field($countrySpecific->getFieldString(), $countrySpecific->getFieldString(), $conditionList, $type, $validator);
        }
    }

    /**
     * @return \Generator
     */
    private function getStaticFields(): \Generator {
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
            throw new \RuntimeException('Invalid CountrySpecific type '.$type);
        }
    }

    public static function validateLivelihood($value): bool
    {
        return in_array($value, Livelihood::values(), true);
    }

    public static function validateLocationType($value): bool
    {
        return is_int($value) || in_array($value, HouseholdLocation::LOCATION_TYPES, true);
    }
}
