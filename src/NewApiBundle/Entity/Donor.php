<?php

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use NewApiBundle\Entity\Helper\CreatedAt;
use NewApiBundle\Entity\Helper\LastModifiedAt;
use CommonBundle\Utils\ExportableInterface;

/**
 * Donor
 *
 * @ORM\Table(name="donor")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\DonorRepository")
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
     * @var string
     *
     * @ORM\Column(name="fullname", type="string", length=255)
     *
     */
    private $fullname;

    /**
     * @var string
     *
     * @ORM\Column(name="shortname", type="string", length=255, nullable=true)
     *
     */
    private $shortname;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateAdded", type="datetime")
     *
     */
    private $dateAdded;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="string", length=255, nullable=true)
     *
     */
    private $notes;

    /**
     * @ORM\ManyToMany(targetEntity="NewApiBundle\Entity\Project", mappedBy="donors")
     *
     */
    private $projects;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", length=255, nullable=true)
     */
    private $logo;


    /**
     * Set id.
     *
     * @param $id
     * @return $this
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
     * Set fullname.
     *
     * @param string $fullname
     *
     * @return Donor
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Get fullname.
     *
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set shortname.
     *
     * @param string $shortname
     *
     * @return Donor
     */
    public function setShortname($shortname)
    {
        $this->shortname = $shortname;

        return $this;
    }

    /**
     * Get shortname.
     *
     * @return string
     */
    public function getShortname()
    {
        return $this->shortname;
    }

    /**
     * Set dateAdded.
     *
     * @param \DateTime $dateAdded
     *
     * @return Donor
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded.
     *
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set notes.
     *
     * @param string $notes
     *
     * @return Donor
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes.
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->projects = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add project.
     *
     * @param \NewApiBundle\Entity\Project $project
     *
     * @return Donor
     */
    public function addProject(\NewApiBundle\Entity\Project $project)
    {
        $this->projects[] = $project;

        return $this;
    }

    /**
     * Remove project.
     *
     * @param \NewApiBundle\Entity\Project $project
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeProject(\NewApiBundle\Entity\Project $project)
    {
        return $this->projects->removeElement($project);
    }

    /**
     * Get projects.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
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
            "Short name"=> $this->getShortname(),
            "Date added" => $this->getDateAdded()->format('d-m-Y H:i:s'),
            "Notes" => $this->getNotes(),
            "Project" => $project,
        ];
    }

     /**
     * Set logo.
     *
     * @param string|null $logo
     *
     * @return Donor
     */
    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo.
     *
     * @return string|null
     */
    public function getLogo(): ?string
    {
        return $this->logo;
    }
}