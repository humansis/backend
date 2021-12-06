<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\CreatedAt;
use NewApiBundle\Entity\Helper\CreatedBy;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use NewApiBundle\Component\Import\Enum\State;
use ProjectBundle\Entity\Project;
use UserBundle\Entity\User;

/**
 * @ORM\Table(name="import")
 * @ORM\Entity(repositoryClass="NewApiBundle\Component\Import\Repository\ImportRepository")
 */
class Import
{
    use StandardizedPrimaryKey;
    use CreatedBy;
    use CreatedAt;

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
     * @var Queue[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Component\Import\Entity\Queue", mappedBy="import", cascade={"remove"})
     */
    private $importQueue;

    /**
     * @var File[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Component\Import\Entity\File", mappedBy="import", cascade={"remove"})
     */
    private $importFiles;

    /**
     * @var Beneficiary[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Component\Import\Entity\Beneficiary", mappedBy="import", cascade={"remove"})
     */
    private $importBeneficiaries;

    /**
     * @var InvalidFile[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Component\Import\Entity\InvalidFile", mappedBy="import", cascade={"remove"})
     */
    private $importInvalidFiles;

    public function __construct(string $title, ?string $notes, Project $project, User $creator)
    {
        $this->title = $title;
        $this->notes = $notes;
        $this->project = $project;
        $this->state = State::NEW;
        $this->createdBy = $creator;
        $this->createdAt = new \DateTime('now');
        $this->importQueue = new ArrayCollection();
        $this->importFiles = new ArrayCollection();
        $this->importBeneficiaries = new ArrayCollection();
        $this->importInvalidFiles = new ArrayCollection();
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
     * @return string one of State::* values
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state one of State::* values
     */
    public function setState(string $state)
    {
        if (!in_array($state, State::values())) {
            throw new \InvalidArgumentException('Invalid argument. '.$state.' is not valid Import state');
        }

        $this->state = $state;
    }

    /**
     * @return Collection|Queue[]
     */
    public function getQueue()
    {
        return $this->importQueue;
    }

    public function __toString()
    {
        return "Import#{$this->getId()} ({$this->getTitle()})";
    }

    /**
     * @return Collection|File[]
     */
    public function getFiles()
    {
        return $this->importFiles;
    }

    /**
     * @return Collection|Beneficiary[]
     */
    public function getImportBeneficiaries()
    {
        return $this->importBeneficiaries;
    }

    /**
     * @return Collection|InvalidFile[]
     */
    public function getInvalidFiles()
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

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
