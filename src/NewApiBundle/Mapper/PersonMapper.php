<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\Person;
use DateTimeInterface;
use InvalidArgumentException;
use NewApiBundle\Serializer\MapperInterface;

class PersonMapper implements MapperInterface
{
    /** @var Person */
    private $object;

    /**
     * {@inheritDoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Person;
    }

    /**
     * {@inheritDoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Person) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException('Invalid argument. It should be instance of '.Person::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getProfileId(): ?int
    {
        if ($this->object->getProfile()) {
            return $this->object->getProfile()->getId();
        }

        return null;
    }

    public function getReferralType(): ?string
    {
        if ($this->object->getReferral()) {
            return $this->object->getReferral()->getType();
        }

        return null;
    }

    public function getReferralComment(): ?string
    {
        if ($this->object->getReferral()) {
            return $this->object->getReferral()->getComment();
        }

        return null;
    }

    public function getEnGivenName(): ?string
    {
        return $this->object->getEnGivenName();
    }

    public function getEnFamilyName(): ?string
    {
        return $this->object->getEnFamilyName();
    }

    public function getEnParentsName(): ?string
    {
        return $this->object->getEnParentsName();
    }

    public function getLocalGivenName(): ?string
    {
        return $this->object->getLocalGivenName();
    }

    public function getLocalFamilyName(): ?string
    {
        return $this->object->getLocalFamilyName();
    }

    public function getLocalParentsName(): ?string
    {
        return $this->object->getLocalParentsName();
    }

    public function getGender(): ?string
    {
        if (null !== $this->object->getGender()) {
            return 1 === $this->object->getGender() ? 'M' : 'F';
        }

        return null;
    }

    public function getDateOfBirth(): ?DateTimeInterface
    {
        return $this->object->getDateOfBirth();
    }

    public function getUpdatedOn(): ?DateTimeInterface
    {
        return $this->object->getUpdatedOn();
    }
}
