<?php

declare(strict_types=1);

namespace Controller;

use Controller\ExportController;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Exception\ConstraintViolationException;
use InputType\UserCreateInputType;
use InputType\UserUpdateInputType;
use InputType\UserFilterInputType;
use InputType\UserInitializeInputType;
use InputType\UserOrderInputType;
use Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Entity\User;
use Repository\UserRepository;
use Utils\UserService;

class UserController extends AbstractController
{
    /** @var UserService */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @Rest\Get("/web-app/v1/users/exports")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exports(Request $request): Response
    {
        $request->query->add(['users' => true]);

        return $this->forward(ExportController::class . '::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/web-app/v1/users/{id}")
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
     * @Rest\Get("/web-app/v1/users")
     *
     * @param UserOrderInputType $userOderInputType
     * @param UserFilterInputType $userFilterInputType
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function list(
        UserOrderInputType $userOderInputType,
        UserFilterInputType $userFilterInputType,
        Pagination $pagination
    ): JsonResponse {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getDoctrine()->getRepository(User::class);

        $users = $userRepository->findByParams($userOderInputType, $userFilterInputType, $pagination);

        return $this->json($users);
    }

    /**
     * @Rest\Post("/web-app/v1/users/initialize")
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
     * @Rest\Post("/web-app/v1/users/{id}")
     *
     * @param User $user
     * @param UserCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(User $user, UserCreateInputType $inputType): JsonResponse
    {

        $user = $this->userService->create($user, $inputType);

        return $this->json($user);
    }

    /**
     * @Rest\Put("/web-app/v1/users/{id}")
     *
     * @param User $user
     * @param UserUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(User $user, UserUpdateInputType $inputType): JsonResponse
    {

        $updatedUser = $this->userService->update($user, $inputType);

        return $this->json($updatedUser);
    }

    /**
     * @Rest\Patch("/web-app/v1/users/{id}")
     *
     * @param User $user
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function patch(User $user, Request $request): JsonResponse
    {
        if ($request->request->has('password')) {
            $user->setPassword($request->request->get('password'));
            $user->setChangePassword(false);
        }

        if ($request->request->has('phoneNumber')) {
            $user->setPhoneNumber((int) $request->request->get('phoneNumber'));

            if ($request->request->has('phonePrefix')) {
                $user->setPhonePrefix($request->request->get('phonePrefix'));
            }
        }

        if ($request->request->has('2fa')) {
            if (!$user->getPhoneNumber() && $request->request->getBoolean('2fa')) {
                throw new ConstraintViolationException(
                    new ConstraintViolation(
                        'Unable to enable 2FA. There is no phone number.',
                        null,
                        [],
                        '2fa',
                        '2fa',
                        true
                    )
                );
            }

            $user->setTwoFactorAuthentication($request->request->getBoolean('2fa'));
        }

        $this->getDoctrine()->getManager()->persist($user);
        $this->getDoctrine()->getManager()->flush();

        return $this->json($user);
    }

    /**
     * @Rest\Delete("/web-app/v1/users/{id}")
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
     * @Rest\Get("/web-app/v1/users/salt/{username}")
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
