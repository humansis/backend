<?php

declare(strict_types=1);

namespace NewApiBundle\Controller\VendorApp;

use CommonBundle\Pagination\Paginator;
use Swagger\Annotations as SWG;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Serializer\SerializerInterface;
use VoucherBundle\Utils\BookletService;

/**
 * Class BookletController
 * @package VoucherBundle\Controller
 *
 * @SWG\Parameter(
 *     name="country",
 *     in="header",
 *     type="string",
 *     required=true
 * )
 */
class BookletController extends AbstractVendorAppController
{

    private $bookletService;
    private $serializer;

    public function __construct(BookletService $bookletService, SerializerInterface $serializer)
    {
        $this->bookletService = $bookletService;
        $this->serializer = $serializer;
    }



    /**
     * Get booklets that are protected by a password
     *
     * @Rest\Get("/vendor-app/v2/protected-booklets", name="get_protected_booklets")
     *
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Booklets delivered",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Booklet::class, groups={"ProtectedBooklet"}))
     *     )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getProtectedAction(Request $request)
    {
        try {
            $booklets = $this->bookletService->findProtected($request->headers->get('Country'));
            $json = $this->serializer->serialize(new Paginator($booklets), 'json', ['groups' => ['ProtectedBooklet']]);
            $response = new Response($json);
            $response->setEtag(md5($response->getContent()));
            $response->setPublic();
            $response->isNotModified($request);
            return $response;
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }


}
