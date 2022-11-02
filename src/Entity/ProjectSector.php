<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use DBAL\SectorEnum;
use DBAL\SubSectorEnum;

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
    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     */
    private ?int $id = null;

    /**
     * ProjectSector constructor.
     */
    public function __construct(private string $sector, private Project $project, private ?string $subSector = null)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
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
