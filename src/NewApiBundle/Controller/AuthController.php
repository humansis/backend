<?php

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthController extends AbstractController
{
    /**
     * @Rest\Post("/login")
     *
     * @param JWTTokenManagerInterface $JWTManager
     *
     * @return JsonResponse
     */
    public function getTokenUser(JWTTokenManagerInterface $JWTManager)
    {
        $user = $this->getUser();

        return $this->json([
            'token' => $JWTManager->create($user),
        ]);
    }
}
