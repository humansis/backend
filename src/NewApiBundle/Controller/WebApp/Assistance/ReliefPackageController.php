<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\WebApp\Assistance;

use DistributionBundle\Entity\Assistance;
use NewApiBundle\Controller\WebApp\AbstractWebAppController;
use NewApiBundle\Workflow\ReliefPackageTransitions;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Repository\Assistance\ReliefPackageRepository;
use NewApiBundle\InputType\Assistance\ReliefPackageFilterInputType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Workflow\WorkflowInterface;

class ReliefPackageController extends AbstractWebAppController
{
    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/relief-packages")
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
     * @Rest\Get("/web-app/v1/assistances/relief-packages/{id}")
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

    /**
     * @Rest\Patch("/web-app/v1/assistances/relief-packages/distribute")
     * @ParamConverter(class="NewApiBundle\InputType\Assistance\DistributeReliefPackagesInputType", name="packages", converter="array_input_type_converter")
     *
     * @param array                   $packages
     * @param ReliefPackageRepository $repository
     *
     * @return JsonResponse
     */
    public function distributePackages(array $packages, ReliefPackageRepository $repository, WorkflowInterface $reliefPackageWorkflow): JsonResponse
    {
        foreach ($packages as $packageUpdate) {
            /** @var ReliefPackage $package */
            $package = $repository->find($packageUpdate->getId());
            if ($packageUpdate->getAmountDistributed() === null) {
                $package->distributeRest();
            } else {
                $package->addAmountOfDistributed($packageUpdate->getAmountDistributed());
            }

            if ($reliefPackageWorkflow->can($package, ReliefPackageTransitions::DISTRIBUTE)) {
                $reliefPackageWorkflow->apply($package, ReliefPackageTransitions::DISTRIBUTE);
            }

            $repository->save($package);
        }
        return $this->json(true);
    }
}
