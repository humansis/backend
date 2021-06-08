<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Enum\ImportState;
use ProjectBundle\Entity\Project;
use UserBundle\Entity\User;

/**
 * @ORM\Entity()
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\ImportRepository")
 */
class Import
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", nullable=false)
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="string", nullable=true)
     */
    private $notes;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="ProjectBundle\Entity\Project")
     */
    private $project;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="enum_import_state", nullable=false)
     */
    private $state;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User", inversedBy="imports")
     */
    private $createdBy;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="created_at", type="datetimetz", nullable=false)
     */
    private $createdAt;

    /**
     * @var ImportQueue[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\ImportQueue", mappedBy="import", cascade={"remove"})
     */
    private $importQueue;

    /**
     * @var ImportFile[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\ImportFile", mappedBy="import", cascade={"remove"})
     */
    private $importFiles;

    /**
     * @var ImportBeneficiary[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\ImportBeneficiary", mappedBy="import", cascade={"remove"})
     */
    private $importBeneficiaries;

    /**
     * @var ImportInvalidFile[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\ImportInvalidFile", mappedBy="import", cascade={"remove"})
     */
    private $importInvalidFiles;

    public function __construct(string $title, ?string $notes, Project $project, User $creator)
    {
        $this->title = $title;
        $this->notes = $notes;
        $this->project = $project;
        $this->state = ImportState::NEW;
        $this->createdBy = $creator;
        $this->createdAt = new \DateTime('now');
        $this->importQueue = new ArrayCollection();
        $this->importFiles = new ArrayCollection();
        $this->importBeneficiaries = new ArrayCollection();
        $this->importInvalidFiles = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return User
     */
    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    /**
     * @return string one of ImportState::* values
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state one of ImportState::* values
     */
    public function setState(string $state)
    {
        if (!in_array($state, ImportState::values())) {
            throw new \InvalidArgumentException('Invalid argument. '.$state.' is not valid Import state');
        }

        $this->state = $state;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @return Collection|ImportQueue[]
     */
    public function getImportQueue()
    {
        return $this->importQueue;
    }

    public function __toString()
    {
        return "Import#{$this->getId()} ({$this->getTitle()})";
    }

    /**
     * @return Collection|ImportFile[]
     */
    public function getImportFiles()
    {
        return $this->importFiles;
    }

    /**
     * @return Collection|ImportBeneficiary[]
     */
    public function getImportBeneficiaries()
    {
        return $this->importBeneficiaries;
    }

    /**
     * @return Collection|ImportInvalidFile[]
     */
    public function getImportInvalidFiles()
    {
        return $this->importInvalidFiles;
    }

    /**
     * @param string|null $notes
     */
    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }
}
