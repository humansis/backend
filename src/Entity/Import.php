<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CreatedAt;
use Entity\Helper\CreatedBy;
use Entity\Helper\CountryDependent;
use Entity\Helper\EnumTrait;
use Entity\Helper\StandardizedPrimaryKey;
use Enum\ImportState;
use Entity\Project;
use Entity\User;
use Stringable;

#[ORM\Entity(repositoryClass: 'Repository\ImportRepository')]
#[ORM\HasLifecycleCallbacks]
class Import implements Stringable
{
    use StandardizedPrimaryKey;
    use CreatedBy;
    use CreatedAt;
    use EnumTrait;
    use CountryDependent;

    /**
     * @var string
     */
    #[ORM\Column(name: 'title', type: 'string', nullable: false)]
    private string $title;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'notes', type: 'string', nullable: true)]
    private string|null $notes;

    /**
     * @var Project[]|Collection
     */
    #[ORM\JoinTable(name: 'import_project')]
    #[ORM\JoinColumn(name: 'import_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'project_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: 'Entity\Project', cascade: ['persist'])]
    private array| Collection $projects;

    #[ORM\Column(name: 'state', type: 'enum_import_state', nullable: false)]
    private string $state;

    /**
     * @var ImportQueue[]|Collection
     */
    #[ORM\OneToMany(mappedBy: 'import', targetEntity: 'Entity\ImportQueue', cascade: ['remove'])]
    private array| Collection $importQueue;

    /**
     * @var ImportFile[]|Collection
     */
    #[ORM\OneToMany(mappedBy: 'import', targetEntity: 'Entity\ImportFile', cascade: ['persist', 'remove'])]
    private array| Collection $importFiles;

    /**
     * @var ImportBeneficiary[]|Collection
     */
    #[ORM\OneToMany(mappedBy: 'import', targetEntity: 'Entity\ImportBeneficiary', cascade: ['persist', 'remove'])]
    private array| Collection $importBeneficiaries;

    /**
     * @var ImportInvalidFile[]|Collection
     */
    #[ORM\OneToMany(mappedBy: 'import', targetEntity: 'Entity\ImportInvalidFile', cascade: ['remove'])]
    private array| Collection $importInvalidFiles;

    public function __construct(
        string $countryIso3,
        string $title,
        ?string $notes,
        array $projects,
        User $creator,
    ) {
        $this->countryIso3 = $countryIso3;
        $this->title = $title;
        $this->notes = $notes;
        $this->projects = new ArrayCollection($projects);
        $this->state = ImportState::NEW;
        $this->createdBy = $creator;
        $this->importQueue = new ArrayCollection();
        $this->importFiles = new ArrayCollection();
        $this->importBeneficiaries = new ArrayCollection();
        $this->importInvalidFiles = new ArrayCollection();
    }

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
     * @return Project[]|Collection
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function removeProject(Project $project): void
    {
        $this->projects->removeElement($project);
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
     * @see ImportState::values()
     */
    public function setState(string $state)
    {
        self::validateValue('state', ImportState::class, $state, false);
        $this->state = $state;
    }

    /**
     * @return Collection|ImportQueue[]
     */
    public function getImportQueue(): Collection |array
    {
        return $this->importQueue;
    }

    public function __toString(): string
    {
        return (string) "Import#{$this->getId()} ({$this->getTitle()})";
    }

    /**
     * @return Collection|ImportFile[]
     */
    public function getImportFiles(): Collection |array
    {
        return $this->importFiles;
    }

    /**
     * @return Collection|ImportBeneficiary[]
     */
    public function getImportBeneficiaries(): Collection |array
    {
        return $this->importBeneficiaries;
    }

    /**
     * @return Collection|ImportInvalidFile[]
     */
    public function getImportInvalidFiles(): Collection |array
    {
        return $this->importInvalidFiles;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
