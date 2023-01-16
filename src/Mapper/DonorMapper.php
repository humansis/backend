<?php

declare(strict_types=1);

namespace Mapper;

use DateTime;
use DateTimeInterface;
use Entity\Donor;
use InvalidArgumentException;
use Serializer\MapperInterface;

class DonorMapper implements MapperInterface
{
    private ?Donor $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Donor && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof Donor) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Donor::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getFullname(): string
    {
        return $this->object->getFullname();
    }

    public function getShortname(): string
    {
        return $this->object->getShortname();
    }

    public function getNotes(): ?string
    {
        return $this->object->getNotes();
    }

    public function getDateAdded(): string
    {
        return $this->object->getDateAdded()->format(DateTimeInterface::ATOM);
    }

    public function getLogo(): ?string
    {
        return $this->object->getLogo();
    }
}
