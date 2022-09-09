<?php

namespace ReportingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use  ReportingBundle\Utils\Model\IndicatorInterface;

/**
 * ReportingIndicator
 *
 * @ORM\Table(name="reporting_indicator")
 * @ORM\Entity(repositoryClass="ReportingBundle\Repository\ReportingIndicatorRepository")
 */
class ReportingIndicator implements IndicatorInterface
{
    use StandardizedPrimaryKey;

    /**
     * @var string
     *
     * @ORM\Column(name="reference", type="string", length=255)
     */
    private $reference;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     */
    private $code;

    /**
     * @var array|null
     *
     * @ORM\Column(name="filters", type="simple_array", nullable=true)
     */
    private $filters;

    /**
     * @var string|null
     *
     * @ORM\Column(name="graph", type="string", length=255, nullable=true)
     */
    private $graph;

    /**
     * @ORM\OneToMany(targetEntity="ReportingBundle\Entity\ReportingCountry", mappedBy="indicator", cascade={"persist"})
     **/
    private $reportingCountry;

    /**
     * @ORM\OneToMany(targetEntity="ReportingBundle\Entity\ReportingProject", mappedBy="indicator", cascade={"persist"})
     **/
    private $reportingProject;

    /**
     * @ORM\OneToMany(targetEntity="ReportingBundle\Entity\ReportingAssistance", mappedBy="indicator", cascade={"persist"})
     **/
    private $reportingDistribution;


    /**
     * Set reference.
     *
     * @param string $reference
     *
     * @return ReportingIndicator
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get reference.
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return ReportingIndicator
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set filters.
     *
     * @param array|null $filters
     *
     * @return ReportingIndicator
     */
    public function setFilters($filters = null)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Get filters.
     *
     * @return array|null
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Set graph.
     *
     * @param string|null $graph
     *
     * @return ReportingIndicator
     */
    public function setGraph($graph = null)
    {
        $this->graph = $graph;

        return $this;
    }

    /**
     * Get graph.
     *
     * @return string|null
     */
    public function getGraph()
    {
        return $this->graph;
    }

    /**
     * Set reportingCountry
     *
     * @param string $reportingCountry
     * @return ReportingReference
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
     * @return ReportingReference
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
     * @return ReportingReference
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
