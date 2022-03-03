<?php declare(strict_types=1);

namespace NewApiBundle\Controller\VendorApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use UserBundle\Entity\User;

class AuthController extends AbstractVendorAppController
{
    /**
     * @Rest\Post("/vendor-app/v2/login")
     *
     * @param Profiler|null $profiler
     *
     * @return JsonResponse|Response
     */
    public function loginVendorApp(?Profiler $profiler): Response
    {
        if (null !== $profiler) {
            $profiler->disable();
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $vendor = $this->container->get('voucher.vendor_service')->login($user);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_FORBIDDEN);
        }

        return $this->json($vendor, Response::HTTP_OK, [], ['login' => true]);
    }

}
