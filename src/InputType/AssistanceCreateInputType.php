<?php

declare(strict_types=1);

namespace InputType;

use DateTimeInterface;
use Enum\ModalityType;
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
    /**
     * @Country
     */
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $iso3;

    /**
     * @Iso8601
     */
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $dateDistribution;

    /**
     * @Iso8601
     */
    private $dateExpiration;

    #[Assert\Type('string')]
    private $description;

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
    private ?int $scoringBlueprintId = null;

    #[Assert\Type('array')]
    #[Assert\Valid]
    private array $commodities = [];

    #[Assert\Type('array')]
    #[Assert\Valid]
    private array $selectionCriteria = [];

    #[Assert\Type('integer')]
    private $threshold;

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    private $communities;

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    private $institutions;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private $householdsTargeted;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private $individualsTargeted;

    #[Assert\Type('boolean')]
    private bool $completed = false;

    #[Assert\Type('boolean')]
    private bool $validated = false;

    #[Assert\Type('numeric')]
    private $foodLimit;

    #[Assert\Type('numeric')]
    private $nonFoodLimit;

    #[Assert\Type('numeric')]
    private $cashbackLimit;

    #[Assert\Type('boolean')]
    private $remoteDistributionAllowed;

    #[Assert\Type('string')]
    private $note;

    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    #[Assert\LessThan(100)]
    private ?int $round = null;

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback={"Enum\ProductCategoryType", "values"}, strict=true, groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    private $allowedProductCategoryTypes;

    #[Assert\IsTrue(groups: 'Strict', message: 'Expiration date must be greater than distribution date')]
    public function isExpirationDateValid(): bool
    {
        return $this->getDateExpiration() == null || $this->getDateExpiration() >= $this->getDateDistribution();
    }

    #[Assert\IsTrue(groups: 'AdditionalChecks', message: 'Please add BNF has valid card criterion for each group')]
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

    #[Assert\IsTrue(groups: 'AdditionalChecks', message: 'remoteDistributionAllowed must not be null if distribution is for smartcards. Null otherwise.')]
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

    #[Assert\IsTrue(groups: 'AdditionalChecks', message: 'Assistance cannot have more than one smartcard commodity.')]
    public function hasMaxOneSmartcardCommodity(): bool
    {
        $smartcardCommodities = array_filter($this->commodities, fn(CommodityInputType $commodity) => $commodity->getModalityType() === ModalityType::SMART_CARD);

        return count((array) $smartcardCommodities) <= 1;
    }

    /**
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    /**
     * @param string $iso3
     */
    public function setIso3($iso3)
    {
        $this->iso3 = $iso3;
    }

    public function getDateDistribution(): DateTimeInterface
    {
        return Iso8601Converter::toDateTime($this->dateDistribution);
    }

    public function setDateDistribution(string $dateDistribution): void
    {
        $this->dateDistribution = $dateDistribution;
    }

    public function getDateExpiration(): ?DateTimeInterface
    {
        return $this->dateExpiration ? Iso8601Converter::toDateTime($this->dateExpiration) : null;
    }

    public function setDateExpiration(?string $dateExpiration): void
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
     * @param string|null $description
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
     * @param int $projectId
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
     * @param int $locationId
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
     * @param string $target
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
     * @param string $type
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
     * @param string $sector
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
     * @param string $subsector
     */
    public function setSubsector($subsector)
    {
        $this->subsector = $subsector;
    }

    public function getScoringBlueprintId(): ?int
    {
        return $this->scoringBlueprintId;
    }

    public function setScoringBlueprintId(?int $scoringBlueprintId): AssistanceCreateInputType
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

    /**
     * @return int|null
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * @param int|null $threshold
     */
    public function setThreshold($threshold): void
    {
        $this->threshold = $threshold;
    }

    /**
     * @return bool
     */
    public function getCompleted()
    {
        return $this->completed;
    }

    /**
     * @param bool $completed
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
     * @return int|null
     */
    public function getHouseholdsTargeted()
    {
        return $this->householdsTargeted;
    }

    /**
     * @param int|null $householdsTargeted
     */
    public function setHouseholdsTargeted($householdsTargeted)
    {
        $this->householdsTargeted = $householdsTargeted;
    }

    /**
     * @return int|null
     */
    public function getIndividualsTargeted()
    {
        return $this->individualsTargeted;
    }

    /**
     * @param int|null $individualsTargeted
     */
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
     * @param bool $validated
     */
    public function setValidated($validated)
    {
        $this->validated = $validated;
    }

    /**
     * @return mixed
     */
    public function getFoodLimit()
    {
        return $this->foodLimit;
    }

    public function setFoodLimit(mixed $foodLimit): void
    {
        $this->foodLimit = $foodLimit;
    }

    /**
     * @return mixed
     */
    public function getNonFoodLimit()
    {
        return $this->nonFoodLimit;
    }

    public function setNonFoodLimit(mixed $nonFoodLimit): void
    {
        $this->nonFoodLimit = $nonFoodLimit;
    }

    /**
     * @return mixed
     */
    public function getCashbackLimit()
    {
        return $this->cashbackLimit;
    }

    public function setCashbackLimit(mixed $cashbackLimit): void
    {
        $this->cashbackLimit = $cashbackLimit;
    }

    /**
     * @return bool|null
     */
    public function getRemoteDistributionAllowed()
    {
        return $this->remoteDistributionAllowed;
    }

    /**
     * @param bool|null $remoteDistributionAllowed
     */
    public function setRemoteDistributionAllowed($remoteDistributionAllowed)
    {
        $this->remoteDistributionAllowed = $remoteDistributionAllowed;
    }

    /**
     * @return array
     */
    public function getAllowedProductCategoryTypes()
    {
        return $this->allowedProductCategoryTypes;
    }

    /**
     * @param array $allowedProductCategoryTypes
     */
    public function setAllowedProductCategoryTypes($allowedProductCategoryTypes): void
    {
        $this->allowedProductCategoryTypes = $allowedProductCategoryTypes;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note = null): void
    {
        $this->note = $note;
    }

    /**
     * @return int|null
     */
    public function getRound()
    {
        return $this->round;
    }

    public function setRound(?int $round): void
    {
        $this->round = $round;
    }
}
