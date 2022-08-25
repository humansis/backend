<?php

namespace DistributionBundle\Entity;

use CommonBundle\Entity\Location;
use CommonBundle\Utils\ExportableInterface;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Enum\AssistanceType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use NewApiBundle\Entity\Assistance\SelectionCriteria;
use NewApiBundle\Entity\ScoringBlueprint;
use ProjectBundle\DBAL\SectorEnum;
use ProjectBundle\DBAL\SubSectorEnum;
use ProjectBundle\Entity\Project;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use UserBundle\Entity\User;
use VoucherBundle\Entity\SmartcardPurchase;

/**
 * Assistance
 *
 * @ORM\Table(name="assistance")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\AssistanceRepository")
 */
class Assistance implements ExportableInterface
{
    const NAME_HEADER_ID = "ID SYNC";

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @SymfonyGroups({"FullAssistance", "SmallAssistance", "AssistanceOverview"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="assistance_type", type="enum_assistance_type")
     * @SymfonyGroups({"FullAssistance", "SmallAssistance", "FullBooklet", "AssistanceOverview"})
     */
    private $assistanceType = AssistanceType::DISTRIBUTION;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45)
     *
     * @SymfonyGroups({"FullAssistance", "SmallAssistance", "FullBooklet", "AssistanceOverview"})
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="UpdatedOn", type="datetime")
     */
    private $updatedOn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_distribution", type="date")
     *
     * @SymfonyGroups({"FullAssistance", "SmallAssistance", "AssistanceOverview"})
     */
    private $dateDistribution;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_expiration", type="datetime", nullable=true)
     *
     * @SymfonyGroups({"FullAssistance", "SmallAssistance", "AssistanceOverview"})
     */
    private $dateExpiration;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Location")
     */
    private $location;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="ProjectBundle\Entity\Project", inversedBy="distributions")
     *
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $project;

    /**
     * @var AssistanceSelection
     *
     * @ORM\OneToOne(targetEntity="DistributionBundle\Entity\AssistanceSelection", cascade={"persist"})
     * @ORM\JoinColumn(name="assistance_selection_id", nullable=false)
     */
    private $assistanceSelection;

    /**
     * @var boolean
     *
     * @ORM\Column(name="archived", type="boolean", options={"default" : 0})
     *
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $archived = 0;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $validatedBy = null;

    /**
     * @ORM\OneToMany(targetEntity="ReportingBundle\Entity\ReportingAssistance", mappedBy="distribution", cascade={"persist", "remove"})
     **/
    private $reportingDistribution;

    /**
     * @var string
     *
     * @ORM\Column(name="target_type", type="enum_assistance_target_type")
     *
     * @SymfonyGroups({"FullAssistance", "SmallAssistance", "AssistanceOverview"})
     */
    private $targetType;

    /**
     * @var Commodity[]
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\Commodity", mappedBy="assistance", cascade={"persist"})
     * @SymfonyGroups({"FullAssistance", "SmallAssistance", "AssistanceOverview"})
     */
    private $commodities;

    /**
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\AssistanceBeneficiary", mappedBy="assistance", cascade={"persist"})
     *
     * @SymfonyGroups({"FullAssistance", "FullProject"})
     */
    private $distributionBeneficiaries;

    /**
     * @var boolean
     *
     * @ORM\Column(name="completed", type="boolean", options={"default" : 0})
     *
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $completed = 0;

    /**
     * @var string
     *
     * @see SectorEnum
     *
     * @ORM\Column(name="sector", type="enum_sector", nullable=false)
     */
    private $sector;

    /**
     * @var string|null
     *
     * @see SubSectorEnum
     *
     * @ORM\Column(name="subsector", type="enum_sub_sector", nullable=true)
     */
    private $subSector;

    /**
     * @var ScoringBlueprint|null
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\ScoringBlueprint")
     *
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $scoringBlueprint;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     *
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $description;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $householdsTargeted;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $individualsTargeted;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $remoteDistributionAllowed;

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
     * @var int|null
     *
     * @ORM\Column(name="round", type="smallint", nullable=true)
     */
    private $round;

    /**
     * @var string[]
     *
     * @ORM\Column(name="allowed_product_category_types", type="array", nullable=false)
     */
    private $allowedProductCategoryTypes;

    /**
     * @var SmartcardPurchase[]|Collection
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\SmartcardPurchase", mappedBy="assistanceId")
     */
    private $smartcardPurchases;

    /**
     * @var string|null
     *
     * @ORM\Column(name="note", type="text", length=65535, nullable=true)
     */
    private $note;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reportingDistribution = new ArrayCollection();
        $this->distributionBeneficiaries = new ArrayCollection();
        $this->commodities = new ArrayCollection();
        $this->assistanceSelection = new AssistanceSelection();
        $this->setUpdatedOn(new \DateTime());
        $this->allowedProductCategoryTypes = [];
        $this->smartcardPurchases = new ArrayCollection();
    }

    /**
     * Set id.
     *
     * @param $id
     *
     * @return Assistance
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAssistanceType(): string
    {
        return $this->assistanceType;
    }

    /**
     * @param string $assistanceType
     *
     * @return Assistance
     */
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
     * @param \DateTimeInterface $updatedOn
     *
     * @return Assistance
     */
    public function setUpdatedOn(\DateTimeInterface $updatedOn)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updatedOn.
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     *
     * @return string
     */
    public function getUpdatedOn(): string
    {
        return $this->updatedOn->format('Y-m-d H:i:s');
    }

    public function getUpdatedOnDateTime(): \DateTime
    {
        return $this->updatedOn;
    }

    /**
     * Set archived.
     *
     * @param bool $archived
     *
     * @return Assistance
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
     * @param User|null $validatedBy
     *
     * @return Assistance
     */
    public function setValidatedBy(?User $validatedBy): Assistance
    {
        $this->validatedBy = $validatedBy;

        return $this;
    }

    /**
     * Get validated.
     *
     * @return User|null
     */
    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    /**
     * Set completed.
     *
     * @param bool $completed
     *
     * @return Assistance
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
     * @param string $targetType
     *
     * @return self
     */
    public function setTargetType(string $targetType): Assistance
    {
        if (!in_array($targetType, AssistanceTargetType::values())) {
            throw new \InvalidArgumentException("Wrong assistance target type: $targetType, allowed are: "
                .implode(', ', AssistanceTargetType::values()));
        }
        $this->targetType = $targetType;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getTargetType(): string
    {
        return $this->targetType;
    }

    /**
     * Set location.
     *
     * @param \CommonBundle\Entity\Location|null $location
     *
     * @return Assistance
     */
    public function setLocation(\CommonBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return \CommonBundle\Entity\Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set project.
     *
     * @param \ProjectBundle\Entity\Project|null $project
     *
     * @return Assistance
     */
    public function setProject(\ProjectBundle\Entity\Project $project = null)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project.
     *
     * @return \ProjectBundle\Entity\Project|null
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Add selectionCriterion.
     *
     * @param SelectionCriteria $selectionCriterion
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
     * @param SelectionCriteria $selectionCriterion
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeSelectionCriterion(SelectionCriteria $selectionCriterion)
    {
        return $this->getAssistanceSelection()->getSelectionCriteria()->removeElement($selectionCriterion);
    }

    /**
     * Get selectionCriteria.
     *
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     *
     * @return \Doctrine\Common\Collections\Collection|SelectionCriteria[]
     */
    public function getSelectionCriteria()
    {
        return $this->getAssistanceSelection()->getSelectionCriteria();
    }

    public function getAssistanceSelection(): AssistanceSelection
    {
        return $this->assistanceSelection;
    }

    /**
     * Add reportingDistribution.
     *
     * @param \ReportingBundle\Entity\ReportingAssistance $reportingDistribution
     *
     * @return Assistance
     */
    public function addReportingAssistance(\ReportingBundle\Entity\ReportingAssistance $reportingDistribution)
    {
        $this->reportingDistribution[] = $reportingDistribution;

        return $this;
    }

    /**
     * Remove reportingDistribution.
     *
     * @param \ReportingBundle\Entity\ReportingAssistance $reportingDistribution
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeReportingAssistance(\ReportingBundle\Entity\ReportingAssistance $reportingDistribution)
    {
        return $this->reportingDistribution->removeElement($reportingDistribution);
    }

    /**
     * Get reportingDistribution.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReportingAssistance()
    {
        return $this->reportingDistribution;
    }

    /**
     * Add commodity.
     *
     * @param \DistributionBundle\Entity\Commodity $commodity
     *
     * @return Assistance
     */
    public function addCommodity(\DistributionBundle\Entity\Commodity $commodity)
    {
        $commodity->setAssistance($this);
        $this->commodities[] = $commodity;

        return $this;
    }

    /**
     * Remove commodity.
     *
     * @param \DistributionBundle\Entity\Commodity $commodity
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCommodity(\DistributionBundle\Entity\Commodity $commodity)
    {
        return $this->commodities->removeElement($commodity);
    }

    /**
     * Get commodities.
     *
     * @return \Doctrine\Common\Collections\Collection|Commodity[]
     */
    public function getCommodities()
    {
        return $this->commodities;
    }

    /**
     * Add assistanceBeneficiary.
     *
     * @param \DistributionBundle\Entity\AssistanceBeneficiary $assistanceBeneficiary
     *
     * @return Assistance
     */
    public function addAssistanceBeneficiary(\DistributionBundle\Entity\AssistanceBeneficiary $assistanceBeneficiary)
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
     * @param \DistributionBundle\Entity\AssistanceBeneficiary $assistanceBeneficiary
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeAssistanceBeneficiary(\DistributionBundle\Entity\AssistanceBeneficiary $assistanceBeneficiary)
    {
        return $this->distributionBeneficiaries->removeElement($assistanceBeneficiary);
    }

    /**
     * Get distributionBeneficiaries.
     *
     * @return \Doctrine\Common\Collections\Collection|AssistanceBeneficiary[]
     */
    public function getDistributionBeneficiaries()
    {
        return $this->distributionBeneficiaries;
    }

    /**
     * Set dateDistribution.
     *
     * @param \DateTimeInterface $dateDistribution
     *
     * @return Assistance
     */
    public function setDateDistribution(\DateTimeInterface $dateDistribution)
    {
        $this->dateDistribution = $dateDistribution;

        return $this;
    }

    /**
     * Get dateDistribution.
     *
     * @return \DateTimeInterface
     */
    public function getDateDistribution(): \DateTimeInterface
    {
        return $this->dateDistribution;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->dateExpiration;
    }

    /**
     * @param \DateTimeInterface|null $dateExpiration
     */
    public function setDateExpiration(?\DateTimeInterface $dateExpiration): void
    {
        $this->dateExpiration = $dateExpiration;
    }

    /**
     * @return string
     */
    public function getSector(): string
    {
        return $this->sector;
    }

    /**
     * @param string $sector
     */
    public function setSector(string $sector): void
    {
        if (!in_array($sector, SectorEnum::all())) {
            throw new InvalidArgumentException("Invalid sector: '$sector'");
        }

        $this->sector = $sector;
    }

    /**
     * @return string|null
     */
    public function getSubSector(): ?string
    {
        return $this->subSector;
    }

    /**
     * @return ArrayCollection
     */
    public function getReportingDistribution(): ArrayCollection
    {
        return $this->reportingDistribution;
    }

    /**
     * @param ArrayCollection $reportingDistribution
     *
     * @return Assistance
     */
    public function setReportingDistribution(ArrayCollection $reportingDistribution): Assistance
    {
        $this->reportingDistribution = $reportingDistribution;

        return $this;
    }

    /**
     * @return ScoringBlueprint|null
     */
    public function getScoringBlueprint(): ?ScoringBlueprint
    {
        return $this->scoringBlueprint;
    }

    /**
     * @param ScoringBlueprint|null $scoringBlueprint
     *
     * @return Assistance
     */
    public function setScoringBlueprint(?ScoringBlueprint $scoringBlueprint): Assistance
    {
        $this->scoringBlueprint = $scoringBlueprint;

        return $this;
    }


    /**
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription(?string $description): Assistance
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return int|null
     */
    public function getHouseholdsTargeted(): ?int
    {
        return $this->householdsTargeted;
    }

    /**
     * @param int|null $householdsTargeted
     */
    public function setHouseholdsTargeted(?int $householdsTargeted): void
    {
        $this->householdsTargeted = $householdsTargeted;
    }

    /**
     * @return int|null
     */
    public function getIndividualsTargeted(): ?int
    {
        return $this->individualsTargeted;
    }

    /**
     * @param int|null $individualsTargeted
     */
    public function setIndividualsTargeted(?int $individualsTargeted): void
    {
        $this->individualsTargeted = $individualsTargeted;
    }

    /**
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param string|null $note
     */
    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    /**
     * @param string|null $subSector
     */
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
                $value = \ProjectBundle\Enum\Livelihood::translate($value);
            } elseif ($field === 'camp Name') {
                $field = 'camp Id';
            }

            if ($field === 'gender' || $field === 'head Of Household Gender') {
                $stringCriterion = $field." ".$condition.($value === '0' ? ' Female' : ' Male');
            } elseif ($condition === 'true') {
                $stringCriterion = $field;
            } elseif ($condition === 'false') {
                $stringCriterion = 'not '.$field;
            } else {
                $stringCriterion = $field." ".$condition." ".$value;
            }
            array_push($valueselectioncriteria, $stringCriterion);
        }
        $valueselectioncriteria = join(', ', $valueselectioncriteria);

        // récuperer les valeurs des commodities depuis l'objet commodities

        $valuescommodities = [];

        foreach ($this->getCommodities() as $commodity) {
            $stringCommodity = $commodity->getModalityType()." ".$commodity->getValue()." ".$commodity->getUnit();
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
                $percentage .= $this->getPercentageValue($commodity).'% '.$commodity->getModalityType();
            } else {
                $percentage .= '0% '.$commodity->getModalityType();
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

    public function getCommoditySentAmountFromBeneficiary(Commodity $commodity, AssistanceBeneficiary $assistanceBeneficiary): int
    {
        $sent = 0;
        foreach ($assistanceBeneficiary->getReliefPackages() as $package) {
            if ($package->getModalityType() == $commodity->getModalityType()) {
                $sent += floatval($package->getAmountDistributed());
            }
        }
        return floor($sent);
    }

    /**
     * @return bool|null
     */
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

    /**
     * @return string|null
     */
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
        } else if ( (gettype($foodLimit) === 'string' && is_numeric($foodLimit)) || null === $foodLimit) {
            $this->foodLimit = $foodLimit;
        } else {
            throw new InvalidArgumentException("'$foodLimit' is not valid numeric format.");
        }
    }

    /**
     * @return string|null
     */
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
        } else if ( (gettype($nonFoodLimit) === 'string' && is_numeric($nonFoodLimit)) || null === $nonFoodLimit) {
            $this->nonFoodLimit = $nonFoodLimit;
        } else {
            throw new InvalidArgumentException("'$nonFoodLimit' is not valid numeric format.");
        }
    }

    /**
     * @return string|null
     */
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
        } else if ( (gettype($cashbackLimit) === 'string' && is_numeric($cashbackLimit)) || null === $cashbackLimit) {
            $this->cashbackLimit = $cashbackLimit;
        } else {
            throw new InvalidArgumentException("'$cashbackLimit' is not valid numeric format.");
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

    /**
     * @param Collection $smartcardPurchases
     */
    public function setSmartcardPurchases(Collection $smartcardPurchases): void
    {
        $this->smartcardPurchases = $smartcardPurchases;
    }

    /**
     * @return int|null
     */
    public function getRound(): ?int
    {
        return $this->round;
    }

    /**
     * @param int|null $round
     */
    public function setRound(?int $round): void
    {
        $this->round = $round;
    }

    /**
     * Returns if assistance has at least one commodity with given modality type
     *
     * @param string $modalityType - You can use NewApiBundle\Enum\ModalityType
     *
     * @return bool
     */
    public function hasModalityTypeCommodity(string $modalityType): bool {
        $hasModalityTypeCommodity = false;
        foreach ($this->commodities as $commodity) {
            $hasModalityTypeCommodity = $hasModalityTypeCommodity || $commodity->getModalityType() === $modalityType;
        }
        return $hasModalityTypeCommodity;
    }

}
