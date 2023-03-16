<?php

declare(strict_types=1);

namespace Mapper;

use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\User;
use Entity\UserCountry;
use Entity\UserProject;

class UserMapper implements MapperInterface
{
    private User|null $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof User && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof User) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . User::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getUsername(): string
    {
        return $this->object->getUsername();
    }

    public function getEmail(): string
    {
        return $this->object->getEmail();
    }

    public function getPhonePrefix(): ?string
    {
        return $this->object->getPhonePrefix();
    }

    public function getPhoneNumber(): ?string
    {
        return $this->object->getPhoneNumber();
    }

    public function getCountries(): array
    {
        return array_values(
            array_map(fn(UserCountry $item) => $item->getCountryIso3(), $this->object->getCountries()->toArray())
        );
    }

    public function getLanguage(): ?string
    {
        return $this->object->getLanguage();
    }

    public function getRoles(): array
    {
        return $this->object->getRoles();
    }

    public function getProjectIds(): array
    {
        return array_values(
            array_map(fn(UserProject $item) => $item->getProject()->getId(), $this->object->getProjects()->toArray())
        );
    }

    public function getChangePassword(): bool
    {
        return $this->object->getChangePassword();
    }

    public function get2fa(): bool
    {
        return $this->object->getTwoFactorAuthentication();
    }

    public function getFirstName(): string|null
    {
        return $this->object->getFirstName();
    }

    public function getLastName(): string|null
    {
        return $this->object->getLastName();
    }

    public function getPosition(): string|null
    {
        return $this->object->getPosition();
    }
}
