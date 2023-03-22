<?php

/** @noinspection PhpMissingParamTypeInspection */

/** @noinspection PhpMissingReturnTypeInspection */

/** @noinspection PhpMissingFieldTypeInspection */

declare(strict_types=1);

namespace InputType;

use DateTimeInterface;
use DBAL\SectorEnum;
use DBAL\SubSectorEnum;
use Enum\AssistanceTargetType;
use Enum\AssistanceType;
use Enum\ModalityType;
use Enum\ProductCategoryType;
use Enum\SelectionCriteriaField;
use Happyr\Validator\Constraint\EntityExist;
use InputType\Assistance\CommodityInputType;
use InputType\Assistance\SelectionCriterionInputType;
use Request\InputTypeNullableDenormalizer;
use Utils\DateTime\Iso8601Converter;
use Validator\Constraints\Country;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['AssistanceCreateInputType', 'Strict', 'AdditionalChecks'])]
class AssistanceCreateInputType implements InputTypeNullableDenormalizer
{
    #[Assert\NotBlank]
    #[Country]
    private $iso3;

    #[Assert\NotBlank]
    #[Assert\Date]
    private $dateDistribution;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Date]
    private $dateExpiration;

    #[Assert\Type('string')]
    private $description;

    /**
     * @EntityExist(entity="Entity\Project")
     */
    #[Assert\NotBlank]
    private $projectId;

    /**
     * @EntityExist(entity="Entity\Location")
     */
    #[Assert\NotBlank]
    private $locationId;

    #[Assert\Choice(callback: [AssistanceTargetType::class, 'values'])]
    #[Assert\NotBlank]
    private $target;

    #[Assert\Choice(callback: [AssistanceType::class, 'values'])]
    #[Assert\NotBlank]
    private $type;

    #[Assert\Choice(callback: [SectorEnum::class, 'all'])]
    #[Assert\NotBlank]
    private $sector;

    #[Assert\Choice(callback: [SubSectorEnum::class, 'all'])]
    #[Assert\NotBlank]
    private $subsector;

    #[Assert\Type('integer')]
    private $scoringBlueprintId;

    #[Assert\Type('array')]
    #[Assert\Valid]
    private $commodities = [];

    #[Assert\Type('array')]
    #[Assert\Valid]
    private $selectionCriteria = [];

    #[Assert\Type('integer')]
    private $threshold;

    #[Assert\All(constraints: [new Assert\Type('integer', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    private $communities;

    #[Assert\All(constraints: [new Assert\Type('integer', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    private $institutions;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private $householdsTargeted;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private $individualsTargeted;

    #[Assert\Type('boolean')]
    private $completed = false;

    #[Assert\Type('boolean')]
    private $validated = false;

    #[Assert\Type('numeric')]
    private $foodLimit;

    #[Assert\Type('numeric')]
    private $nonFoodLimit;

    #[Assert\Type('numeric')]
    private $cashbackLimit;

    #[Assert\Type('boolean')]
    private $remoteDistributionAllowed = null;

    #[Assert\Type('string')]
    private $note = null;

    #[Assert\Type('integer')]
    #[Assert\Range(notInRangeMessage: 'Supported round range is from {{ min }} to {{ max }}.', min: 1, max: 99)]
    private $round = null;

    #[Assert\All(
        constraints: [
            new Assert\Choice(callback: [ProductCategoryType::class, "values"], strict: true, groups: ['Strict']),
        ],
        groups: ['Strict']
    )]
    #[Assert\Type('array')]
    private $allowedProductCategoryTypes = [];

    #[Assert\Type('string')]
    #[Assert\NotBlank]
    private $name;

    #[Assert\IsTrue(message: 'Expiration date must be greater than distribution date', groups: ['Strict'])]
    public function isExpirationDateValid(): bool
    {
        return $this->getDateExpiration() == null || $this->getDateExpiration() >= $this->getDateDistribution();
    }

    #[Assert\IsTrue(message: 'Please add BNF has valid card criterion for each group', groups: ['AdditionalChecks'])]
    public function isValidSmartcardForRemoteDistribution(): bool
    {
        if ($this->remoteDistributionAllowed) {
            $criteriaSorted = [];
            foreach ($this->getSelectionCriteria() as $criteriaField) {
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
        /** @var CommodityInputType $commodity */
        foreach ($this->commodities as $commodity) {
            if ($commodity->getModalityType() === ModalityType::SMART_CARD) {
                return $this->remoteDistributionAllowed !== null;
            }
        }

        return $this->remoteDistributionAllowed === null;
    }

    #[Assert\IsTrue(message: 'Assistance cannot have more than one smartcard commodity.', groups: ['AdditionalChecks'])]
    public function hasMaxOneSmartcardCommodity(): bool
    {
        $smartcardCommodities = array_filter(
            $this->commodities,
            fn(CommodityInputType $commodity) => $commodity->getModalityType() === ModalityType::SMART_CARD
        );

        return count($smartcardCommodities) <= 1;
    }

    /**
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    /**
     * @param $iso3 string
     */
    public function setIso3($iso3)
    {
        $this->iso3 = $iso3;
    }

    public function getDateDistribution(): DateTimeInterface
    {
        return Iso8601Converter::toDateTime($this->dateDistribution, true);
    }

    public function setDateDistribution(string $dateDistribution): void
    {
        $this->dateDistribution = $dateDistribution;
    }

    public function getDateExpiration(): DateTimeInterface | null
    {
        return $this->dateExpiration ? Iso8601Converter::toDateTime($this->dateExpiration, true) : null;
    }

    public function setDateExpiration(string | null $dateExpiration): void
    {
        $this->dateExpiration = $dateExpiration;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $description string|null
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param $projectId int
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * @return int
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @param $locationId int
     */
    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param $target string
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type string
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getSector()
    {
        return $this->sector;
    }

    /**
     * @param $sector string
     */
    public function setSector($sector)
    {
        $this->sector = $sector;
    }

    /**
     * @return string
     */
    public function getSubsector()
    {
        return $this->subsector;
    }

    /**
     * @param $subsector string
     */
    public function setSubsector($subsector)
    {
        $this->subsector = $subsector;
    }

    /**
     * @return int | null
     */
    public function getScoringBlueprintId()
    {
        return $this->scoringBlueprintId;
    }

    /**
     * @param $scoringBlueprintId int | null
     */
    public function setScoringBlueprintId($scoringBlueprintId)
    {
        $this->scoringBlueprintId = $scoringBlueprintId;
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

    /**
     * @return int | null
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * @param $threshold int | null
     */
    public function setThreshold($threshold): void
    {
        $this->threshold = $threshold;
    }

    /**
     * @return bool | null
     */
    public function getCompleted()
    {
        return $this->completed;
    }

    /**
     * @param $completed bool | null
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;
    }

    /**
     * @return int[]|null
     */
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
     * @return int[]|null
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

    /**
     * @return int | null
     */
    public function getHouseholdsTargeted()
    {
        return $this->householdsTargeted;
    }

    /**
     * @param $householdsTargeted int | null
     */
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

    /**
     * @return bool
     */
    public function getValidated()
    {
        return $this->validated;
    }

    /**
     * @param $validated bool
     */
    public function setValidated($validated)
    {
        $this->validated = $validated;
    }

    /**
     * @return string|float|int|null
     */
    public function getFoodLimit()
    {
        return $this->foodLimit;
    }

    /**
     * @param $foodLimit string|float|int|null
     */
    public function setFoodLimit($foodLimit): void
    {
        $this->foodLimit = $foodLimit;
    }

    /**
     * @return string|float|int|null
     */
    public function getNonFoodLimit()
    {
        return $this->nonFoodLimit;
    }

    /**
     * @param $nonFoodLimit string|float|int|null
     */
    public function setNonFoodLimit($nonFoodLimit): void
    {
        $this->nonFoodLimit = $nonFoodLimit;
    }

    /**
     * @return string|float|int|null
     */
    public function getCashbackLimit()
    {
        return $this->cashbackLimit;
    }

    /**
     * @param $cashbackLimit string|float|int|null
     */
    public function setCashbackLimit($cashbackLimit): void
    {
        $this->cashbackLimit = $cashbackLimit;
    }

    /**
     * @return bool | null
     */
    public function getRemoteDistributionAllowed()
    {
        return $this->remoteDistributionAllowed;
    }

    /**
     * @param $remoteDistributionAllowed bool | null
     */
    public function setRemoteDistributionAllowed($remoteDistributionAllowed)
    {
        $this->remoteDistributionAllowed = $remoteDistributionAllowed;
    }

    /**
     * @return string[]
     */
    public function getAllowedProductCategoryTypes()
    {
        return $this->allowedProductCategoryTypes;
    }

    /**
     * @param $allowedProductCategoryTypes string[]
     */
    public function setAllowedProductCategoryTypes($allowedProductCategoryTypes)
    {
        $this->allowedProductCategoryTypes = $allowedProductCategoryTypes;
    }

    /**
     * @return null | string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param $note null | string
     */
    public function setNote($note): void
    {
        $this->note = $note;
    }

    /**
     * @return int | null
     */
    public function getRound()
    {
        return $this->round;
    }

    /**
     * @param $round int | null
     */
    public function setRound($round): void
    {
        $this->round = $round;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }
}
