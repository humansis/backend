<?php

declare(strict_types=1);

namespace Controller\VendorApp;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

class BookletController extends AbstractVendorAppController
{
    /**
     * Get booklets that have been deactivated
     *
     * @Rest\Get("/vendor-app/v1/deactivated-booklets")
     *
     * @param Request $request
     * @return Response
     */
    public function vendorGetDeactivatedAction(Request $request)
    {
        try {
            $booklets = $this->get('voucher.booklet_service')->findDeactivated();
        } catch (Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('serializer')->serialize($booklets, 'json', ['groups' => ['FullBooklet']]);

        return new Response($json);
    }

    /**
     * Get booklets that are protected by a password
     *
     * @Rest\Get("/vendor-app/v1/protected-booklets")
     *
     * @param Request $request
     * @return Response
     */
    public function vendorGetProtectedAction(Request $request)
    {
        try {
            $booklets = $this->get('voucher.booklet_service')->findProtected();
        } catch (Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $bookletPasswords = [];

        foreach ($booklets as $booklet) {
            $bookletPasswords[] = [
                $booklet->getCode() => $booklet->getPassword(),
            ];
        }

        $json = $this->get('serializer')->serialize($bookletPasswords, 'json', ['groups' => ['FullBooklet']]);

        return new Response($json);
    }

    /**
     * Deactivate booklets
     *
     * @Rest\Post("/vendor-app/v1/deactivate-booklets")
     *
     * @return Response
     */
    public function vendorDeactivateBookletsAction(Request $request)
    {
        try {
            $data = $request->request->all();
            $bookletCodes = $data['bookletCodes'];
            $this->get('voucher.booklet_service')->deactivateMany($bookletCodes);
        } catch (Exception $exception) {
            $this->container->get('logger')->error('exception', [$exception->getMessage()]);

            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode('Booklet successfully deactivated'));
    }
}
