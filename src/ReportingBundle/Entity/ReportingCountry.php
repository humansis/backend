<?php

namespace ReportingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;

/**
 * ReportingCountry
 *
 * @ORM\Table(name="reporting_country")
 * @ORM\Entity(repositoryClass="ReportingBundle\Repository\ReportingCountryRepository")
 */
class ReportingCountry extends AbstractEntity
{
    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=255)
     */
    private $country;

    /**
     *@ORM\ManyToOne(targetEntity="ReportingBundle\Entity\ReportingIndicator", inversedBy="reportingCountry")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $indicator;

    /**
     * @ORM\ManyToOne(targetEntity="ReportingBundle\Entity\ReportingValue", inversedBy="reportingCountry")
     * @ORM\JoinColumn(nullable=false)
     **/
    private $value;


    /**
     * Set indicator
     *
     * @param \ReportingBundle\Entity\ReportingIndicator $indicator
     * @return ReportingCountry
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
     * @return ReportingCountry
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
     * Set country
     *
     * @param string $country
     * @return ReportingCountry
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }
}
