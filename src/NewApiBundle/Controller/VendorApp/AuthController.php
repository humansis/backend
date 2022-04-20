<?php declare(strict_types=1);

namespace NewApiBundle\Controller\VendorApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use UserBundle\Entity\User;
use UserBundle\Utils\UserService;
use VoucherBundle\Utils\VendorService;


class AuthController extends AbstractVendorAppController
{
    /** @var VendorService */
    private $vendorService;

    /** @var UserService */
    private $userService;

    /**
     * @param VendorService $vendorService
     * @param UserService   $userService
     */
    public function __construct(VendorService $vendorService, UserService $userService)
    {
        $this->vendorService = $vendorService;
        $this->userService = $userService;
    }

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
            $vendor = $this->vendorService->getVendorByUser($user);
        } catch (NotFoundHttpException $ex) {
            throw new AccessDeniedHttpException('User does not have assigned vendor. You cannot log-in into vendor app.');
        }

        return $this->json($vendor, Response::HTTP_OK, [], ['login' => true]);
    }

    /**
     * Get user's salt
     *
     * @Rest\Get("/vendor-app/v1/salt/{username}")
     */
    public function getSaltAction($username): Response
    {
        try {
            $salt = $this->userService->getSaltOld($username);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), $exception->getCode()>=Response::HTTP_BAD_REQUEST ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($salt);
    }

}
