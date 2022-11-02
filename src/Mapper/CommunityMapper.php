<?php

declare(strict_types=1);

namespace Mapper;

use Entity\Community;
use InvalidArgumentException;
use Serializer\MapperInterface;

/**
 * Class CommunityMapper
 *
 * @package Mapper
 */
class CommunityMapper implements MapperInterface
{
    private ?\Entity\Community $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Community && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Community) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Community::class . ', ' . $object::class . ' given.'
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

    public function getContactGivenName(): ?string
    {
        return $this->object->getContactName();
    }

    public function getContactFamilyName(): ?string
    {
        return $this->object->getContactFamilyName();
    }

    public function getAddressId(): ?int
    {
        if (is_null($this->object->getAddress())) {
            return null;
        }

        return $this->object->getAddress()->getId();
    }

    public function getNationalId(): ?int
    {
        if (is_null($this->object->getNationalId())) {
            return null;
        }

        return $this->object->getNationalId()->getId();
    }

    public function getPhoneId(): ?int
    {
        if (is_null($this->object->getPhone())) {
            return null;
        }

        return $this->object->getPhone()->getId();
    }
}
