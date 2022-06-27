<?php declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria\Loader;

use NewApiBundle\Component\SelectionCriteria\Enum\CriteriaValueTransformerEnum;

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
}
