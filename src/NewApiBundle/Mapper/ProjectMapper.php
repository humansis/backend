<?php

namespace NewApiBundle\Mapper;

use NewApiBundle\Serializer\MapperInterface;
use ProjectBundle\Entity\Donor;
use ProjectBundle\Entity\Project;
use ProjectBundle\Entity\ProjectSector;

class ProjectMapper implements MapperInterface
{
    /** @var Project */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Project && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof Project) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Project::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getIso3(): string
    {
        return $this->object->getIso3();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getNotes(): ?string
    {
        return $this->object->getNotes();
    }

    public function getTarget(): ?float
    {
        return $this->object->getTarget();
    }

    public function getNumberOfHouseholds(): int
    {
        return $this->object->getNumberOfHouseholds();
    }

    public function getStartDate(): string
    {
        return $this->object->getStartDate()->format('Y-m-d');
    }

    public function getEndDate(): string
    {
        return $this->object->getEndDate()->format('Y-m-d');
    }

    public function getSectorIds()
    {
        return array_map(function (ProjectSector $projectSector) {
            return $projectSector->getId();
        }, $this->object->getSectors()->toArray());
    }

    public function getDonorIds()
    {
        return array_map(function (Donor $donor) {
            return $donor->getId();
        }, $this->object->getDonors()->toArray());
    }
}
