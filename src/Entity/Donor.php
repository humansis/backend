<?php

namespace Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CreatedAt;
use Entity\Helper\LastModifiedAt;
use Utils\ExportableInterface;

/**
 * Donor
 *
 * @ORM\Table(name="donor")
 * @ORM\Entity(repositoryClass="Repository\DonorRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Donor implements ExportableInterface
{
    use CreatedAt;
    use LastModifiedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     */
    private $id;

    /**
     *
     * @ORM\Column(name="fullname", type="string", length=255)
     *
     */
    private ?string $fullname = null;

    /**
     *
     * @ORM\Column(name="shortname", type="string", length=255)
     *
     */
    private ?string $shortname = null;

    /**
     *
     * @ORM\Column(name="dateAdded", type="datetime")
     *
     */
    private ?\DateTime $dateAdded = null;

    /**
     *
     * @ORM\Column(name="notes", type="string", length=255, nullable=true)
     *
     */
    private ?string $notes = null;

    /**
     * @ORM\ManyToMany(targetEntity="Entity\Project", mappedBy="donors")
     *
     */
    private $projects;

    /**
     * @ORM\Column(name="logo", type="string", length=255, nullable=true)
     */
    private ?string $logo = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->projects = new ArrayCollection();
    }

    /**
     * Set id.
     *
     * @param $id
     */
    public function setId($id): Donor
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set fullname.
     *
     *
     */
    public function setFullname(string $fullname): Donor
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Get fullname.
     */
    public function getFullname(): string
    {
        return $this->fullname;
    }

    /**
     * Set shortname.
     *
     *
     */
    public function setShortname(string $shortname): Donor
    {
        $this->shortname = $shortname;

        return $this;
    }

    /**
     * Get shortname.
     */
    public function getShortname(): string
    {
        return $this->shortname;
    }

    /**
     * Set dateAdded.
     *
     *
     */
    public function setDateAdded(DateTime $dateAdded): Donor
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded.
     */
    public function getDateAdded(): DateTime
    {
        return $this->dateAdded;
    }

    /**
     * Set notes.
     *
     *
     */
    public function setNotes(?string $notes): Donor
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
     * Add project.
     *
     *
     */
    public function addProject(Project $project): Donor
    {
        $this->projects[] = $project;

        return $this;
    }

    /**
     * Remove project.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeProject(Project $project): bool
    {
        return $this->projects->removeElement($project);
    }

    /**
     * Get projects.
     *
     * @return Collection
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     */
    public function getMappedValueForExport(): array
    {
        // Recover projects of the donor
        $project = [];
        foreach ($this->getProjects()->getValues() as $value) {
            array_push($project, $value->getName());
        }
        $project = join(',', $project);

        return [
            "Full name" => $this->getFullName(),
            "Short name" => $this->getShortname(),
            "Date added" => $this->getDateAdded()->format('d-m-Y H:i:s'),
            "Notes" => $this->getNotes(),
            "Project" => $project,
        ];
    }

    /**
     * Set logo.
     *
     *
     */
    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo.
     */
    public function getLogo(): ?string
    {
        return $this->logo;
    }
}
