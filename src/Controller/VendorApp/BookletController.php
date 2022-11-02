<?php

declare(strict_types=1);

namespace Controller\VendorApp;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Serializer\SerializerInterface;
use Utils\BookletService;

class BookletController extends AbstractVendorAppController
{
    public function __construct(private readonly BookletService $bookletService, private readonly SerializerInterface $serializer, private readonly LoggerInterface $logger
    ){
    }

    /**
     * Get booklets that have been deactivated
     *
     * @Rest\Get("/vendor-app/v1/deactivated-booklets")
     *
     * @return Response
     */
    public function vendorGetDeactivatedAction(Request $request)
    {
        try {
            $booklets = $this->bookletService->findDeactivated();
        } catch (Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->serializer->serialize($booklets, 'json', ['groups' => ['FullBooklet']]);

        return new Response($json);
    }

    /**
     * Get booklets that are protected by a password
     *
     * @Rest\Get("/vendor-app/v1/protected-booklets")
     *
     * @return Response
     */
    public function vendorGetProtectedAction(Request $request)
    {
        try {
            $booklets = $this->bookletService->findProtected();
        } catch (Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $bookletPasswords = [];

        foreach ($booklets as $booklet) {
            $bookletPasswords[] = [
                $booklet->getCode() => $booklet->getPassword(),
            ];
        }

        $json = $this->serializer->serialize($bookletPasswords, 'json', ['groups' => ['FullBooklet']]);

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
            $this->bookletService->deactivateMany($bookletCodes);
        } catch (Exception $exception) {
            $this->logger->error('exception', [$exception->getMessage()]);

            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode('Booklet successfully deactivated'));
    }
}
