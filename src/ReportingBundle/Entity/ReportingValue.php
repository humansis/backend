<?php

namespace ReportingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;

/**
 * ReportingValue
 *
 * @ORM\Table(name="reporting_value")
 * @ORM\Entity(repositoryClass="ReportingBundle\Repository\ReportingValueRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ReportingValue extends AbstractEntity
{
    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="unity", type="string", length=255)
     */
    private $unity;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creationDate", type="datetime")
     */
    private $creationDate;

    /**
     * @ORM\OneToMany(targetEntity="ReportingBundle\Entity\ReportingCountry", mappedBy="value", cascade={"persist"})
     **/
    private $reportingCountry;

    /**
     * @ORM\OneToMany(targetEntity="ReportingBundle\Entity\ReportingProject", mappedBy="value", cascade={"persist"})
     **/
    private $reportingProject;

    /**
     * @ORM\OneToMany(targetEntity="ReportingBundle\Entity\ReportingAssistance", mappedBy="value", cascade={"persist"})
     **/
    private $reportingDistribution;


    /**
     * Set value.
     *
     * @param string $value
     *
     * @return ReportingValue
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set unity.
     *
     * @param string $unity
     *
     * @return ReportingValue
     */
    public function setUnity($unity)
    {
        $this->unity = $unity;

        return $this;
    }

    /**
     * Get unity.
     *
     * @return string
     */
    public function getUnity()
    {
        return $this->unity;
    }

    /**
     * Set creationDate.
     *
     * @param \DateTime $creationDate
     *
     * @return ReportingValue
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }


    /**
     * Set reportingCountry
     *
     * @param string $reportingCountry
     * @return ReportingValue
     */
    public function setReportingCountry($reportingCountry)
    {
        $this->reportingCountry= $reportingCountry;

        return $this;
    }

    /**
     * Get reportingCountry
     *
     * @return string
     */
    public function getReportingCountry()
    {
        return $this->reportingCountry;
    }

    /**
     * Set reportingProject
     *
     * @param string $reportingProject
     * @return ReportingValue
     */
    public function setReportingProject($reportingProject)
    {
        $this->reportingProject= $reportingProject;

        return $this;
    }

    /**
     * Get reportingProject
     *
     * @return string
     */
    public function getReportingProject()
    {
        return $this->reportingProject;
    }

    /**
     * Set reportingDistribution
     *
     * @param string $reportingDistribution
     * @return ReportingValue
     */
    public function setReportingAssistance($reportingDistribution)
    {
        $this->reportingDistribution= $reportingDistribution;

        return $this;
    }

    /**
     * Get reportingDistribution
     *
     * @return string
     */
    public function getReportingAssistance()
    {
        return $this->reportingDistribution;
    }
}
