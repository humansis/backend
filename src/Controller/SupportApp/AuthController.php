<?php

namespace Controller\SupportApp;

use Controller\AbstractController;
use Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * @Rest\Route("/support-app/v1/login")
 */
class AuthController extends AbstractController
{
    public function __construct(private readonly JWTTokenManagerInterface $jwtManager)
    {
    }

    /**
     * @Rest\Post
     *
     *
     * @return JsonResponse
     */
    public function getTokenUser(?Profiler $profiler)
    {
        if (null !== $profiler) {
            $profiler->disable();
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'token' => $this->jwtManager->create($user),
            'userId' => $user->getId(),
        ]);
    }
}
