<?php

declare(strict_types=1);

namespace Mapper;

use Entity\Organization;
use Serializer\MapperInterface;

class OrganizationMapper implements MapperInterface
{
    /** @var Organization */
    private $object;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Organization && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof Organization) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Organization::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getLogo(): ?string
    {
        return $this->object->getLogo();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getPrimaryColor(): string
    {
        return $this->object->getPrimaryColor();
    }

    public function getSecondaryColor(): string
    {
        return $this->object->getSecondaryColor();
    }

    public function getFont(): string
    {
        return $this->object->getFont();
    }

    public function getFooterContent(): string
    {
        return $this->object->getFooterContent();
    }
}
