<?php

declare(strict_types=1);

namespace InputType;

use Enum\ModalityType;
use Enum\ProductCategoryType;
use Enum\SelectionCriteriaField;
use InputType\Assistance\CommodityInputType;
use InputType\Assistance\SelectionCriterionInputType;
use Request\InputTypeNullableDenormalizer;
use Utils\DateTime\Iso8601Converter;
use Validator\Constraints\Country;
use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['AssistanceCreateInputType', 'Strict', 'AdditionalChecks'])]
class AssistanceCreateInputType implements InputTypeNullableDenormalizer
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Country]
    private $iso3;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Iso8601]
    private $dateDistribution;

    #[Iso8601]
    private $dateExpiration = null;

    #[Assert\Type('string')]
    private $description = null;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $projectId;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $locationId;

    #[Assert\Choice(callback: [\Enum\AssistanceTargetType::class, 'values'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $target;

    #[Assert\Choice(callback: [\Enum\AssistanceType::class, 'values'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $type;

    #[Assert\Choice(callback: [\DBAL\SectorEnum::class, 'all'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $sector;

    #[Assert\Choice(callback: [\DBAL\SubSectorEnum::class, 'all'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $subsector;

    #[Assert\Type('integer')]
    private $scoringBlueprintId = null;

    /**
     * @var CommodityInputType[]
     */
    #[Assert\Type('array')]
    #[Assert\Valid]
    private $commodities = [];

    /**
     * @var SelectionCriterionInputType[]
     */
    #[Assert\Type('array')]
    #[Assert\Valid]
    private $selectionCriteria = [];

    #[Assert\Type('integer')]
    private $threshold = null;

    #[Assert\All(constraints: [new Assert\Type('integer', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    private $communities = [];

    #[Assert\All(constraints: [new Assert\Type('integer', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    private $institutions = [];

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private $householdsTargeted = 0;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private $individualsTargeted = 0;

    #[Assert\Type('boolean')]
    private $completed = false;

    #[Assert\Type('boolean')]
    private $validated = false;

    #[Assert\Type('numeric')]
    private $foodLimit = null;

    #[Assert\Type('numeric')]
    private $nonFoodLimit = null;

    #[Assert\Type('numeric')]
    private $cashbackLimit = null;

    #[Assert\Type('boolean')]
    private $remoteDistributionAllowed = null;

    #[Assert\Type('string')]
    private $note = null;

    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    #[Assert\LessThan(100)]
    private $round = null;

    #[Assert\All(
        constraints: [
            new Assert\Choice(callback: [ProductCategoryType::class, "values"], strict: true, groups: ['Strict']),
        ],
        groups: ['Strict']
    )]
    #[Assert\Type('array')]
    private $allowedProductCategoryTypes;

    #[Assert\Type('string')]
    #[Assert\NotNull]
    private $name;

    #[Assert\IsTrue(message: 'Expiration date make sense only for Smartcard distribution.', groups: ['Strict'])]
    public function isExpirationDateOnlyForSmartcardDistribution(): bool
    {
        return count($this->getSmartcardCommodities()) > 0 || $this->getDateExpiration() === null;
    }

    #[Assert\IsTrue(message: 'Expiration date must be greater than distribution date', groups: ['AdditionalChecks'])]
    public function isExpirationDateValid(): bool
    {
        return $this->getDateExpiration() == null || $this->getDateExpiration() >= $this->getDateDistribution();
    }

    #[Assert\IsTrue(message: 'Please add BNF has valid card criterion for each group', groups: ['AdditionalChecks'])]
    public function isValidSmartcardForRemoteDistribution(): bool
    {
        if ($this->remoteDistributionAllowed) {
            $criteriaSorted = [];
            foreach ($this->getSelectionCriteria() as $key => $criteriaField) {
                $criteriaSorted[$criteriaField->getGroup()][] = $criteriaField;
            }

            /** @var SelectionCriterionInputType[] $groupFields */
            foreach ($criteriaSorted as $groupFields) {
                $hasValidSmartcardField = false;
                foreach ($groupFields as $groupField) {
                    if ($groupField->getField() === SelectionCriteriaField::HAS_VALID_SMARTCARD) {
                        if ($groupField->getValue() === true) {
                            $hasValidSmartcardField = true;
                        } else {
                            return false;
                        }
                    }
                }
                if ($hasValidSmartcardField === false) {
                    return false;
                }
            }
        }

        return true;
    }

    #[Assert\IsTrue(message: 'remoteDistributionAllowed must not be null if distribution is for smartcards. Null otherwise.', groups: ['AdditionalChecks'])]
    public function isNotNullRemoteDistributionWhenSmartcard(): bool
    {
        return count($this->getSmartcardCommodities()) > 0 ?
            $this->remoteDistributionAllowed !== null :
            $this->remoteDistributionAllowed === null;
    }

    #[Assert\IsTrue(message: 'Assistance cannot have more than one smartcard commodity.', groups: ['AdditionalChecks'])]
    public function hasMaxOneSmartcardCommodity(): bool
    {
        return count($this->getSmartcardCommodities()) <= 1;
    }

    public function getIso3()
    {
        return $this->iso3;
    }

    public function setIso3($iso3)
    {
        $this->iso3 = $iso3;
    }

    public function getDateDistribution()
    {
        return Iso8601Converter::toDateTime($this->dateDistribution);
    }

    public function setDateDistribution($dateDistribution): void
    {
        $this->dateDistribution = $dateDistribution;
    }

    public function getDateExpiration()
    {
        return $this->dateExpiration ? Iso8601Converter::toDateTime((string) $this->dateExpiration) : null;
    }

    public function setDateExpiration($dateExpiration): void
    {
        $this->dateExpiration = $dateExpiration;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }

    public function getLocationId()
    {
        return $this->locationId;
    }

    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getSector()
    {
        return $this->sector;
    }

    public function setSector($sector)
    {
        $this->sector = $sector;
    }

    public function getSubsector()
    {
        return $this->subsector;
    }

    public function setSubsector($subsector)
    {
        $this->subsector = $subsector;
    }

    public function getScoringBlueprintId()
    {
        return $this->scoringBlueprintId;
    }

    public function setScoringBlueprintId($scoringBlueprintId)
    {
        $this->scoringBlueprintId = $scoringBlueprintId;

        return $this;
    }

    /**
     * @return Assistance\CommodityInputType[]
     */
    public function getCommodities()
    {
        return $this->commodities;
    }

    public function addCommodity(Assistance\CommodityInputType $commodity)
    {
        $this->commodities[] = $commodity;
    }

    public function removeCommodity($commodity)
    {
        // method must be declared to fullfill normalizer requirements
    }

    /**
     * @return Assistance\SelectionCriterionInputType[]
     */
    public function getSelectionCriteria()
    {
        return $this->selectionCriteria;
    }

    public function addSelectionCriterion(Assistance\SelectionCriterionInputType $selectionCriterion)
    {
        $this->selectionCriteria[] = $selectionCriterion;
    }

    public function removeSelectionCriterion($nationalIdCard)
    {
        // method must be declared to fullfill normalizer requirements
    }

    public function getThreshold()
    {
        return $this->threshold;
    }

    public function setThreshold($threshold): void
    {
        $this->threshold = $threshold;
    }

    public function getCompleted()
    {
        return $this->completed;
    }

    public function setCompleted($completed)
    {
        $this->completed = $completed;
    }

    public function getCommunities()
    {
        return $this->communities;
    }

    /**
     * @param int[]|null $communities
     */
    public function setCommunities($communities)
    {
        $this->communities = $communities;
    }

    /**
     * @return int[]
     */
    public function getInstitutions()
    {
        return $this->institutions;
    }

    /**
     * @param int[]|null $institutions
     */
    public function setInstitutions($institutions)
    {
        $this->institutions = $institutions;
    }

    public function getHouseholdsTargeted()
    {
        return $this->householdsTargeted;
    }

    public function setHouseholdsTargeted($householdsTargeted)
    {
        $this->householdsTargeted = $householdsTargeted;
    }

    public function getIndividualsTargeted()
    {
        return $this->individualsTargeted;
    }

    public function setIndividualsTargeted($individualsTargeted)
    {
        $this->individualsTargeted = $individualsTargeted;
    }

    public function getValidated()
    {
        return $this->validated;
    }

    public function setValidated($validated)
    {
        $this->validated = $validated;
    }

    public function getFoodLimit()
    {
        return $this->foodLimit;
    }

    public function setFoodLimit($foodLimit): void
    {
        $this->foodLimit = $foodLimit;
    }

    public function getNonFoodLimit()
    {
        return $this->nonFoodLimit;
    }

    public function setNonFoodLimit($nonFoodLimit): void
    {
        $this->nonFoodLimit = $nonFoodLimit;
    }

    public function getCashbackLimit()
    {
        return $this->cashbackLimit;
    }

    public function setCashbackLimit($cashbackLimit): void
    {
        $this->cashbackLimit = $cashbackLimit;
    }

    public function getRemoteDistributionAllowed()
    {
        return $this->remoteDistributionAllowed;
    }

    public function setRemoteDistributionAllowed($remoteDistributionAllowed)
    {
        $this->remoteDistributionAllowed = $remoteDistributionAllowed;
    }

    public function getAllowedProductCategoryTypes()
    {
        return $this->allowedProductCategoryTypes;
    }

    public function setAllowedProductCategoryTypes($allowedProductCategoryTypes): void
    {
        $this->allowedProductCategoryTypes = $allowedProductCategoryTypes;
    }

    public function getNote()
    {
        return $this->note;
    }

    public function setNote($note = null): void
    {
        $this->note = $note;
    }

    public function getRound()
    {
        return $this->round;
    }

    public function setRound($round): void
    {
        $this->round = $round;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return CommodityInputType[]
     */
    private function getSmartcardCommodities(): array
    {
        return array_filter(
            $this->commodities,
            fn(CommodityInputType $commodity) => $commodity->getModalityType() === ModalityType::SMART_CARD
        );
    }
}
