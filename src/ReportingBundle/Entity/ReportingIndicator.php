<?php

namespace ReportingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use  \ReportingBundle\Model\IndicatorInterface;

/**
 * ReportingIndicator
 *
 * @ORM\Table(name="reporting_indicator")
 * @ORM\Entity(repositoryClass="ReportingBundle\Repository\ReportingIndicatorRepository")
 */
class ReportingIndicator implements IndicatorInterface
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
     * @ORM\Column(name="filtres", type="simple_array", nullable=true)
     */
    private $filtres;

    /**
     * @var string|null
     *
     * @ORM\Column(name="graphique", type="string", length=255, nullable=true)
     */
    private $graphique;


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
     * Set filtres.
     *
     * @param array|null $filtres
     *
     * @return ReportingIndicator
     */
    public function setFiltres($filtres = null)
    {
        $this->filtres = $filtres;

        return $this;
    }

    /**
     * Get filtres.
     *
     * @return array|null
     */
    public function getFiltres()
    {
        return $this->filtres;
    }

    /**
     * Set graphique.
     *
     * @param string|null $graphique
     *
     * @return ReportingIndicator
     */
    public function setGraphique($graphique = null)
    {
        $this->graphique = $graphique;

        return $this;
    }

    /**
     * Get graphique.
     *
     * @return string|null
     */
    public function getGraphique()
    {
        return $this->graphique;
    }
}
