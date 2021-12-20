<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use UserBundle\Entity\User;

class AuthController extends AbstractOfflineAppController
{
    /**
     * @Rest\Post("/offline-app/v2/login")
     *
     * @param JWTTokenManagerInterface $JWTManager
     *
     * @return JsonResponse
     */
    public function loginFieldApp(JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'token' => $JWTManager->create($user),
            'userId' => $user->getId(),
        ]);
    }
}
