<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Entity\User;

class AuthController extends AbstractOfflineAppController
{
    /**
     * @Rest\Post("/offline-app/v2/login")
     *
     * @return JsonResponse|Response
     */
    public function loginFieldApp()
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getVendor()) {
            return new Response('Vendor can not connect to the field app.', Response::HTTP_FORBIDDEN);
        }

        if ($user->getChangePassword()) {
            return new Response("You must login to web app and change password", Response::HTTP_FORBIDDEN);
        }

        return $this->json($user, Response::HTTP_OK, [], ['login' => true]);
    }
}