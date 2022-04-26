<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Entity\Import;
use NewApiBundle\Serializer\MapperInterface;
use ProjectBundle\Entity\Project;

class ImportMapper implements MapperInterface
{
    /** @var Import */
    private $object;

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

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Import::class.', '.get_class($object).' given.');
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
        return array_map(function (Project $project) {
            return $project->getId();
        }, $this->object->getProjects()->toArray());
    }

    public function getStatus(): string
    {
        return $this->object->getState();
    }

    public function getCreatedBy(): int
    {
        return $this->object->getCreatedBy()->getId();
    }

    public function getCreatedAt(): string
    {
        return $this->object->getCreatedAt()->format(\DateTimeInterface::ISO8601);
    }
}
