<?php

namespace Controller;

use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations as Rest;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Entity\User;

class AuthController extends AbstractController
{
    /**
     *
     * @return JsonResponse
     */
    #[Rest\Post('/web-app/v5/login')]
    public function getTokenUser(JWTTokenManagerInterface $JWTManager, ?Profiler $profiler)
    {
        if (null !== $profiler) {
            //$profiler->disable();
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'token' => $JWTManager->create($user),
            'userId' => $user->getId(),
        ]);
    }
}
