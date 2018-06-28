<?php

namespace ReportingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ReportingProject
 *
 * @ORM\Table(name="reporting_project")
 * @ORM\Entity(repositoryClass="ReportingBundle\Repository\ReportingProjectRepository")
 */
class ReportingProject
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
