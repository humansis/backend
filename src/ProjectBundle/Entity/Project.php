<?php

namespace ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Type as JMS_Type;
use JMS\Serializer\Annotation\Groups;

/**
 * Project
 *
 * @ORM\Table(name="project")
 * @ORM\Entity(repositoryClass="ProjectBundle\Repository\ProjectRepository")
 */
class Project
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"FullProject"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @Groups({"FullProject"})
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startDate", type="date")
     * @JMS_Type("DateTime<'Y-m-d'>")
     *
     * @Groups({"FullProject"})
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endDate", type="date")
     * @JMS_Type("DateTime<'Y-m-d'>")
     *
     * @Groups({"FullProject"})
     */
    private $endDate;

    /**
     * @var int
     *
     * @ORM\Column(name="numberOfHouseholds", type="integer")
     *
     * @Groups({"FullProject"})
     */
    private $numberOfHouseholds;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float")
     *
     * @Groups({"FullProject"})
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     *
     * @Groups({"FullProject"})
     */
    private $notes;

    /**
     * @var string
     *
     * @ORM\Column(name="iso3", type="text")
     *
     * @Groups({"FullProject"})
     */
    private $iso3;

    /**
     * @ORM\ManyToMany(targetEntity="ProjectBundle\Entity\Donor", inversedBy="projects")
     *
     * @Groups({"FullProject"})
     */
    private $donors;

    /**
     * @ORM\ManyToMany(targetEntity="ProjectBundle\Entity\Sector", inversedBy="projects")
     *
     * @Groups({"FullProject"})
     */
    private $sectors;

    /**
     * @ORM\OneToMany(targetEntity="UserBundle\Entity\UserProject", mappedBy="project")
     */
    private $usersProject;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->usersProject = new \Doctrine\Common\Collections\ArrayCollection();
        $this->donors = new \Doctrine\Common\Collections\ArrayCollection();
        $this->sectors = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set id.
     *
     * @return Project
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
     * Set name.
     *
     * @param string $name
     *
     * @return Project
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
     * Set startDate.
     *
     * @param \DateTime $startDate
     *
     * @return Project
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate.
     *
     * @param \DateTime $endDate
     *
     * @return Project
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate.
     *
     * @return \DateTime
     */
    public function getEndDate()
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
    public function setNumberOfHouseholds($numberOfHouseholds)
    {
        $this->numberOfHouseholds = $numberOfHouseholds;

        return $this;
    }

    /**
     * Get numberOfHouseholds.
     *
     * @return int
     */
    public function getNumberOfHouseholds()
    {
        return $this->numberOfHouseholds;
    }

    /**
     * Set value.
     *
     * @param float $value
     *
     * @return Project
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set notes.
     *
     * @param string|null $notes
     *
     * @return Project
     */
    public function setNotes($notes = null)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes.
     *
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set iso3.
     *
     * @param string $iso3
     *
     * @return Project
     */
    public function setIso3($iso3)
    {
        $this->iso3 = $iso3;

        return $this;
    }

    /**
     * Get iso3.
     *
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    /**
     * Add usersProject.
     *
     * @param \UserBundle\Entity\UserProject $usersProject
     *
     * @return Project
     */
    public function addUsersProject(\UserBundle\Entity\UserProject $usersProject)
    {
        $this->usersProject[] = $usersProject;

        return $this;
    }

    /**
     * Remove usersProject.
     *
     * @param \UserBundle\Entity\UserProject $usersProject
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeUsersProject(\UserBundle\Entity\UserProject $usersProject)
    {
        return $this->usersProject->removeElement($usersProject);
    }

    /**
     * Get usersProject.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsersProject()
    {
        return $this->usersProject;
    }

    /**
     * Add donor.
     *
     * @param \ProjectBundle\Entity\Donor $donor
     *
     * @return Project
     */
    public function addDonor(\ProjectBundle\Entity\Donor $donor)
    {
        $this->donors[] = $donor;

        return $this;
    }

    /**
     * Remove donor.
     *
     * @param \ProjectBundle\Entity\Donor $donor
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeDonor(\ProjectBundle\Entity\Donor $donor)
    {
        return $this->donors->removeElement($donor);
    }

    /**
     * Get donors.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDonors()
    {
        return $this->donors;
    }

    /**
     * Clean donors.
     *
     * @return $this
     */
    public function cleanDonors()
    {
        $this->donors = new \Doctrine\Common\Collections\ArrayCollection();

        return $this;
    }

    /**
     * Add sector.
     *
     * @param \ProjectBundle\Entity\Sector $sector
     *
     * @return Project
     */
    public function addSector(\ProjectBundle\Entity\Sector $sector)
    {
        $this->sectors[] = $sector;

        return $this;
    }

    /**
     * Remove sector.
     *
     * @param \ProjectBundle\Entity\Sector $sector
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeSector(\ProjectBundle\Entity\Sector $sector)
    {
        return $this->sectors->removeElement($sector);
    }

    /**
     * Get sectors.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSectors()
    {
        return $this->sectors;
    }

    /**
     * Clean sectors.
     *
     * @return $this
     */
    public function cleanSectors()
    {
        $this->sectors = new \Doctrine\Common\Collections\ArrayCollection();

        return $this;
    }
}
