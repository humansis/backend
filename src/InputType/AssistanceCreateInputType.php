<?php

declare(strict_types=1);

namespace InputType;

use DateTimeInterface;
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
    private string $iso3;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Date]
    private ?string $dateDistribution;

    #[Iso8601]
    #[Assert\NotBlank(allowNull: true)]
    private ?string $dateExpiration = null;

    #[Assert\Type('string')]
    private ?string $description = null;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private int $projectId;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private int $locationId;

    #[Assert\Choice(callback: [\Enum\AssistanceTargetType::class, 'values'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private string $target;

    #[Assert\Choice(callback: [\Enum\AssistanceType::class, 'values'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private string $type;

    #[Assert\Choice(callback: [\DBAL\SectorEnum::class, 'all'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private string $sector;

    #[Assert\Choice(callback: [\DBAL\SubSectorEnum::class, 'all'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private string $subsector;

    #[Assert\Type('integer')]
    private ?int $scoringBlueprintId = null;

    #[Assert\Type('array')]
    #[Assert\Valid]
    private array $commodities = [];

    #[Assert\Type('array')]
    #[Assert\Valid]
    private array $selectionCriteria = [];

    #[Assert\Type('integer')]
    private ?int $threshold;

    #[Assert\All(constraints: [new Assert\Type('integer', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    private ?array $communities;

    #[Assert\All(constraints: [new Assert\Type('integer', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    private ?array $institutions;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $householdsTargeted = null;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $individualsTargeted = null;

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
    private ?bool $remoteDistributionAllowed = null;

    #[Assert\Type('string')]
    private ?string $note = null;

    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    #[Assert\LessThan(100)]
    private ?int $round = null;

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
        $smartcardCommodities = array_filter($this->commodities, fn(CommodityInputType $commodity) => $commodity->getModalityType() === ModalityType::SMART_CARD);

        return count((array) $smartcardCommodities) <= 1;
    }

    public function getIso3(): string
    {
        return $this->iso3;
    }

    public function setIso3(string $iso3)
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function setProjectId(int $projectId)
    {
        $this->projectId = $projectId;
    }

    public function getLocationId(): int
    {
        return $this->locationId;
    }

    public function setLocationId(int $locationId)
    {
        $this->locationId = $locationId;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setTarget(string $target)
    {
        $this->target = $target;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function getSector(): string
    {
        return $this->sector;
    }

    public function setSector(string $sector)
    {
        $this->sector = $sector;
    }

    public function getSubsector(): string
    {
        return $this->subsector;
    }

    public function setSubsector(string $subsector)
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
    public function getCommodities(): array
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
    public function getSelectionCriteria(): array
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

    public function getThreshold(): ?int
    {
        return $this->threshold;
    }

    public function setThreshold(?int $threshold): void
    {
        $this->threshold = $threshold;
    }

    public function getCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed)
    {
        $this->completed = $completed;
    }

    /**
     * @return int[]|null
     */
    public function getCommunities(): ?array
    {
        return $this->communities;
    }

    /**
     * @param int[]|null $communities
     */
    public function setCommunities(?array $communities)
    {
        $this->communities = $communities;
    }

    /**
     * @return int[]|null
     */
    public function getInstitutions(): ?array
    {
        return $this->institutions;
    }

    /**
     * @param int[]|null $institutions
     */
    public function setInstitutions(?array $institutions)
    {
        $this->institutions = $institutions;
    }

    public function getHouseholdsTargeted(): ?int
    {
        return $this->householdsTargeted;
    }

    public function setHouseholdsTargeted(?int $householdsTargeted)
    {
        $this->householdsTargeted = $householdsTargeted;
    }

    public function getIndividualsTargeted(): ?int
    {
        return $this->individualsTargeted;
    }

    public function setIndividualsTargeted(?int $individualsTargeted)
    {
        $this->individualsTargeted = $individualsTargeted;
    }

    public function getValidated(): bool
    {
        return $this->validated;
    }

    public function setValidated(bool $validated)
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

    public function getRemoteDistributionAllowed(): ?bool
    {
        return $this->remoteDistributionAllowed;
    }

    public function setRemoteDistributionAllowed(?bool $remoteDistributionAllowed)
    {
        $this->remoteDistributionAllowed = $remoteDistributionAllowed;
    }

    public function getAllowedProductCategoryTypes(): array
    {
        return $this->allowedProductCategoryTypes;
    }

    public function setAllowedProductCategoryTypes(array $allowedProductCategoryTypes): void
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

    public function getRound(): ?int
    {
        return $this->round;
    }

    public function setRound(?int $round): void
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
