<?php

declare(strict_types=1);

namespace Component\Assistance\Domain;

use Component\SelectionCriteria\Enum\CriteriaValueTransformerEnum;
use Component\SelectionCriteria\Loader\CriterionConfiguration;
use Entity\Assistance\SelectionCriteria as SelectionCriteriaEntity;

class SelectionCriteria
{
    const TABLE_VULNERABILITY_CRITERIA = 'vulnerabilityCriteria';
    const TABLE_COUNTRY_SPECIFIC = 'countrySpecific';

    /** @var SelectionCriteriaEntity */
    private $criteriaRoot;

    /** @var CriterionConfiguration */
    private $configuration;

    /**
     * @param SelectionCriteriaEntity $criteriaRoot
     * @param CriterionConfiguration $configuration
     */
    public function __construct(SelectionCriteriaEntity $criteriaRoot, CriterionConfiguration $configuration)
    {
        $this->criteriaRoot = $criteriaRoot;
        $this->configuration = $configuration;
    }

    /**
     * @return SelectionCriteriaEntity
     */
    public function getCriteriaRoot(): SelectionCriteriaEntity
    {
        return $this->criteriaRoot;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        if ($this->criteriaRoot->getTableString() === 'Personnal') {
            return $this->configuration->getType();
        }

        return 'table_field';
    }

    /**
     * @return string|null
     */
    public function getConditionOperator(): ?string
    {
        return $this->criteriaRoot->getConditionString();
    }

    public function getField(): string
    {
        return $this->criteriaRoot->getFieldString();
    }

    public function hasValueString(): bool
    {
        // TODO: rename
        return !is_null($this->criteriaRoot->getValueString());
    }

    public function getValueString()
    {
        return $this->criteriaRoot->getValueString();
    }

    public function supportsHousehold(): bool
    {
        return $this->criteriaRoot->getTarget() === 'Household';
    }

    public function supportsHouseholdHead(): bool
    {
        return $this->criteriaRoot->getTarget() === 'Head';
    }

    public function supportsIndividual(): bool
    {
        return $this->criteriaRoot->getTarget() === 'Beneficiary';
    }

    public function hasCountrySpecificType(): bool
    {
        return $this->criteriaRoot->getTableString() === self::TABLE_COUNTRY_SPECIFIC;
    }

    public function hasTableFieldType(): bool
    {
        return $this->getType() === 'table_field';
    }

    public function hasTypeOther(): bool
    {
        return $this->getType() === 'other';
    }

    public function hasVulnerabilityCriteriaType(): bool
    {
        return $this->criteriaRoot->getTableString() === self::TABLE_VULNERABILITY_CRITERIA;
    }

    public function getTypedValue()
    {
        switch ($this->configuration->getReturnType()) {
            case CriteriaValueTransformerEnum::CONVERT_TO_INT:
                return (int) $this->getValueString();
            case CriteriaValueTransformerEnum::CONVERT_TO_FLOAT:
                return (float) $this->getValueString();
            case CriteriaValueTransformerEnum::CONVERT_TO_BOOL:
                return (bool) $this->getValueString();
            default:
                return $this->getValueString();
        }
    }
}
