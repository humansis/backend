<?php

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use UserBundle\Entity\User;

class AuthController extends AbstractController
{
    /**
     * @Rest\Post("/web-app/v1/login")
     *
     * @param JWTTokenManagerInterface $JWTManager
     *
     * @return JsonResponse
     */
    public function getTokenUser(JWTTokenManagerInterface $JWTManager)
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'token' => $JWTManager->create($user),
            'userId' => $user->getId(),
        ]);
    }
}
