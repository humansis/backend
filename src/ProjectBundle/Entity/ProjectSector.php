<?php

namespace ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use ProjectBundle\DBAL\SectorEnum;
use ProjectBundle\DBAL\SubSectorEnum;

/**
 * Sector
 *
 * @ORM\Table(name="project_sector", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="uniq_sector_project", columns={"sector", "subsector", "project_id"})})
 * })
 * @ORM\Entity(repositoryClass="ProjectBundle\Repository\SectorRepository")
 */
class ProjectSector
{
    use StandardizedPrimaryKey;

    /**
     * @var string
     * @see SectorEnum
     *
     * @ORM\Column(name="sector", type="enum_sector", nullable=false)
     * @SymfonyGroups({"FullSector", "FullProject"})
     */
    private $sector;

    /**
     * @var string|null
     * @see SubSectorEnum
     *
     * @ORM\Column(name="subsector", type="enum_sub_sector", nullable=true)
     * @SymfonyGroups({"FullSector", "FullProject"})
     */
    private $subSector;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="ProjectBundle\Entity\Project", inversedBy="sectors")
     */
    private $project;

    /**
     * ProjectSector constructor.
     *
     * @param string      $sector
     * @param string|null $subSector
     * @param Project     $project
     */
    public function __construct(string $sector, Project $project, ?string $subSector = null)
    {
        $this->sector = $sector;
        $this->subSector = $subSector;
        $this->project = $project;
    }


    /**
     * @return string
     */
    public function getSector(): string
    {
        return $this->sector;
    }

    /**
     * @param string $sector
     */
    public function setSector(string $sector): void
    {
        $this->sector = $sector;
    }

    /**
     * backward compatibility
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getSector() . '-' . $this->getSubSector();
    }

    /**
     * @return string|null
     */
    public function getSubSector(): ?string
    {
        return $this->subSector;
    }

    /**
     * @param string|null $subSector
     */
    public function setSubSector(?string $subSector): void
    {
        $this->subSector = $subSector;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     */
    public function setProject(Project $project): void
    {
        $this->project = $project;
    }
}
