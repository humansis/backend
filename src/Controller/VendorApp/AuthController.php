<?php

declare(strict_types=1);

namespace Controller\VendorApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Entity\User;
use Utils\VendorService;

class AuthController extends AbstractVendorAppController
{
    public function __construct(private readonly VendorService $vendorService)
    {
    }

    /**
     * @Rest\Post("/vendor-app/v2/login")
     *
     *
     */
    public function loginVendorApp(?Profiler $profiler): Response
    {
        if (null !== $profiler) {
            $profiler->disable();
        }

        /** @var User $user */
        $user = $this->getUser();
        try {
            $vendor = $this->vendorService->getVendorByUser($user);
        } catch (NotFoundHttpException) {
            throw new AccessDeniedHttpException(
                'User does not have assigned vendor. You cannot log-in into vendor app.'
            );
        }

        return $this->json($vendor, Response::HTTP_OK, [], ['login' => true]);
    }
}
