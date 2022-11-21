<?php

declare(strict_types=1);

namespace Mapper\Import;

use DateTimeInterface;
use Entity\Import;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\Project;

class ImportMapper implements MapperInterface
{
    private ?\Entity\Import $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Import && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof Import) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Import::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getTitle(): string
    {
        return $this->object->getTitle();
    }

    public function getDescription(): ?string
    {
        return $this->object->getNotes();
    }

    public function getProjects(): array
    {
        return array_values(
            array_map(fn(Project $project) => [
                'id' => $project->getId(),
                'name' => $project->getName(),
            ], $this->object->getProjects()->toArray())
        );
    }

    public function getStatus(): string
    {
        return $this->object->getState();
    }

    public function getCreatedBy(): array
    {
        return [
            'id' => $this->object->getCreatedBy()->getId(),
            'email' => $this->object->getCreatedBy()->getEmail(),
        ];
    }

    public function getCreatedAt(): string
    {
        return $this->object->getCreatedAt()->format(DateTimeInterface::ATOM);
    }
}
