<?php

namespace Entity;

use DateTime;
use DateTimeInterface;
use Entity\Helper\StandardizedPrimaryKey;
use Enum\AssistanceState;
use Enum\Livelihood;
use Utils\ExportableInterface;
use Enum\AssistanceTargetType;
use Enum\AssistanceType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Entity\Assistance\SelectionCriteria;
use DBAL\SectorEnum;
use DBAL\SubSectorEnum;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Assistance
 *
 * @ORM\Table(name="assistance")
 * @ORM\Entity(repositoryClass="Repository\AssistanceRepository")
 */
class Assistance implements ExportableInterface
{
    use StandardizedPrimaryKey;

    /**
     * @ORM\Column(name="assistance_type", type="enum_assistance_type")
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance', 'FullBooklet', 'AssistanceOverview'])]
    private string $assistanceType = AssistanceType::DISTRIBUTION;

    /**
     *
     * @ORM\Column(name="name", type="string", length=45)
     *
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance', 'FullBooklet', 'AssistanceOverview'])]
    private string $name;

    /**
     * @ORM\Column(name="UpdatedOn", type="datetime")
     */
    private DateTimeInterface $updatedOn;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_distribution", type="date")
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance', 'AssistanceOverview'])]
    private $dateDistribution;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="date_expiration", type="datetime", nullable=true)
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance', 'AssistanceOverview'])]
    private $dateExpiration;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Location")
     */
    private ?\Entity\Location $location = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\Project", inversedBy="distributions")
     *
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    private ?\Entity\Project $project = null;

    /**
     *
     * @ORM\OneToOne(targetEntity="Entity\AssistanceSelection", cascade={"persist"}, inversedBy="assistance", fetch="EAGER")
     * @ORM\JoinColumn(name="assistance_selection_id", nullable=false)
     */
    private \Entity\AssistanceSelection $assistanceSelection;

    /**
     *
     * @ORM\Column(name="archived", type="boolean", options={"default" : 0})
     *
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    private int|bool $archived = 0;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    private ?\Entity\User $validatedBy = null;

    /**
     *
     * @ORM\Column(name="target_type", type="enum_assistance_target_type")
     *
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance', 'AssistanceOverview'])]
    private ?string $targetType = null;

    /**
     * @var Collection | Commodity[]
     * @ORM\OneToMany(targetEntity="Entity\Commodity", mappedBy="assistance", cascade={"persist"})
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance', 'AssistanceOverview'])]
    private Collection | array $commodities;

    /**
     * @ORM\OneToMany(targetEntity="Entity\AssistanceBeneficiary", mappedBy="assistance", cascade={"persist"})
     */
    #[SymfonyGroups(['FullAssistance', 'FullProject'])]
    private $distributionBeneficiaries;

    /**
     *
     * @ORM\Column(name="completed", type="boolean", options={"default" : 0})
     *
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    private bool $completed = false;

    /**
     *
     * @see SectorEnum
     * @ORM\Column(name="sector", type="enum_sector", nullable=false)
     */
    private ?string $sector = null;

    /**
     *
     * @see SubSectorEnum
     * @ORM\Column(name="subsector", type="enum_sub_sector", nullable=true)
     */
    private ?string $subSector = null;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\ScoringBlueprint")
     *
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    private ?\Entity\ScoringBlueprint $scoringBlueprint = null;

    /**
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     *
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    private ?string $description = null;

    /**
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    private ?int $householdsTargeted = null;

    /**
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    private ?int $individualsTargeted = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $remoteDistributionAllowed = null;

    /**
     * @var numeric|null
     *
     * @ORM\Column(name="food_limit", type="decimal", nullable=true)
     */
    private $foodLimit;

    /**
     * @var numeric|null
     *
     * @ORM\Column(name="non_food_limit", type="decimal", nullable=true)
     */
    private $nonFoodLimit;

    /**
     * @var numeric|null
     *
     * @ORM\Column(name="cashback_limit", type="decimal", nullable=true)
     */
    private $cashbackLimit;

    /**
     * @ORM\Column(name="round", type="smallint", nullable=true)
     */
    private ?int $round = null;

    /**
     * @var string[]
     *
     * @ORM\Column(name="allowed_product_category_types", type="array", nullable=false)
     */
    private array $allowedProductCategoryTypes;

    /**
     * @var SmartcardPurchase[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\SmartcardPurchase", mappedBy="assistanceId")
     */
    private $smartcardPurchases;

    /**
     * @ORM\Column(name="note", type="text", length=65535, nullable=true)
     */
    private ?string $note = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->distributionBeneficiaries = new ArrayCollection();
        $this->commodities = new ArrayCollection();
        $this->assistanceSelection = new AssistanceSelection();
        $this->setUpdatedOn(new DateTime());
        $this->allowedProductCategoryTypes = [];
        $this->smartcardPurchases = new ArrayCollection();
    }

    public function getAssistanceType(): string
    {
        return $this->assistanceType;
    }

    public function setAssistanceType(string $assistanceType): Assistance
    {
        $this->assistanceType = $assistanceType;

        return $this;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Assistance
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set updatedOn.
     *
     *
     * @return Assistance
     */
    public function setUpdatedOn(DateTimeInterface $updatedOn)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updatedOn.
     *
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    public function getUpdatedOn(): string
    {
        return $this->updatedOn->format('Y-m-d H:i:s');
    }

    public function getUpdatedOnDateTime(): DateTime
    {
        return $this->updatedOn;
    }

    /**
     * Set archived.
     *
     *
     */
    public function setArchived(bool $archived): Assistance
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived.
     *
     * @return bool
     */
    public function getArchived()
    {
        return $this->archived;
    }

    /**
     * Set validated.
     *
     *
     */
    public function setValidatedBy(?User $validatedBy): Assistance
    {
        $this->validatedBy = $validatedBy;

        return $this;
    }

    /**
     * Get validated.
     */
    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    /**
     * Set completed.
     *
     *
     */
    public function setCompleted(bool $completed = true): Assistance
    {
        $this->completed = $completed;

        return $this;
    }

    /**
     * Get completed.
     *
     * @return bool
     */
    public function getCompleted()
    {
        return $this->completed;
    }

    /**
     * Set type.
     *
     *
     */
    public function setTargetType(string $targetType): Assistance
    {
        if (!in_array($targetType, AssistanceTargetType::values())) {
            throw new InvalidArgumentException(
                "Wrong assistance target type: $targetType, allowed are: "
                . implode(', ', AssistanceTargetType::values())
            );
        }
        $this->targetType = $targetType;

        return $this;
    }

    /**
     * Get type.
     */
    public function getTargetType(): string
    {
        return $this->targetType;
    }

    /**
     * Set location.
     *
     * @param Location|null $location
     *
     * @return Assistance
     */
    public function setLocation(Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set project.
     *
     * @param Project|null $project
     *
     * @return Assistance
     */
    public function setProject(Project $project = null)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project.
     *
     * @return Project|null
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Add selectionCriterion.
     *
     *
     * @return Assistance
     */
    public function addSelectionCriterion(SelectionCriteria $selectionCriterion)
    {
        $this->getAssistanceSelection()->getSelectionCriteria()->add($selectionCriterion);
        $selectionCriterion->setAssistanceSelection($this->getAssistanceSelection());

        return $this;
    }

    /**
     * Remove selectionCriterion.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeSelectionCriterion(SelectionCriteria $selectionCriterion)
    {
        return $this->getAssistanceSelection()->getSelectionCriteria()->removeElement($selectionCriterion);
    }

    /**
     * Get selectionCriteria.
     *
     *
     * @return Collection|SelectionCriteria[]
     */
    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    public function getSelectionCriteria(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->getAssistanceSelection()->getSelectionCriteria();
    }

    public function getAssistanceSelection(): AssistanceSelection
    {
        return $this->assistanceSelection;
    }

    /**
     * Add commodity.
     *
     *
     * @return Assistance
     */
    public function addCommodity(Commodity $commodity)
    {
        $commodity->setAssistance($this);
        $this->commodities[] = $commodity;

        return $this;
    }

    /**
     * Remove commodity.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCommodity(Commodity $commodity)
    {
        return $this->commodities->removeElement($commodity);
    }

    /**
     * Get commodities.
     *
     * @return Collection|Commodity[]
     */
    public function getCommodities(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->commodities;
    }

    /**
     * Add assistanceBeneficiary.
     *
     *
     * @return Assistance
     */
    public function addAssistanceBeneficiary(AssistanceBeneficiary $assistanceBeneficiary)
    {
        if (null === $this->distributionBeneficiaries) {
            $this->distributionBeneficiaries = new ArrayCollection();
        }
        $this->distributionBeneficiaries[] = $assistanceBeneficiary;

        return $this;
    }

    /**
     * Remove assistanceBeneficiary.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeAssistanceBeneficiary(AssistanceBeneficiary $assistanceBeneficiary)
    {
        return $this->distributionBeneficiaries->removeElement($assistanceBeneficiary);
    }

    /**
     * Get distributionBeneficiaries.
     *
     * @return Collection|AssistanceBeneficiary[]
     */
    public function getDistributionBeneficiaries(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->distributionBeneficiaries;
    }

    /**
     * Set dateDistribution.
     *
     *
     * @return Assistance
     */
    public function setDateDistribution(DateTimeInterface $dateDistribution)
    {
        $this->dateDistribution = $dateDistribution;

        return $this;
    }

    /**
     * Get dateDistribution.
     */
    public function getDateDistribution(): DateTimeInterface
    {
        return $this->dateDistribution;
    }

    public function getDateExpiration(): ?DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(?DateTimeInterface $dateExpiration): void
    {
        $this->dateExpiration = $dateExpiration;
    }

    public function getSector(): string
    {
        return $this->sector;
    }

    public function setSector(string $sector): void
    {
        if (!in_array($sector, SectorEnum::all())) {
            throw new InvalidArgumentException("Invalid sector: '$sector'");
        }

        $this->sector = $sector;
    }

    public function getSubSector(): ?string
    {
        return $this->subSector;
    }

    public function getScoringBlueprint(): ?ScoringBlueprint
    {
        return $this->scoringBlueprint;
    }

    public function setScoringBlueprint(?ScoringBlueprint $scoringBlueprint): Assistance
    {
        $this->scoringBlueprint = $scoringBlueprint;

        return $this;
    }

    public function getState(): string
    {
        if ($this->getCompleted()) {
            return AssistanceState::CLOSED;
        }

        if ($this->isValidated()) {
            return AssistanceState::VALIDATED;
        }

        return AssistanceState::NEW;
    }

    public function setDescription(?string $description): Assistance
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getHouseholdsTargeted(): ?int
    {
        return $this->householdsTargeted;
    }

    public function setHouseholdsTargeted(?int $householdsTargeted): void
    {
        $this->householdsTargeted = $householdsTargeted;
    }

    public function getIndividualsTargeted(): ?int
    {
        return $this->individualsTargeted;
    }

    public function setIndividualsTargeted(?int $individualsTargeted): void
    {
        $this->individualsTargeted = $individualsTargeted;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function setSubSector(?string $subSector): void
    {
        if (null !== $subSector && !in_array($subSector, SubSectorEnum::all())) {
            throw new InvalidArgumentException("Invalid subSector: '$subSector'");
        }

        $this->subSector = $subSector;
    }

    public function isValidated(): bool
    {
        return $this->validatedBy !== null;
    }

    public function getMappedValueForExport(): array
    {
        // récuperer les criteria de selection  depuis l'objet selectioncriteria

        $valueselectioncriteria = [];
        foreach ($this->getSelectionCriteria() as $criterion) {
            // First we split the camelCase field names
            $field = implode(' ', preg_split('/(?=[A-Z])/', $criterion->getFieldString()));
            // Then we replace the = by a :
            $condition = $criterion->getConditionString() === '=' ? ':' : $criterion->getConditionString();
            $value = $criterion->getValueString();

            // Then we make the string coherent
            if ($field === 'livelihood') {
                $value = Livelihood::translate($value);
            } elseif ($field === 'camp Name') {
                $field = 'camp Id';
            }

            if ($field === 'gender' || $field === 'head Of Household Gender') {
                $stringCriterion = $field . " " . $condition . ($value === '0' ? ' Female' : ' Male');
            } elseif ($condition === 'true') {
                $stringCriterion = $field;
            } elseif ($condition === 'false') {
                $stringCriterion = 'not ' . $field;
            } else {
                $stringCriterion = $field . " " . $condition . " " . $value;
            }
            array_push($valueselectioncriteria, $stringCriterion);
        }
        $valueselectioncriteria = join(', ', $valueselectioncriteria);

        // récuperer les valeurs des commodities depuis l'objet commodities

        $valuescommodities = [];

        foreach ($this->getCommodities() as $commodity) {
            $stringCommodity = $commodity->getModalityType() . " " . $commodity->getValue() . " " . $commodity->getUnit(
            );
            array_push($valuescommodities, $stringCommodity);
        }
        $valuescommodities = join(',', $valuescommodities);

        //récuperer les valeurs des distributions des beneficiaires depuis l'objet distribution
        // $valuesdistributionbeneficiaries = [];

        // foreach ($this->getDistributionBeneficiaries() as $value) {
        //     array_push($valuesdistributionbeneficiaries, $value->getIdNumber());
        // }
        // $valuesdistributionbeneficiaries = join(',',$valuesdistributionbeneficiaries);

        $percentage = '';
        foreach ($this->getCommodities() as $index => $commodity) {
            $percentage .= $index !== 0 ? ', ' : '';
            if ($this->isValidated()) {
                $percentage .= $this->getPercentageValue($commodity) . '% ' . $commodity->getModalityType();
            } else {
                $percentage .= '0% ' . $commodity->getModalityType();
            }
        }

        $typeString = $this->getTargetType() === AssistanceTargetType::INDIVIDUAL ? 'Beneficiaries' : 'Households';

        $adm1 = $this->getLocation()->getAdm1Name();
        $adm2 = $this->getLocation()->getAdm2Name();
        $adm3 = $this->getLocation()->getAdm3Name();
        $adm4 = $this->getLocation()->getAdm4Name();

        return [
            "ID" => $this->getId(),
            "project" => $this->getProject()->getName(),
            "type" => $typeString,
            // "Archived"=> $this->getArchived(),
            "adm1" => $adm1,
            "adm2" => $adm2,
            "adm3" => $adm3,
            "adm4" => $adm4,
            "Name" => $this->getName(),
            "Date of distribution " => $this->getDateDistribution(),
            "Update on " => $this->updatedOn,
            "Selection criteria" => $valueselectioncriteria,
            "Commodities " => $valuescommodities,
            "Number of beneficiaries" => count($this->getDistributionBeneficiaries()),
            "Percentage distributed" => $percentage,
            // "Distribution beneficiaries" =>$valuesdistributionbeneficiaries,
        ];
    }

    public function getPercentageValue($commodity)
    {
        $totalCommodityValue = count($this->getDistributionBeneficiaries()) * $commodity->getValue();
        if ($totalCommodityValue <= 0.00001) {
            return 0;
        }

        $amountSent = 0;
        foreach ($this->getDistributionBeneficiaries() as $assistanceBeneficiary) {
            $amountSent += $this->getCommoditySentAmountFromBeneficiary($commodity, $assistanceBeneficiary);
        }
        $percentage = $amountSent / $totalCommodityValue * 100;

        return round($percentage * 100) / 100;
    }

    public function getCommoditySentAmountFromBeneficiary(
        Commodity $commodity,
        AssistanceBeneficiary $assistanceBeneficiary
    ): int {
        $sent = 0;
        foreach ($assistanceBeneficiary->getReliefPackages() as $package) {
            if ($package->getModalityType() == $commodity->getModalityType()) {
                $sent += floatval($package->getAmountDistributed());
            }
        }

        return floor($sent);
    }

    public function isRemoteDistributionAllowed(): ?bool
    {
        return $this->remoteDistributionAllowed;
    }

    /**
     * @param bool|true $remoteDistributionAllowed
     */
    public function setRemoteDistributionAllowed(?bool $remoteDistributionAllowed): void
    {
        $this->remoteDistributionAllowed = $remoteDistributionAllowed;
    }

    public function getFoodLimit(): ?string
    {
        return $this->foodLimit;
    }

    /**
     * @param float|int|string|null $foodLimit
     */
    public function setFoodLimit($foodLimit): void
    {
        if (gettype($foodLimit) === 'integer' || gettype($foodLimit) === 'double') {
            $this->foodLimit = number_format($foodLimit, 2, '.', '');
        } else {
            if ((gettype($foodLimit) === 'string' && is_numeric($foodLimit)) || null === $foodLimit) {
                $this->foodLimit = $foodLimit;
            } else {
                throw new InvalidArgumentException("'$foodLimit' is not valid numeric format.");
            }
        }
    }

    public function getNonFoodLimit(): ?string
    {
        return $this->nonFoodLimit;
    }

    /**
     * @param float|int|string|null $nonFoodLimit
     */
    public function setNonFoodLimit($nonFoodLimit): void
    {
        if (gettype($nonFoodLimit) === 'integer' || gettype($nonFoodLimit) === 'double') {
            $this->nonFoodLimit = number_format($nonFoodLimit, 2, '.', '');
        } else {
            if ((gettype($nonFoodLimit) === 'string' && is_numeric($nonFoodLimit)) || null === $nonFoodLimit) {
                $this->nonFoodLimit = $nonFoodLimit;
            } else {
                throw new InvalidArgumentException("'$nonFoodLimit' is not valid numeric format.");
            }
        }
    }

    public function getCashbackLimit(): ?string
    {
        return $this->cashbackLimit;
    }

    /**
     * @param float|int|string|null $cashbackLimit
     */
    public function setCashbackLimit($cashbackLimit): void
    {
        if (gettype($cashbackLimit) === 'integer' || gettype($cashbackLimit) === 'double') {
            $this->cashbackLimit = number_format($cashbackLimit, 2, '.', '');
        } else {
            if ((gettype($cashbackLimit) === 'string' && is_numeric($cashbackLimit)) || null === $cashbackLimit) {
                $this->cashbackLimit = $cashbackLimit;
            } else {
                throw new InvalidArgumentException("'$cashbackLimit' is not valid numeric format.");
            }
        }
    }

    /**
     * @return string[]
     */
    public function getAllowedProductCategoryTypes(): array
    {
        return $this->allowedProductCategoryTypes;
    }

    /**
     * @param string[] $allowedProductCategoryTypes
     */
    public function setAllowedProductCategoryTypes(array $allowedProductCategoryTypes): void
    {
        $this->allowedProductCategoryTypes = $allowedProductCategoryTypes;
    }

    /**
     * @return SmartcardPurchase[]|Collection
     */
    public function getSmartcardPurchases(): Collection
    {
        return $this->smartcardPurchases;
    }

    public function setSmartcardPurchases(Collection $smartcardPurchases): void
    {
        $this->smartcardPurchases = $smartcardPurchases;
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
     * Returns if assistance has at least one commodity with given modality type
     *
     * @param string $modalityType - You can use Enum\ModalityType
     */
    public function hasModalityTypeCommodity(string $modalityType): bool
    {
        $hasModalityTypeCommodity = false;
        foreach ($this->commodities as $commodity) {
            $hasModalityTypeCommodity = $hasModalityTypeCommodity || $commodity->getModalityType() === $modalityType;
        }

        return $hasModalityTypeCommodity;
    }
}
