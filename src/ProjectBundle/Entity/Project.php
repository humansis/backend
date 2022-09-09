<?php

namespace ProjectBundle\Entity;

use BeneficiaryBundle\Entity\Beneficiary;
use DateTime;
use DateTimeInterface;
use DistributionBundle\Entity\Assistance;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\NonUniqueResultException;
use NewApiBundle\Entity\Helper\CountryDependent;
use NewApiBundle\Entity\Helper\CreatedAt;
use NewApiBundle\Entity\Helper\LastModifiedAt;
use NewApiBundle\Enum\ProductCategoryType;
use ProjectBundle\DTO\Sector;
use ReportingBundle\Entity\ReportingProject;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use CommonBundle\Utils\ExportableInterface;
use BeneficiaryBundle\Entity\Household;
use UserBundle\Entity\UserProject;

/**
 * Project
 *
 * @ORM\Table(name="project")
 * @ORM\Entity(repositoryClass="ProjectBundle\Repository\ProjectRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Project implements ExportableInterface
{
    use CreatedAt;
    use LastModifiedAt;
    use CountryDependent;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @SymfonyGroups({"FullProject", "FullDonor", "FullAssistance", "SmallAssistance", "FullHousehold", "SmallHousehold", "FullUser", "FullBooklet"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @SymfonyGroups({"FullProject", "FullDonor", "FullAssistance", "SmallAssistance", "FullHousehold", "SmallHousehold", "FullUser", "FullBooklet"})
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="internalId", type="string", length=255, nullable=true)
     *
     * @SymfonyGroups({"FullProject", "FullDonor", "FullAssistance", "SmallAssistance", "FullHousehold", "SmallHousehold", "FullUser"})
     */
    private $internalId;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="startDate", type="date")
     *
     * @SymfonyGroups({"FullProject"})
     */
    private $startDate;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="endDate", type="date")
     *
     * @SymfonyGroups({"FullProject"})
     */
    private $endDate;

    /**
     * @var int
     *
     * @SymfonyGroups({"FullProject"})
     */
    private $numberOfHouseholds;

    /**
     * @var float
     *
     * @ORM\Column(name="target", type="float", nullable=true)
     *
     * @SymfonyGroups({"FullProject"})
     */
    private $target;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     *
     * @SymfonyGroups({"FullProject"})
     */
    private $notes;

    /**
     * @ORM\ManyToMany(targetEntity="ProjectBundle\Entity\Donor", inversedBy="projects")
     *
     * @SymfonyGroups({"FullProject"})
     */
    private $donors;

    /**
     * @ORM\OneToMany(targetEntity="ProjectBundle\Entity\ProjectSector", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     *
     * @SymfonyGroups({"FullProject", "FullAssistance", "SmallAssistance"})
     */
    private $sectors;

    /**
     * @var boolean
     *
     * @ORM\Column(name="archived", type="boolean", options={"default" : 0})
     * @SymfonyGroups({"FullProject", "FullUser", "SmallHousehold", "FullHousehold"})
     */
    private $archived = 0;

    /**
     * @ORM\OneToMany(targetEntity="UserBundle\Entity\UserProject", mappedBy="project", cascade={"remove"})
     */
    private $usersProject;

    /**
    * @ORM\OneToMany(targetEntity="ReportingBundle\Entity\ReportingProject", mappedBy="project", cascade={"persist", "remove"})
    **/
    private $reportingProject;

    /**
     * @ORM\ManyToMany(targetEntity="BeneficiaryBundle\Entity\AbstractBeneficiary", mappedBy="projects")
     */
    private $households;

    /**
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\Assistance", mappedBy="project")
     * @SymfonyGroups({"FullProject"})
     */
    private $distributions;

    /**
     * @var string|null
     *
     * @ORM\Column(name="project_invoice_address_local", type="text", nullable=true, options={"default" : null})
     */
    private $projectInvoiceAddressLocal = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="project_invoice_address_english", type="text", nullable=true, options={"default" : null})
     */
    private $projectInvoiceAddressEnglish = null;

    /**
     * @var string[]
     *
     * @ORM\Column(name="allowed_product_category_types", type="array", nullable=false)
     */
    private $allowedProductCategoryTypes;

    /**
     * @var DateTimeInterface|null
     */
    private $lastModifiedAtIncludingBeneficiaries = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->usersProject = new ArrayCollection();
        $this->donors = new ArrayCollection();
        $this->sectors = new ArrayCollection();
        $this->households = new ArrayCollection();
        $this->distributions = new ArrayCollection();

        $this->allowedProductCategoryTypes = [];
    }

    /**
     * Set id.
     *
     * @return Project
     */
    public function setId($id): Project
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Project
     */
    public function setName(string $name): Project
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getInternalId(): ?string
    {
        return $this->internalId;
    }

    /**
     * @param string|null $internalId
     *
     * @return Project
     */
    public function setInternalId(?string $internalId): Project
    {
        $this->internalId = $internalId;

        return $this;
    }

    /**
     * Set startDate.
     *
     * @param DateTime $startDate
     *
     * @return Project
     */
    public function setStartDate(DateTime $startDate): Project
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return DateTime
     */
    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    /**
     * Set endDate.
     *
     * @param DateTime $endDate
     *
     * @return Project
     */
    public function setEndDate(DateTime $endDate): Project
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate.
     *
     * @return DateTime
     */
    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    /**
     * Set numberOfHouseholds.
     *
     * @param int $numberOfHouseholds
     *
     * @return Project
     */
    public function setNumberOfHouseholds(int $numberOfHouseholds): Project
    {
        $this->numberOfHouseholds = $numberOfHouseholds;

        return $this;
    }

    /**
     * Get numberOfHouseholds.
     *
     * @return int
     */
    public function getNumberOfHouseholds(): int
    {
        return $this->numberOfHouseholds;
    }

    /**
     * Set target.
     *
     * @param float $target
     *
     * @return Project
     */
    public function setTarget(float $target): Project
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target.
     *
     * @return float
     */
    public function getTarget(): float
    {
        return $this->target;
    }

    /**
     * Set notes.
     *
     * @param string|null $notes
     *
     * @return Project
     */
    public function setNotes(?string $notes = null): Project
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes.
     *
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Set archived.
     *
     * @param bool $archived
     *
     * @return Project
     */
    public function setArchived(bool $archived): Project
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
     * Add donor.
     *
     * @param Donor $donor
     *
     * @return Project
     */
    public function addDonor(Donor $donor): Project
    {
        $this->donors->add($donor);

        return $this;
    }

    /**
     * Remove donor.
     *
     * @param Donor $donor
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeDonor(Donor $donor): bool
    {
        return $this->donors->removeElement($donor);
    }

    /**
     * Remove donors.
     *
     * @return Project
     */
    public function removeDonors(): Project
    {
        $this->donors->clear();

        return $this;
    }

    /**
     * Get donors.
     *
     * @return Collection
     */
    public function getDonors()
    {
        return $this->donors;
    }

    /**
     * Add sector.
     *
     * @param string $sectorId
     *
     * @return Project
     */
    public function addSector(string $sectorId): Project
    {
        $this->sectors->add(new ProjectSector($sectorId, $this));

        return $this;
    }

    /**
     * @param Sector[] $sectorDTOs
     *
     * @return Project
     */
    public function setSectors(iterable $sectorIDs): self
    {
        $this->sectors->clear();

        foreach ($sectorIDs as $sectorID) {
            $this->addSector($sectorID);
        }

        return $this;
    }

    /**
     * Remove sector.
     *
     * @param Sector $sector
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeSector(Sector $sector): bool
    {
        return $this->sectors->removeElement($sector);
    }

    /**
     * Remove sectors.
     *
     * @return Project
     */
    public function removeSectors(): Project
    {
        $this->sectors->clear();

        return $this;
    }

    /**
     * Get sectors.
     *
     * @return Collection<ProjectSector>|ProjectSector[]
     */
    public function getSectors(): Collection
    {
        return $this->sectors;
    }

    /**
     * Add usersProject.
     *
     * @param UserProject $usersProject
     *
     * @return Project
     */
    public function addUsersProject(UserProject $usersProject): Project
    {
        $this->usersProject[] = $usersProject;
        return $this;
    }

    /**
     * Remove usersProject.
     *
     * @param UserProject $usersProject
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeUsersProject(UserProject $usersProject): bool
    {
        return $this->usersProject->removeElement($usersProject);
    }

    /**
     * Get usersProject.
     *
     * @return Collection
     */
    public function getUsersProject()
    {
        return $this->usersProject;
    }

    /**
     * Get reportingProject
     *
     * @return Collection<ReportingProject>
     */
    public function getReportingProject(): Collection
    {
        return $this->reportingProject;
    }

    /**
     * Add reportingProject.
     *
     * @param ReportingProject $reportingProject
     *
     * @return Project
     */
    public function addReportingProject(ReportingProject $reportingProject): Project
    {
        $this->reportingProject[] = $reportingProject;

        return $this;
    }

    /**
     * Remove reportingProject.
     *
     * @param ReportingProject $reportingProject
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeReportingProject(ReportingProject $reportingProject): bool
    {
        return $this->reportingProject->removeElement($reportingProject);
    }

    /**
     * Add household.
     *
     * @param Household $household
     *
     * @return Project
     */
    public function addHousehold(Household $household): Project
    {
        $this->households->add($household);
        return $this;
    }

    /**
     * Remove household.
     *
     * @param Household $household
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeHousehold(Household $household): bool
    {
        return $this->households->removeElement($household);
    }

    /**
     * Get households.
     *
     * @return Collection
     */
    public function getHouseholds()
    {
        return $this->households;
    }

    /**
     * Add distribution.
     *
     * @param Assistance $distribution
     *
     * @return Project
     */
    public function addDistribution(Assistance $distribution): Project
    {
        $this->distributions[] = $distribution;

        return $this;
    }

    /**
     * Remove distribution.
     *
     * @param Assistance $distribution
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeDistribution(Assistance $distribution): bool
    {
        return $this->distributions->removeElement($distribution);
    }

    /**
     * Get distributions.
     *
     * @return Collection
     */
    public function getDistributions(): Collection
    {
        return $this->distributions;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
     */
    public function getMappedValueForExport(): array
    {
        //  Recover all donors with the Donors object
        $donors = [];
        foreach ($this->getDonors()->getValues() as $value) {
            array_push($donors, $value->getFullname());
        }
        $donors = join(',', $donors);

        // Recover all sectors with the Sectors object
        $sectors = [];
        foreach ($this->getSectors()->getValues() as $value) {
            array_push($sectors, $value->getName());
        }
        $sectors = join(',', $sectors);

        return [
            "ID" => $this->getId(),
            "Project name" => $this->getName(),
            "Internal ID" => $this->getInternalId(),
            "Start date"=> $this->getStartDate()->format('d-m-Y'),
            "End date" => $this->getEndDate()->format('d-m-Y'),
            "Number of households" => $this->getNumberOfHouseholds(),
            "Total Target beneficiaries" => $this->getTarget(),
            "Notes" => $this->getNotes(),
            "Country" => $this->getCountryIso3(),
            "Donors" => $donors,
            "Sectors" => $sectors,
            "is archived" => $this->getArchived(),
        ];
    }

    /**
     * @ORM\PostLoad
     * @ORM\PostPersist
     * @ORM\PostUpdate
     */
    public function updateNumberOfHouseholds(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        /** @var Project $entity */
        $entity = $args->getObject();
        
        $this->setNumberOfHouseholds(intval($em->getRepository(Household::class)->countUnarchivedByProject($entity)));
    }

    /**
     * @ORM\PostPersist
     * @ORM\PostUpdate
     * @throws NonUniqueResultException
     */
    public function updateLastModifiedAtIncludingBeneficiaries(LifecycleEventArgs $args)
    {
        /** @var Project $project */
        $project = $args->getObject();
        $em = $args->getEntityManager();
        $lastModifiedBnf = $em->getRepository(Beneficiary::class)->getLastModifiedByProject($project);
        if ($lastModifiedBnf) {
            $totalLastModified = $lastModifiedBnf > $project->getLastModifiedAt() ? $lastModifiedBnf : $project->getLastModifiedAt();
        } else {
            $totalLastModified = $project->getLastModifiedAt();
        }
        $this->setLastModifiedAtIncludingBeneficiaries($totalLastModified);
    }

    /**
     * @return string|null
     */
    public function getProjectInvoiceAddressLocal(): ?string
    {
        return $this->projectInvoiceAddressLocal;
    }

    /**
     * @param string|null $projectInvoiceAddressLocal
     *
     * @return Project
     */
    public function setProjectInvoiceAddressLocal(?string $projectInvoiceAddressLocal): Project
    {
        $this->projectInvoiceAddressLocal = $projectInvoiceAddressLocal;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getProjectInvoiceAddressEnglish(): ?string
    {
        return $this->projectInvoiceAddressEnglish;
    }

    /**
     * @param string|null $projectInvoiceAddressEnglish
     *
     * @return Project
     */
    public function setProjectInvoiceAddressEnglish(?string $projectInvoiceAddressEnglish): Project
    {
        $this->projectInvoiceAddressEnglish = $projectInvoiceAddressEnglish;

        return $this;
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
    public function setAllowedProductCategoryTypes(array $allowedProductCategoryTypes): Project
    {
        foreach ($allowedProductCategoryTypes as $categoryType) {
            if (!in_array($categoryType, ProductCategoryType::values())) {
                throw new \InvalidArgumentException("$categoryType is not valid category type value. Allowed values: [" . implode(',', ProductCategoryType::values()) . ']');
            }
        }

        $this->allowedProductCategoryTypes = $allowedProductCategoryTypes;

        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function getLastModifiedAtIncludingBeneficiaries(): ?DateTimeInterface
    {
        return $this->lastModifiedAtIncludingBeneficiaries;
    }

    /**
     * @param DateTimeInterface $lastModifiedAtIncludingBeneficiaries
     */
    public function setLastModifiedAtIncludingBeneficiaries(DateTimeInterface $lastModifiedAtIncludingBeneficiaries): void
    {
        $this->lastModifiedAtIncludingBeneficiaries = $lastModifiedAtIncludingBeneficiaries;
    }

}
