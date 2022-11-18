<?php

declare(strict_types=1);

namespace DataHelper;

use Entity\User;
use InputType\UserCreateInputType;
use InputType\UserInitializeInputType;
use Repository\UserRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Utils\UserService;

final class UserDataHelper
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserService $userService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function createDummy(): User
    {
        $userInitializeInputType = new UserInitializeInputType();
        $userInitializeInputType->setUsername('dummy-user-' . time());

        if (count($this->validator->validate($userInitializeInputType)) > 0) {
            throw new \InvalidArgumentException('Invalid input type');
        }

        $userId = $this->userService->initialize($userInitializeInputType)['userId'];
        $user = $this->userRepository->find($userId);

        $userCreateInputType = new UserCreateInputType();
        $userCreateInputType->setEmail('dummy@email.com');
        $userCreateInputType->setPassword('dummy-password');
        $userCreateInputType->setRoles(['ROLE_ADMIN']);
        $userCreateInputType->setChangePassword(false);

        if (count($this->validator->validate($userCreateInputType)) > 0) {
            throw new \InvalidArgumentException('Invalid input type');
        }

        return $this->userService->create($user, $userCreateInputType);
    }
}
