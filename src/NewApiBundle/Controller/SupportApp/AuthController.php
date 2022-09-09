<?php

namespace NewApiBundle\Controller\SupportApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use NewApiBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use UserBundle\Entity\User;

class AuthController extends AbstractController
{

    /**
     * @var JWTTokenManagerInterface
     */
    private $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
    }

    /**
     * @Rest\Post("/support-app/v1/login")
     *
     * @param Profiler|null            $profiler
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
