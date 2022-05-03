<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp\Assistance;

use DistributionBundle\Entity\Assistance;
use NewApiBundle\Controller\OfflineApp\AbstractOfflineAppController;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\InputType\Assistance\ReliefPackageFilterInputType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;

class ReliefPackageController extends AbstractOfflineAppController
{
    /**
     * @Rest\Get("/offline-app/v1/assistances/{id}/relief-packages")
     *
     * @param Assistance                   $assistance
     * @param Request                      $request
     * @param ReliefPackageFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function packages(Assistance $assistance, Request $request, ReliefPackageFilterInputType $filter): JsonResponse
    {
        $reliefPackages = $this->getDoctrine()->getRepository(ReliefPackage::class)->findByAssistance($assistance, $filter);

        $response = $this->json($reliefPackages);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Get("/offline-app/v1/assistances/relief-packages/{id}")
     * @Cache(lastModified="package.getLastModifiedAt()", public=true)
     *
     * @param ReliefPackage $package
     * @param Request       $request
     *
     * @return JsonResponse
     */
    public function package(ReliefPackage $package, Request $request): JsonResponse
    {
        $response = $this->json($package);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
