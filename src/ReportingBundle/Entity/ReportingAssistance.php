<?php

namespace ReportingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;

/**
 * ReportingAssistance
 *
 * @ORM\Table(name="reporting_distribution")
 * @ORM\Entity(repositoryClass="ReportingBundle\Repository\ReportingAssistanceRepository")
 */
class ReportingAssistance extends AbstractEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\Assistance", inversedBy="reportingDistribution")
     * @ORM\JoinColumn(name="assistance_id", nullable=true)
     **/
    private $distribution;

    /**
     * @ORM\ManyToOne(targetEntity="ReportingBundle\Entity\ReportingIndicator", inversedBy="reportingDistribution")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $indicator;

    /**
     * @ORM\ManyToOne(targetEntity="ReportingBundle\Entity\ReportingValue", inversedBy="reportingDistribution")
     * @ORM\JoinColumn(nullable=false)
     **/
    private $value;


    /**
     * Set indicator
     *
     * @param \ReportingBundle\Entity\ReportingIndicator $indicator
     * @return reportingDistribution
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
     * @return reportingDistribution
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
     * Set distribution
     *
     * @param \DistributionBundle\Entity\Assistance $distribution
     * @return ReportingAssistance
     */
    public function setDistribution($distribution)
    {
        $this->distribution = $distribution;

        return $this;
    }

    /**
     * Get distribution
     *
     * @return \DistributionBundle\Entity\Assistance
     */
    public function getDistribution()
    {
        return $this->distribution;
    }
}
