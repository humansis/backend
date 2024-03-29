<?php

declare(strict_types=1);

namespace Mapper;

use DateTimeInterface;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\Project;
use Entity\ProjectSector;

class ProjectOfflineMapper implements MapperInterface
{
    use MapperContextTrait;

    private ?\Entity\Project $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Project && $this->isOfflineApp($context);
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Project) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Project::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getInternalId(): ?string
    {
        return $this->object->getInternalId();
    }

    public function getIso3(): string
    {
        return $this->object->getCountryIso3();
    }

    public function getNotes(): ?string
    {
        return $this->object->getNotes();
    }

    public function getTarget(): int
    {
        return (int) $this->object->getTarget();
    }

    public function getStartDate(): DateTimeInterface
    {
        return $this->object->getStartDate();
    }

    public function getEndDate(): DateTimeInterface
    {
        return $this->object->getEndDate();
    }

    public function getSectors(): array
    {
        return array_values(
            array_map(fn(ProjectSector $item) => $item->getSector(), $this->object->getSectors()->toArray())
        );
    }

    public function getDonorIds(): array
    {
        return array_values(
            array_map(fn($item) => $item->getId(), $this->object->getDonors()->toArray())
        );
    }

    public function getNumberOfHouseholds(): int
    {
        return $this->object->getNumberOfHouseholds();
    }
}
