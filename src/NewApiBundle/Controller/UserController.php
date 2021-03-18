<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\UserCreateInputType;
use NewApiBundle\InputType\UserUpdateInputType;
use NewApiBundle\InputType\UserFilterInputType;
use NewApiBundle\InputType\UserInitializeInputType;
use NewApiBundle\InputType\UserOrderInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Entity\User;
use UserBundle\Repository\UserRepository;
use UserBundle\Utils\UserService;

class UserController extends AbstractController
{
    /** @var UserService */
    private $userService;

    /**
     * UserController constructor.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @Rest\Get("/users/{id}")
     *
     * @param User $object
     *
     * @return JsonResponse
     */
    public function item(User $object): JsonResponse
    {
        return $this->json($object);
    }

    /**
     * @Rest\Get("/users")
     *
     * @param UserOrderInputType $userOderInputType
     * @param UserFilterInputType $userFilterInputType
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function list(UserOrderInputType $userOderInputType, UserFilterInputType $userFilterInputType, Pagination $pagination): JsonResponse
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getDoctrine()->getRepository(User::class);

        $users = $userRepository->findByParams($userOderInputType, $userFilterInputType, $pagination);

        return $this->json($users);
    }

    /**
     * @Rest\Post("/users/initialize")
     *
     * @param UserInitializeInputType $inputType
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function initialize(UserInitializeInputType $inputType): JsonResponse
    {
        $initializedUser = $this->userService->initialize($inputType);

        return $this->json($initializedUser);
    }

    /**
     * @Rest\Post("/users/{id}")
     *
     * @param User                $user
     * @param UserCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(User $user, UserCreateInputType $inputType): JsonResponse
    {
        /** @var UserService $userService */
        $userService = $this->userService;

        $user = $userService->create($user, $inputType);

        return $this->json($user);
    }

    /**
     * @Rest\Put("/users/{id}")
     *
     * @param User                $user
     * @param UserUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(User $user, UserUpdateInputType $inputType): JsonResponse
    {
        /** @var UserService $userService */
        $userService = $this->userService;

        $updatedUser = $userService->update($user, $inputType);

        return $this->json($updatedUser);
    }

    /**
     * @Rest\Delete("/users/{id}")
     *
     * @param User $user
     *
     * @return JsonResponse
     */
    public function delete(User $user): JsonResponse
    {
        $this->userService->remove($user);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/users/salt/{username}")
     *
     * @param string $username
     *
     * @return JsonResponse
     */
    public function getSalt(string $username): JsonResponse
    {
        $salt = $this->userService->getSalt($username);

        return $this->json($salt);
    }
}
