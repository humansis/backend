<?php

namespace NewApiBundle\Controller\OfflineApp;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Utils\BeneficiaryService;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\Serializer\MapperInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BeneficiaryController extends AbstractOfflineAppController
{
    /** @var BeneficiaryService */
    private $beneficiaryService;

    /**
     * @param BeneficiaryService $beneficiaryService
     */
    public function __construct(BeneficiaryService $beneficiaryService)
    {
        $this->beneficiaryService = $beneficiaryService;
    }

    /**
     * @Rest\Get("/offline-app/v2/beneficiaries")
     *
     * @param Request                    $request
     * @param BeneficiaryFilterInputType $filter
     *
     * @return JsonResponse
     * @deprecated Application require only one beneficiary at a time
     */
    public function beneficiaries(Request $request, BeneficiaryFilterInputType $filter): JsonResponse
    {
        $beneficiaries = $this->getDoctrine()->getRepository(Beneficiary::class)->findByParams($filter);

        $response = $this->json($beneficiaries, Response::HTTP_OK, [], [MapperInterface::OFFLINE_APP => false]);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Get("/offline-app/v2/beneficiary/{id}")
     *
     * @param Beneficiary $beneficiary
     * @param Request     $request
     *
     * @return JsonResponse
     */
    public function beneficiary(Beneficiary $beneficiary, Request $request): JsonResponse
    {
        $response = $this->json($beneficiary);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Post("/offline-app/v1/beneficiaries/{id}")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @param Request     $request
     * @param Beneficiary $beneficiary
     *
     * @return Response
     */
    public function updateAction(Request $request, Beneficiary $beneficiary): Response
    {
        $beneficiaryData = $request->request->all();

        try {
            $newBeneficiary = $this->beneficiaryService->update($beneficiary, $beneficiaryData);
        } catch (\Exception $exception) {
            $this->container->get('logger')->error('exception', [$exception->getMessage()]);
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        return $this->json($newBeneficiary);
    }
}
