<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\SupportApp;

use BeneficiaryBundle\Repository\BeneficiaryRepository;
use BeneficiaryBundle\Repository\CommunityRepository;
use BeneficiaryBundle\Repository\InstitutionRepository;
use DistributionBundle\Entity;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Repository\AssistanceBeneficiaryRepository;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use InvalidArgumentException;
use NewApiBundle\Component\Assistance\AssistanceFactory;
use NewApiBundle\Component\Assistance\Domain;
use NewApiBundle\Component\Assistance\Services\AssistanceBeneficiaryService;
use NewApiBundle\Controller\AbstractController;
use NewApiBundle\DBAL\NationalIdTypeEnum;
use NewApiBundle\Enum\NationalIdType;
use NewApiBundle\Exception\ManipulationOverValidatedAssistanceException;
use NewApiBundle\InputType\AddRemoveAbstractBeneficiaryToAssistanceInputType;
use NewApiBundle\InputType\AddRemoveBeneficiaryToAssistanceInputType;
use NewApiBundle\InputType\AddRemoveCommunityToAssistanceInputType;
use NewApiBundle\InputType\AddRemoveInstitutionToAssistanceInputType;
use NewApiBundle\InputType\Assistance\AssistanceBeneficiariesOperationInputType;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\InputType\BeneficiaryOrderInputType;
use NewApiBundle\InputType\CommunityFilterType;
use NewApiBundle\InputType\CommunityOrderInputType;
use NewApiBundle\InputType\InstitutionFilterInputType;
use NewApiBundle\InputType\InstitutionOrderInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("web-app/v1/assistances/{id}/assistances-beneficiaries")
 */
class AssistanceBeneficiaryController extends AbstractController
{
    /**
     * @var AssistanceBeneficiaryRepository
     */
    private $assistanceBeneficiaryRepository;

    /**
     * @var BeneficiaryRepository
     */
    private $beneficiaryRepository;

    /**
     * @var AssistanceBeneficiaryService
     */
    private $assistanceBeneficiaryService;

    /**
     * @param AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository
     * @param AssistanceBeneficiaryService    $assistanceBeneficiaryService
     */
    public function __construct(AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository,
                                BeneficiaryRepository $beneficiaryRepository,
                                AssistanceBeneficiaryService $assistanceBeneficiaryService)
    {
        $this->assistanceBeneficiaryRepository = $assistanceBeneficiaryRepository;
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->assistanceBeneficiaryService = $assistanceBeneficiaryService;
    }



    /**
     * @Rest\Put("/web-app/v1/assistances/{id}/assistances-beneficiaries")
     *
     * @param Assistance                         $assistance
     * @param AssistanceBeneficiariesOperationInputType $inputType
     * @param BeneficiaryRepository                     $repository
     * @param AssistanceFactory                         $factory
     *
     * @return JsonResponse
     */
    public function addOrRemoveAssistanceBeneficiaries(
        Assistance                         $assistance,
        AssistanceBeneficiariesOperationInputType $inputType,
        BeneficiaryRepository                     $repository,
        AssistanceFactory                         $factory
    ): JsonResponse {
        if ($assistance->getTargetType() !== AssistanceTargetType::HOUSEHOLD
            && $assistance->getTargetType() !== AssistanceTargetType::INDIVIDUAL) {
            throw new InvalidArgumentException('This assistance is only for households or individuals');
        }

        try {
            $beneficiaries = $this->beneficiaryRepository->findByIdentities($inputType->getNumbers());
            $this->assistanceBeneficiaryService->
        } catch (ManipulationOverValidatedAssistanceException $e) {
            return $this->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function actualizeBeneficiaryNationalId(
        Domain\Assistance                                 $assistance,
        array                                             $idList,
        BeneficiaryRepository                                  $repository,
        AddRemoveAbstractBeneficiaryToAssistanceInputType $inputType
    ): void {
        foreach ($idList as $id) {
            if ($inputType->getAdded()) {
                $beneficiaries = $repository->findByIdentity($id);
            } else {
                $beneficiaries = $repository->findByIdentityAndProject($id, NationalIdType::TAX_NUMBER, $assistance->getAssistanceRoot()->getProject());
            }

            if (count($beneficiaries) > 0) {
                foreach ($beneficiaries as $beneficiary) {
                    if ($inputType->getAdded()) {

                        $assistance->addBeneficiary($beneficiary, $inputType->getJustification());
                    } elseif ($inputType->getRemoved()) {
                        $assistance->removeBeneficiary($beneficiary, $inputType->getJustification());
                    }
                }
            }

        }
    }


}
