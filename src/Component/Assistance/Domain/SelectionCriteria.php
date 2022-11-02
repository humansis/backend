<?php

declare(strict_types=1);

namespace Component\Assistance\Domain;

use Component\SelectionCriteria\Enum\CriteriaValueTransformerEnum;
use Component\SelectionCriteria\Loader\CriterionConfiguration;
use Entity\Assistance\SelectionCriteria as SelectionCriteriaEntity;

class SelectionCriteria
{
    public function __construct(private readonly SelectionCriteriaEntity $criteriaRoot, private readonly CriterionConfiguration $configuration)
    {
    }

    public function getCriteriaRoot(): SelectionCriteriaEntity
    {
        return $this->criteriaRoot;
    }

    public function getType(): string
    {
        if ($this->criteriaRoot->getTableString() === 'Personnal') {
            return $this->configuration->getType();
        }

        return 'table_field';
    }

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
        return $this->criteriaRoot->getTableString() === 'countrySpecific';
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
        return $this->criteriaRoot->getTableString() === 'vulnerabilityCriteria';
    }

    public function getTypedValue()
    {
        return match ($this->configuration->getReturnType()) {
            CriteriaValueTransformerEnum::CONVERT_TO_INT => (int) $this->getValueString(),
            CriteriaValueTransformerEnum::CONVERT_TO_FLOAT => (float) $this->getValueString(),
            CriteriaValueTransformerEnum::CONVERT_TO_BOOL => (bool) $this->getValueString(),
            default => $this->getValueString(),
        };
    }
}
