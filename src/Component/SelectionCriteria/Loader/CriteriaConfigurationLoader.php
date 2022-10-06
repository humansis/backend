<?php

declare(strict_types=1);

namespace Component\SelectionCriteria\Loader;

use Component\SelectionCriteria\Enum\CriteriaValueTransformerEnum;

class CriteriaConfigurationLoader
{
    public const
        TYPE_KEY = 'type',
        TARGET_KEY = 'target',
        VALUE_TRANSFORMER_KEY = 'valueTransformer';

    /**
     * @var array
     */
    private $configuration;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param string $key
     *
     * @return CriterionConfiguration
     */
    public function getCriterionConfiguration(string $key): CriterionConfiguration
    {
        return new CriterionConfiguration(
            $key,
            $this->configuration[$key][self::TYPE_KEY],
            $this->configuration[$key][self::TARGET_KEY],
            $this->configuration[$key][self::VALUE_TRANSFORMER_KEY] ?? CriteriaValueTransformerEnum::CONVERT_TO_STRING,
        );
    }

    public function guessReturnType(string $value): string
    {
        if (strtolower($value) === 'true' || strtolower($value) === 'false') {
            return CriteriaValueTransformerEnum::CONVERT_TO_BOOL;
        }
        if (!is_numeric($value)) {
            return CriteriaValueTransformerEnum::CONVERT_TO_STRING;
        }
        if (strpos($value, '.') !== false) {
            return CriteriaValueTransformerEnum::CONVERT_TO_FLOAT;
        }

        return CriteriaValueTransformerEnum::CONVERT_TO_INT;
    }
}
