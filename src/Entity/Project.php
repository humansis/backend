<?php

namespace Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\LifecycleEventArgs;
use DTO\Sector;
use Entity\Helper\CreatedAt;
use Entity\Helper\LastModifiedAt;
use Enum\ProductCategoryType;
use Exception\CountryMismatchException;
use InvalidArgumentException;
use Utils\ExportableInterface;
use Entity\Helper\CountryDependent;

/**
 * Project
 *
 * @ORM\Table(name="project")
 * @ORM\Entity(repositoryClass="Repository\ProjectRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Project implements ExportableInterface
{
    use CreatedAt;
    use LastModifiedAt;
    use CountryDependent;

    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     */
    private int $id;

    /**
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     */
    private ?string $name = null;

    /**
     *
     * @ORM\Column(name="internalId", type="string", length=255, nullable=true)
     *
     */
    private ?string $internalId = null;

    /**
     *
     * @ORM\Column(name="startDate", type="date")
     *
     */
    private ?\DateTime $startDate = null;

    /**
     *
     * @ORM\Column(name="endDate", type="date")
     *
     */
    private ?\DateTime $endDate = null;

    private ?int $numberOfHouseholds = null;

    /**
     *
     * @ORM\Column(name="target", type="float", nullable=true)
     *
     */
    private ?float $target = null;

    /**
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     *
     */
    private ?string $notes = null;

    /**
     * @ORM\ManyToMany(targetEntity="Entity\Donor", inversedBy="projects")
     *
     */
    private $donors;

    /**
     * @ORM\OneToMany(targetEntity="Entity\ProjectSector", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     *
     */
    private $sectors;

    /**
     * @ORM\Column(name="archived", type="boolean", options={"default" : 0})
     */
    private int|bool $archived = 0;

    /**
     * @ORM\OneToMany(targetEntity="Entity\UserProject", mappedBy="project", cascade={"remove"})
     */
    private $usersProject;

    /**
     * @ORM\ManyToMany(targetEntity="Entity\AbstractBeneficiary", mappedBy="projects")
     */
    private $households;

    /**
     * @ORM\OneToMany(targetEntity="Entity\Assistance", mappedBy="project")
     */
    private $distributions;

    /**
     * @ORM\Column(name="project_invoice_address_local", type="text", nullable=true, options={"default" : null})
     */
    private ?string $projectInvoiceAddressLocal = null;

    /**
     * @ORM\Column(name="project_invoice_address_english", type="text", nullable=true, options={"default" : null})
     */
    private ?string $projectInvoiceAddressEnglish = null;

    /**
     * @var string[]
     *
     * @ORM\Column(name="allowed_product_category_types", type="array", nullable=false)
     */
    private array $allowedProductCategoryTypes;

    private ?\DateTimeInterface $lastModifiedAtIncludingBeneficiaries = null;

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
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     *
     */
    public function setName(string $name): Project
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getInternalId(): ?string
    {
        return $this->internalId;
    }

    public function setInternalId(?string $internalId): Project
    {
        $this->internalId = $internalId;

        return $this;
    }

    /**
     * Set startDate.
     *
     *
     */
    public function setStartDate(DateTime $startDate): Project
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate.
     */
    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    /**
     * Set endDate.
     *
     *
     */
    public function setEndDate(DateTime $endDate): Project
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate.
     */
    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    /**
     * Set numberOfHouseholds.
     *
     *
     */
    public function setNumberOfHouseholds(int $numberOfHouseholds): Project
    {
        $this->numberOfHouseholds = $numberOfHouseholds;

        return $this;
    }

    /**
     * Get numberOfHouseholds.
     */
    public function getNumberOfHouseholds(): int
    {
        return $this->numberOfHouseholds;
    }

    /**
     * Set target.
     *
     *
     */
    public function setTarget(?float $target): Project
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target.
     *
     * @return float
     */
    public function getTarget(): ?float
    {
        return $this->target;
    }

    /**
     * Set notes.
     *
     * @param string|null $notes
     */
    public function setNotes(string $notes = null): Project
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes.
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Set archived.
     *
     *
     */
    public function setArchived(bool $archived): Project
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived.
     */
    public function getArchived(): bool
    {
        return $this->archived;
    }

    /**
     * Add donor.
     *
     *
     */
    public function addDonor(Donor $donor): Project
    {
        $this->donors->add($donor);

        return $this;
    }

    /**
     * Remove donor.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeDonor(Donor $donor): bool
    {
        return $this->donors->removeElement($donor);
    }

    /**
     * Remove donors.
     */
    public function removeDonors(): Project
    {
        $this->donors->clear();

        return $this;
    }

    /**
     * Get donors.
     *
     * @return Collection<Donor>
     */
    public function getDonors(): Collection
    {
        return $this->donors;
    }

    /**
     * Add sector.
     *
     *
     */
    public function addSector(string $sectorId): Project
    {
        $this->sectors->add(new ProjectSector($sectorId, $this));

        return $this;
    }

    /**
     * @param Sector[] $sectorDTOs
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
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeSector(Sector $sector): bool
    {
        return $this->sectors->removeElement($sector);
    }

    /**
     * Remove sectors.
     */
    public function removeSectors(): Project
    {
        $this->sectors->clear();

        return $this;
    }

    /**
     * Get sectors.
     *
     * @return Collection<ProjectSector>
     */
    public function getSectors(): Collection
    {
        return $this->sectors;
    }

    /**
     * Add usersProject.
     *
     *
     */
    public function addUsersProject(UserProject $usersProject): Project
    {
        $this->usersProject[] = $usersProject;

        return $this;
    }

    /**
     * Remove usersProject.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeUsersProject(UserProject $usersProject): bool
    {
        return $this->usersProject->removeElement($usersProject);
    }

    /**
     * Get usersProject.
     *
     * @return Collection<UserProject>
     */
    public function getUsersProject(): Collection
    {
        return $this->usersProject;
    }

    /**
     * Add household.
     *
     *
     */
    public function addHousehold(Household $household): Project
    {
        if ($household->getCountryIso3() !== $this->getCountryIso3()) {
            throw new CountryMismatchException();
        }
        $this->households->add($household);

        return $this;
    }

    /**
     * Remove household.
     *
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
     *
     */
    public function addDistribution(Assistance $distribution): Project
    {
        $this->distributions[] = $distribution;

        return $this;
    }

    /**
     * Remove distribution.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeDistribution(Assistance $distribution): bool
    {
        return $this->distributions->removeElement($distribution);
    }

    /**
     * Get distributions.
     */
    public function getDistributions(): Collection
    {
        return $this->distributions;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
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
            "Start date" => $this->getStartDate()->format('d-m-Y'),
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
            $totalLastModified = $lastModifiedBnf > $project->getLastModifiedAt(
            ) ? $lastModifiedBnf : $project->getLastModifiedAt();
        } else {
            $totalLastModified = $project->getLastModifiedAt();
        }
        $this->setLastModifiedAtIncludingBeneficiaries($totalLastModified);
    }

    public function getProjectInvoiceAddressLocal(): ?string
    {
        return $this->projectInvoiceAddressLocal;
    }

    public function setProjectInvoiceAddressLocal(?string $projectInvoiceAddressLocal): Project
    {
        $this->projectInvoiceAddressLocal = $projectInvoiceAddressLocal;

        return $this;
    }

    public function getProjectInvoiceAddressEnglish(): ?string
    {
        return $this->projectInvoiceAddressEnglish;
    }

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
                throw new InvalidArgumentException(
                    "$categoryType is not valid category type value. Allowed values: [" . implode(
                        ',',
                        ProductCategoryType::values()
                    ) . ']'
                );
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

    public function setLastModifiedAtIncludingBeneficiaries(
        DateTimeInterface $lastModifiedAtIncludingBeneficiaries
    ): void {
        $this->lastModifiedAtIncludingBeneficiaries = $lastModifiedAtIncludingBeneficiaries;
    }
}
