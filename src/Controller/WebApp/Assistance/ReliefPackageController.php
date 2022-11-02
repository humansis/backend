<?php

declare(strict_types=1);

namespace Controller\WebApp\Assistance;

use Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use Controller\WebApp\AbstractWebAppController;
use Entity\Assistance\ReliefPackage;
use InputType\Assistance\DistributeBeneficiaryReliefPackagesInputType;
use InputType\Assistance\DistributeReliefPackagesInputType;
use InputType\Assistance\ReliefPackageFilterInputType;
use Services\AssistanceDistributionService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ReliefPackageController extends AbstractWebAppController
{
    public function __construct(private readonly AssistanceDistributionService $assistanceDistributionService)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/relief-packages")
     *
     *
     */
    public function packages(
        Assistance $assistance,
        Request $request,
        ReliefPackageFilterInputType $filter
    ): JsonResponse {
        $reliefPackages = $this->getDoctrine()->getRepository(ReliefPackage::class)->findByAssistance(
            $assistance,
            $filter
        );

        $response = $this->json($reliefPackages);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/relief-packages/{id}")
     * @Cache(lastModified="package.getLastModifiedAt()", public=true)
     *
     *
     */
    public function package(ReliefPackage $package, Request $request): JsonResponse
    {
        $response = $this->json($package);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Patch("/web-app/v1/assistances/relief-packages/distribute")
     * @ParamConverter(class="InputType\Assistance\DistributeReliefPackagesInputType[]", name="packages", converter="input_type_converter")
     *
     * @param DistributeReliefPackagesInputType[] $packages
     */
    public function distributePackages(
        array $packages
    ): JsonResponse {
        $result = $this->assistanceDistributionService->distributeByReliefIds($packages, $this->getUser());

        return $this->json($result);
    }

    /**
     * @Rest\Patch("/web-app/v1/assistances/{id}/relief-packages/distribute")
     * @ParamConverter(class="InputType\Assistance\DistributeBeneficiaryReliefPackagesInputType[]", name="packages", converter="input_type_converter")
     *
     * @param DistributeBeneficiaryReliefPackagesInputType[] $packages
     *
     */
    public function distributeBeneficiaryPackages(
        Assistance $assistance,
        array $packages
    ): JsonResponse {
        $result = $this->assistanceDistributionService->distributeByBeneficiaryIdAndAssistanceId(
            $packages,
            $assistance,
            $this->getUser()
        );

        return $this->json($result);
    }
}
