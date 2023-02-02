<?php

namespace Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CreatedAt;
use Entity\Helper\LastModifiedAt;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * Donor
 */
#[ORM\Table(name: 'donor')]
#[ORM\Entity(repositoryClass: 'Repository\DonorRepository')]
#[ORM\HasLifecycleCallbacks]
class Donor implements ExportableInterface
{
    use CreatedAt;
    use LastModifiedAt;
    use StandardizedPrimaryKey;

    #[ORM\Column(name: 'fullname', type: 'string', length: 255)]
    private ?string $fullname = null;

    #[ORM\Column(name: 'shortname', type: 'string', length: 255)]
    private ?string $shortname = null;

    #[ORM\Column(name: 'dateAdded', type: 'datetime')]
    private ?\DateTime $dateAdded = null;

    #[ORM\Column(name: 'notes', type: 'string', length: 255, nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToMany(targetEntity: 'Entity\Project', mappedBy: 'donors')]
    private $projects;

    #[ORM\Column(name: 'logo', type: 'string', length: 255, nullable: true)]
    private ?string $logo = null;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
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
