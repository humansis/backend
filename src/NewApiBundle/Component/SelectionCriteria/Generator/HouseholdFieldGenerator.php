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
        yield new Field('livelihood', 'Livelihood', ['='], 'livelihood', [self::class, 'validateLivelihood']);
        yield new Field('foodConsumptionScore', 'Food Consumption Score', ['=', '<', '>', '<=', '>='], 'double');
        yield new Field('copingStrategiesIndex', 'Coping Strategies Index', ['=', '<', '>', '<=', '>='], 'double');
        yield new Field('incomeLevel', 'Income Level', ['=', '<', '>', '<=', '>='], 'integer');
        yield new Field('householdSize', 'Household Size', ['=', '<', '>', '<=', '>='], 'integer');
        yield new Field('location', 'Location', ['='], 'location', 'is_int');
        yield new Field('locationType', 'Location Type', ['='], 'locationType', [self::class, 'validateLocation']);

        foreach ($this->countrySpecificRepository->findBy(['countryIso3' => $countryIso3], ['id'=>'asc']) as $countrySpecific) {
            $type = $this->transformCountrySpecificType($countrySpecific->getType());

            switch ($type) {
                case "integer":
                    $conditionList = ['=', '<', '>', '<=', '>='];
                    break;

                case "string":
                default:
                    $conditionList = ['='];
                    break;
            }

            yield new Field($countrySpecific->getFieldString(), $countrySpecific->getFieldString(), $conditionList, $type);
        }
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
            return 'integer';
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

    public static function validateLocation($value): bool
    {
        return is_int($value) || in_array($value, HouseholdLocation::LOCATION_TYPES, true);
    }
}
