<?php

namespace Mapper;

use Entity\Institution;
use InvalidArgumentException;
use Serializer\MapperInterface;

class InstitutionMapper implements MapperInterface
{
    private ?\Entity\Institution $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Institution && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof Institution) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Institution::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getProjectIds(): array
    {
        return array_values(
            array_map(fn($item) => $item->getId(), $this->object->getProjects()->toArray())
        );
    }

    public function getLongitude(): ?string
    {
        return $this->object->getLongitude();
    }

    public function getLatitude(): ?string
    {
        return $this->object->getLatitude();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getContactGivenName(): ?string
    {
        return $this->object->getContact()?->getEnGivenName();
    }

    public function getContactFamilyName(): ?string
    {
        return $this->object->getContact()?->getEnFamilyName();
    }

    public function getType(): string
    {
        return $this->object->getType();
    }

    public function getAddressId(): ?int
    {
        return $this->object->getAddress()?->getId();
    }

    public function getNationalId(): ?int
    {
        if (null === $this->object->getNationalId()) {
            return null;
        }

        return $this->object->getNationalId()->getId();
    }

    public function getPhoneId(): ?int
    {
        if (null === $this->object->getPhone()) {
            return null;
        }

        return $this->object->getPhone()->getId();
    }
}
