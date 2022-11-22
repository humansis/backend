<?php

declare(strict_types=1);

namespace Mapper;

use InvalidArgumentException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Serializer\MapperInterface;
use Entity\User;
use Utils\UserService;

class OfflineLoginMapper implements MapperInterface
{
    /** @var User */
    private $object;

    /**
     * @var JWTTokenManagerInterface
     */
    private $JWTTokenManager;

    /**
     * @var UserService
     */
    private $userService;

    public function __construct(JWTTokenManagerInterface $JWTTokenManager, UserService $userService)
    {
        $this->JWTTokenManager = $JWTTokenManager;
        $this->userService = $userService;
    }

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof User &&
            isset($context[MapperInterface::OFFLINE_APP]) && $context[MapperInterface::OFFLINE_APP] === true &&
            isset($context['login']) && $context['login'] === true;
    }

    public function populate(object $object)
    {
        if ($object instanceof User) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . User::class . ', ' . get_class($object) . ' given.'
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

    public function getToken(): string
    {
        return $this->JWTTokenManager->create($this->object);
    }

    public function getEmail(): string
    {
        return $this->object->getEmail();
    }

    public function getChangePassword(): bool
    {
        return $this->object->getChangePassword();
    }

    public function getAvailableCountries(): array
    {
        return $this->userService->getCountries($this->object);
    }
}
