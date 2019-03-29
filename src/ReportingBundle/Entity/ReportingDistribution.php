<?php

namespace ReportingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ReportingDistribution
 *
 * @ORM\Table(name="reporting_distribution")
 * @ORM\Entity(repositoryClass="ReportingBundle\Repository\ReportingDistributionRepository")
 */
class ReportingDistribution
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     *@ORM\ManyToOne(targetEntity="DistributionBundle\Entity\DistributionData", inversedBy="reportingDistribution")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $distribution;

    /**
     *@ORM\ManyToOne(targetEntity="ReportingBundle\Entity\ReportingIndicator", inversedBy="reportingDistribution")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $indicator;

    /**
     * @ORM\ManyToOne(targetEntity="ReportingBundle\Entity\ReportingValue", inversedBy="reportingDistribution")
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
     * @param \DistributionBundle\Entity\DistributionData $distribution
     * @return ReportingDistribution
     */
    public function setDistribution($distribution)
    {
        $this->distribution = $distribution;

        return $this;
    }

    /**
     * Get distribution
     *
     * @return \DistributionBundle\Entity\DistributionData
     */
    public function getDistribution()
    {
        return $this->distribution;
    }
}
