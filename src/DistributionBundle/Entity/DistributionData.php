<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query\Expr\Select;
use ProjectBundle\Entity\Project;
use JMS\Serializer\Annotation\Type as JMS_Type;
use JMS\Serializer\Annotation\Groups;

/**
 * DistributionData
 *
 * @ORM\Table(name="distribution_data")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\DistributionDataRepository")
 */
class DistributionData
{

    const TYPE_BENEFICIARY = 0;
    const TYPE_HOUSEHOLD = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"FullDistribution"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45)
     *
     * @Groups({"FullDistribution"})
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="UpdatedOn", type="datetime")
     * @JMS_Type("DateTime<'Y-m-d H:m:i'>")
     *
     * @Groups({"FullDistribution"})
     */
    private $updatedOn;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\Location")
     *
     * @Groups({"FullDistribution"})
     */
    private $location;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="ProjectBundle\Entity\Project", inversedBy="distributions")
     * 
     * @Groups({"FullDistribution"})
     */
    private $project;

    /**
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\SelectionCriteria", cascade={"persist"})
     *
     * @Groups({"FullDistribution"})
     */
    private $selectionCriteria;

    /**
     * @var boolean
     *
     * @ORM\Column(name="archived", type="boolean", options={"default" : 0})
     *
     * @Groups({"FullDistribution"})
     */
    private $archived = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="validated", type="boolean", options={"default" : 0})
     *
     * @Groups({"FullDistribution"})
     */
    private $validated = 0;

    /**
     * @ORM\OneToMany(targetEntity="ReportingBundle\Entity\ReportingDistribution", mappedBy="distribution", cascade={"persist"})
     **/
    private $reportingDistribution;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="type_distribution")
     *
     * @Groups({"FullDistribution"})
     */
    private $type;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reportingDistribution = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setUpdatedOn(new \DateTime());
    }

    /**
     * Set id.
     *
     * @param $id
     * @return DistributionData
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
     * @return DistributionData
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
     * Set archived.
     *
     * @param bool $archived
     *
     * @return DistributionData
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
     * Set validated.
     *
     * @param bool $validated
     *
     * @return DistributionData
     */
    public function setValidated($validated)
    {
        $this->validated = $validated;

        return $this;
    }

    /**
     * Get validated.
     *
     * @return bool
     */
    public function getValidated()
    {
        return $this->validated;
    }

    /**
     * Set location.
     *
     * @param \DistributionBundle\Entity\Location|null $location
     *
     * @return DistributionData
     */
    public function setLocation(\DistributionBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return \DistributionBundle\Entity\Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set project.
     *
     * @param \ProjectBundle\Entity\Project|null $project
     *
     * @return DistributionData
     */
    public function setProject(\ProjectBundle\Entity\Project $project = null)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project.
     *
     * @return \ProjectBundle\Entity\Project|null
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set updatedOn.
     *
     * @param \DateTime $updatedOn
     *
     * @return DistributionData
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;
        return $this;
    }

    /**
     * Get updatedOn.
     *
     * @return \DateTime
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Get reportingDistribution
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReportingDistribution()
    {
        return $this->reportingDistribution;
    }

    /**
     * Add reportingDistribution.
     *
     * @param \ReportingBundle\Entity\ReportingDistribution $reportingDistribution
     *
     * @return DistributionData
     */
    public function addReportingDistribution(\ReportingBundle\Entity\ReportingDistribution $reportingDistribution)
    {
        $this->reportingDistribution[] = $reportingDistribution;

        return $this;
    }

    /**
     * Remove reportingDistribution.
     *
     * @param \ReportingBundle\Entity\ReportingDistribution $reportingDistribution
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeReportingDistribution(\ReportingBundle\Entity\ReportingDistribution $reportingDistribution)
    {
        return $this->reportingDistribution->removeElement($reportingDistribution);
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return DistributionData
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set selectionCriteria.
     *
     * @param \DistributionBundle\Entity\SelectionCriteria|null $selectionCriteria
     *
     * @return DistributionData
     */
    public function setSelectionCriteria(\DistributionBundle\Entity\SelectionCriteria $selectionCriteria = null)
    {
        $this->selectionCriteria = $selectionCriteria;

        return $this;
    }

    /**
     * Get selectionCriteria.
     *
     * @return \DistributionBundle\Entity\SelectionCriteria|null
     */
    public function getSelectionCriteria()
    {
        return $this->selectionCriteria;
    }
}
