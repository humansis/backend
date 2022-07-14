<?php

declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Entity\Community;
use InvalidArgumentException;
use NewApiBundle\Serializer\MapperInterface;

/**
 * Class CommunityMapper
 * @package NewApiBundle\Mapper
 */
class CommunityMapper implements MapperInterface
{
    /**
     * @var Community
     */
    private $object;

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

        throw new InvalidArgumentException('Invalid argument. It should be instance of '. Community::class . ', '.get_class($object).' given.');
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->object->getId();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getProjectIds(): array
    {
        return array_values(array_map(function ($item) {
            return $item->getId();
        }, $this->object->getProjects()->toArray()));
    }

    /**
     * @return string|null
     */
    public function getLongitude(): ?string
    {
        return $this->object->getLongitude();
    }

    /**
     * @return string|null
     */
    public function getLatitude(): ?string
    {
        return $this->object->getLatitude();
    }

    /**
     * @return string|null
     */
    public function getContactGivenName(): ?string
    {
        return $this->object->getContactName();
    }

    /**
     * @return string|null
     */
    public function getContactFamilyName(): ?string
    {
        return $this->object->getContactFamilyName();
    }

    /**
     * @return int|null
     */
    public function getAddressId(): ?int
    {
        if (is_null($this->object->getAddress())) {
            return null;
        }

        return $this->object->getAddress()->getId();
    }

    /**
     * @return int|null
     */
    public function getNationalId(): ?int
    {
        if (is_null($this->object->getNationalId())) {
            return null;
        }

        return $this->object->getNationalId()->getId();
    }

    /**
     * @return int|null
     */
    public function getPhoneId(): ?int
    {
        if (is_null($this->object->getPhone())) {
            return null;
        }

        return $this->object->getPhone()->getId();
    }
}
