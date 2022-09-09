<?php

namespace ReportingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;

/**
 * ReportingProject
 *
 * @ORM\Table(name="reporting_project")
 * @ORM\Entity(repositoryClass="ReportingBundle\Repository\ReportingProjectRepository")
 */
class ReportingProject
{
    use StandardizedPrimaryKey;


    /**
     *@ORM\ManyToOne(targetEntity="ProjectBundle\Entity\Project", inversedBy="reportingProject")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $project;

    /**
     *@ORM\ManyToOne(targetEntity="ReportingBundle\Entity\ReportingIndicator", inversedBy="reportingProject")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $indicator;

    /**
     * @ORM\ManyToOne(targetEntity="ReportingBundle\Entity\ReportingValue", inversedBy="reportingProject")
     * @ORM\JoinColumn(nullable=false)
     **/
    private $value;


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
     * Set indicator
     *
     * @param \ReportingBundle\Entity\ReportingIndicator $indicator
     * @return ReportingProject
     */
    public function setIndicator(\ReportingBundle\Entity\ReportingIndicator $indicator)
    {
        $this->indicator = $indicator;

        return $this;
    }

    /**
     * Get indicator
     *
     * @return \ReportingBundle\Entity\ReportingIndicator
     */
    public function getIndicator()
    {
        return $this->indicator;
    }

    /**
     * Set value
     *
     * @param \ReportingBundle\Entity\ReportingValue $value
     * @return ReportingProject
     */
    public function setValue(\ReportingBundle\Entity\ReportingValue $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return \ReportingBundle\Entity\ReportingValue
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set reportingProject
     *
     * @param \ProjectBundle\Entity\Project $project
     * @return ReportingProject
     */
    public function setProject($project)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project
     *
     * @return \ProjectBundle\Entity\Project
     */
    public function getProject()
    {
        return $this->project;
    }
}
