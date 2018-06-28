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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
