<?php

namespace ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Type as JMS_Type;
use JMS\Serializer\Annotation\Groups;
use CommonBundle\Utils\ExportableInterface;

/**
 * Project
 *
 * @ORM\Table(name="project")
 * @ORM\Entity(repositoryClass="ProjectBundle\Repository\ProjectRepository")
 */
class Project implements ExportableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"FullProject", "FullDonor", "FullDistribution", "FullHousehold", "SmallHousehold", "FullUser"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @Groups({"FullProject", "FullDonor", "FullDistribution", "FullHousehold", "SmallHousehold", "FullUser"})
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
     * @Groups({"FullProject"})
     */
    private $numberOfHouseholds;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float", nullable=true)
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
     * @Groups({"FullProject", "FullUser"})
     */
    private $iso3;

    /**
     * @ORM\ManyToMany(targetEntity="ProjectBundle\Entity\Donor", inversedBy="projects")
     *
     * @Groups({"FullProject"})
     */
    private $donors;

    /**
     * @ORM\ManyToMany(targetEntity="ProjectBundle\Entity\Sector", inversedBy="projects", cascade={"persist"})
     *
     * @Groups({"FullProject", "FullDistribution"})
     */
    private $sectors;

    /**
     * @var boolean
     *
     * @ORM\Column(name="archived", type="boolean", options={"default" : 0})
     * @Groups({"FullProject"})
     */
    private $archived = 0;

    /**
     * @ORM\OneToMany(targetEntity="UserBundle\Entity\UserProject", mappedBy="project", cascade={"remove"})
     */
    private $usersProject;

     /**
     * @ORM\OneToMany(targetEntity="ReportingBundle\Entity\ReportingProject", mappedBy="project", cascade={"persist"})
     **/
    private $reportingProject;

    /**
     * @ORM\ManyToMany(targetEntity="BeneficiaryBundle\Entity\Household", mappedBy="projects")
     */
    private $households;

    /**
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\DistributionData", mappedBy="project")
     */
    private $distributions;


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
     * Set archived.
     *
     * @param bool $archived
     *
     * @return Project
     */
    public function setArchived($archived)
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
     * @param \ProjectBundle\Entity\Donor $donor
     *
     * @return Project
     */
    public function addDonor(\ProjectBundle\Entity\Donor $donor)
    {
        $this->donors->add($donor);

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
     * Remove donors.
     *
     * @return Project
     */
    public function removeDonors()
    {
        $this->donors->clear();

        return $this;
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
     * Add sector.
     *
     * @param \ProjectBundle\Entity\Sector $sector
     *
     * @return Project
     */
    public function addSector(\ProjectBundle\Entity\Sector $sector)
    {
        $this->sectors->add($sector);

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
     * Remove sectors.
     *
     * @return Project
     */
    public function removeSectors()
    {
        $this->sectors->clear();

        return $this;
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
     * Get reportingProject
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReportingProject()
    {
        return $this->reportingProject;
    }

    /**
     * Add reportingProject.
     *
     * @param \ReportingBundle\Entity\ReportingProject $reportingProject
     *
     * @return Project
     */
    public function addReportingProject(\ReportingBundle\Entity\ReportingProject $reportingProject)
    {
        $this->reportingProject[] = $reportingProject;

        return $this;
    }

    /**
     * Remove reportingProject.
     *
     * @param \ReportingBundle\Entity\ReportingProject $reportingProject
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeReportingProject(\ReportingBundle\Entity\ReportingProject $reportingProject)
    {
        return $this->reportingProject->removeElement($reportingProject);
    }

    /**
     * Add household.
     *
     * @param \BeneficiaryBundle\Entity\Household $household
     *
     * @return Project
     */
    public function addHousehold(\BeneficiaryBundle\Entity\Household $household)
    {
        $this->households[] = $household;
        return $this;
    }

    /**
     * Remove household.
     *
     * @param \BeneficiaryBundle\Entity\Household $household
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeHousehold(\BeneficiaryBundle\Entity\Household $household)
    {
        return $this->households->removeElement($household);
    }

    /**
     * Get households.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHouseholds()
    {
        return $this->households;
    }

    /**
     * Add distribution.
     *
     * @param \DistributionBundle\Entity\DistributionData $distribution
     *
     * @return Project
     */
    public function addDistribution(\DistributionBundle\Entity\DistributionData $distribution)
    {
        $this->distributions[] = $distribution;

        return $this;
    }

    /**
     * Remove distribution.
     *
     * @param \DistributionBundle\Entity\DistributionData $distribution
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeDistribution(\DistributionBundle\Entity\DistributionData $distribution)
    {
        return $this->distributions->removeElement($distribution);
    }

    /**
     * Get distributions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDistributions()
    {
        return $this->distributions;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
     */
    function getMappedValueForExport(): array
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
            "Project name" => $this->getName(),
            "Start date"=> $this->getStartDate()->format('Y-m-d'),
            "End date" => $this->getEndDate()->format('Y-m-d'),
            "Number of households" => $this->getNumberOfHouseholds(),
            "Value" => $this->getValue(),
            "Notes" => $this->getNotes(),
            "Country" => $this->getIso3(),
            "Donors" => $donors,
            "Sectors" => $sectors,
            "is archived" => $this->getArchived(),
        ];
    }
}
