<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use DBAL\SectorEnum;
use DBAL\SubSectorEnum;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * Sector
 *
 * @ORM\Table(name="project_sector", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="uniq_sector_project", columns={"sector", "subsector", "project_id"})})
 * })
 * @ORM\Entity(repositoryClass="Repository\SectorRepository")
 */
class ProjectSector
{
    use StandardizedPrimaryKey;

    /**
     * @see SectorEnum
     *
     * @ORM\Column(name="sector", type="enum_sector", nullable=false)
     */
    private string $sector;

    /**
     * @see SubSectorEnum
     *
     * @ORM\Column(name="subsector", type="enum_sub_sector", nullable=true)
     */
    private string|null $subSector;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Entity\Project", inversedBy="sectors")
     */
    private $project;

    public function __construct(string $sector, Project $project, ?string $subSector = null)
    {
        $this->sector = $sector;
        $this->subSector = $subSector;
        $this->project = $project;
    }

    public function getSector(): string
    {
        return $this->sector;
    }

    public function setSector(string $sector): void
    {
        $this->sector = $sector;
    }

    /**
     * backward compatibility
     */
    public function getName(): string
    {
        $subSectorText = $this->getSubSector() ? '-' . $this->getSubSector() : '';

        return $this->getSector() . $subSectorText;
    }

    public function getSubSector(): ?string
    {
        return $this->subSector;
    }

    public function setSubSector(?string $subSector): void
    {
        $this->subSector = $subSector;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): void
    {
        $this->project = $project;
    }
}
