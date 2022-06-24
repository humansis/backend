<?php declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria\Loader;

use NewApiBundle\Component\SelectionCriteria\Enum\CriteriaReturnFunctionEnum;

class CriteriaConfigurationLoader
{
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
            $this->configuration[$key]['type'],
            $this->configuration[$key]['target'],
            $this->configuration[$key]['returnFunction'] ?? CriteriaReturnFunctionEnum::CONVERT_TO_STRING,
        );
    }
}
