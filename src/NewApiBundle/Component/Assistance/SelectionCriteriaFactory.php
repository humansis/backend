<?php declare(strict_types=1);

namespace NewApiBundle\Component\Assistance;

use DistributionBundle\Utils\ConfigurationLoader;
use NewApiBundle\Component\Assistance\Domain\SelectionCriteria;
use NewApiBundle\Entity\Assistance\SelectionCriteria as SelectionCriteriaEntity;

class SelectionCriteriaFactory
{
    /** @var ConfigurationLoader $configurationLoader */
    private $configurationLoader;

    /**
     * @param ConfigurationLoader $configurationLoader
     */
    public function __construct(ConfigurationLoader $configurationLoader)
    {
        $this->configurationLoader = $configurationLoader;
    }

    /**
     * @param string $condition
     * @param string $field
     * @param string $target
     * @param $valueString
     * @param int    $weight
     *
     * @return SelectionCriteriaEntity
     */
    public function createPersonnal(
        string $condition,
        string $field,
        string $target,
        $valueString,
        int $weight
    ): SelectionCriteriaEntity
    {
        $criteria = new SelectionCriteriaEntity();
        $criteria->setConditionString($condition);
        $criteria->setFieldString($field);
        $criteria->setTarget($target);
        $criteria->setValueString($valueString);
        $criteria->setWeight($weight);
        $criteria->setTableString('Personnal');
        return $criteria;
    }

    public function createCountrySpecific(
        string $condition,
        string $field,
        string $target,
               $valueString,
        int $weight
    ): SelectionCriteriaEntity
    {
        $criteria = new SelectionCriteriaEntity();
        $criteria->setConditionString($condition);
        $criteria->setFieldString($field);
        $criteria->setTarget($target);
        $criteria->setValueString($valueString);
        $criteria->setWeight($weight);
        $criteria->setTableString('countrySpecific');
        return $criteria;
    }

    public function createVulnerability(
        string $field,
        string $target,
               $valueString,
        int $weight
    ): SelectionCriteriaEntity
    {
        $criteria = new SelectionCriteriaEntity();
        $criteria->setConditionString($valueString);
        $criteria->setFieldString($field);
        $criteria->setTarget($target);
        $criteria->setValueString(null);
        $criteria->setWeight($weight);
        $criteria->setTableString('vulnerabilityCriteria');
        return $criteria;
    }

    public function hydrate(SelectionCriteriaEntity $criteriaEntity): SelectionCriteria
    {
        return new SelectionCriteria(
            $criteriaEntity,
            $this->configurationLoader->criteria[$criteriaEntity->getFieldString()]
        );
    }
}
