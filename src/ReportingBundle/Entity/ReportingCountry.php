<?php

namespace ReportingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ReportingCountry
 *
 * @ORM\Table(name="reporting_country")
 * @ORM\Entity(repositoryClass="ReportingBundle\Repository\ReportingCountryRepository")
 */
class ReportingCountry
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
