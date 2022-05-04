<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp\Assistance;

use NewApiBundle\Controller\OfflineApp\AbstractOfflineAppController;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Repository\Assistance\ReliefPackageRepository;
use NewApiBundle\Workflow\ReliefPackageTransitions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

class ReliefPackageController extends AbstractOfflineAppController
{
    /**
     * @Rest\Patch("/offline-app/v1/assistances/relief-packages/distribute")
     * @ParamConverter(class="NewApiBundle\InputType\Assistance\DistributeReliefPackagesInputType", name="packages", converter="array_input_type_converter")
     *
     * @param array                   $packages
     * @param ReliefPackageRepository $repository
     * @param Registry                $registry
     *
     * @return JsonResponse
     */
    public function distributePackages(array $packages, ReliefPackageRepository $repository, Registry $registry): JsonResponse
    {
        foreach ($packages as $packageUpdate) {
            /** @var ReliefPackage $package */
            $package = $repository->find($packageUpdate->getId());
            if ($packageUpdate->getAmountDistributed() === null) {
                $package->distributeRest();
            } else {
                $package->addAmountOfDistributed($packageUpdate->getAmountDistributed());
            }

            $reliefPackageWorkflow = $registry->get($package);
            if ($reliefPackageWorkflow->can($package, ReliefPackageTransitions::DISTRIBUTE)) {
                $reliefPackageWorkflow->apply($package, ReliefPackageTransitions::DISTRIBUTE);
            }

            $repository->save($package);
        }
        return $this->json(true);
    }
}
