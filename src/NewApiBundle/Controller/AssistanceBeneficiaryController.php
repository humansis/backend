<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Repository\BeneficiaryRepository;
use BeneficiaryBundle\Repository\CommunityRepository;
use BeneficiaryBundle\Repository\InstitutionRepository;
use DistributionBundle\Entity;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Repository\AssistanceBeneficiaryRepository;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use InvalidArgumentException;
use NewApiBundle\Component\Assistance\AssistanceFactory;
use NewApiBundle\Component\Assistance\Domain;
use NewApiBundle\InputType\AddRemoveAbstractBeneficiaryToAssistanceInputType;
use NewApiBundle\InputType\AddRemoveBeneficiaryToAssistanceInputType;
use NewApiBundle\InputType\AddRemoveCommunityToAssistanceInputType;
use NewApiBundle\InputType\AddRemoveInstitutionToAssistanceInputType;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\InputType\BeneficiaryOrderInputType;
use NewApiBundle\InputType\CommunityFilterType;
use NewApiBundle\InputType\CommunityOrderInputType;
use NewApiBundle\InputType\InstitutionFilterInputType;
use NewApiBundle\InputType\InstitutionOrderInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AssistanceBeneficiaryController extends AbstractController
{
    /**
     * @var AssistanceBeneficiaryRepository
     */
    private $assistanceBeneficiaryRepository;

    /**
     * @param AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository
     */
    public function __construct(AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository)
    {
        $this->assistanceBeneficiaryRepository = $assistanceBeneficiaryRepository;
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/assistances-beneficiaries")
     *
     * @param Entity\Assistance          $assistance
     * @param BeneficiaryFilterInputType $filter
     * @param BeneficiaryOrderInputType  $orderBy
     * @param Pagination                 $pagination
     *
     * @return JsonResponse
     */
    public function assistanceBeneficiariesByAssistance(
        Entity\Assistance          $assistance,
        BeneficiaryFilterInputType $filter,
        BeneficiaryOrderInputType  $orderBy,
        Pagination                 $pagination
    ): JsonResponse {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $assistanceBeneficiaries = $this->assistanceBeneficiaryRepository->findBeneficiariesByAssistance($assistance,
            $filter, $orderBy, $pagination);

        return $this->json($assistanceBeneficiaries);
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/assistances-institutions")
     *
     * @param Entity\Assistance          $assistance
     * @param InstitutionFilterInputType $filter
     * @param InstitutionOrderInputType  $orderBy
     * @param Pagination                 $pagination
     *
     * @return JsonResponse
     */
    public function assistanceInstitutionsByAssistance(
        Entity\Assistance          $assistance,
        InstitutionFilterInputType $filter,
        InstitutionOrderInputType  $orderBy,
        Pagination                 $pagination
    ): JsonResponse {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $assistanceInstitutions = $this->assistanceBeneficiaryRepository->findInstitutionsByAssistance($assistance,
            $filter, $orderBy, $pagination);

        return $this->json($assistanceInstitutions);
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/assistances-communities")
     *
     * @param Entity\Assistance       $assistance
     * @param CommunityFilterType     $filter
     * @param CommunityOrderInputType $orderBy
     * @param Pagination              $pagination
     *
     * @return JsonResponse
     */
    public function assistanceCommunitiesByAssistance(
        Entity\Assistance       $assistance,
        CommunityFilterType     $filter,
        CommunityOrderInputType $orderBy,
        Pagination              $pagination
    ): JsonResponse {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $assistanceCommunities = $this->assistanceBeneficiaryRepository->findCommunitiesByAssistance($assistance,
            $filter, $orderBy, $pagination);

        return $this->json($assistanceCommunities);
    }

    /**
     * @Rest\Put("/web-app/v1/assistances/{id}/assistances-beneficiaries")
     *
     * @param Entity\Assistance                         $assistanceRoot
     * @param AddRemoveBeneficiaryToAssistanceInputType $inputType
     * @param BeneficiaryRepository                     $repository
     * @param AssistanceFactory                         $factory
     *
     * @return JsonResponse
     */
    public function addOrRemoveAssistanceBeneficiaries(
        Entity\Assistance                         $assistanceRoot,
        AddRemoveBeneficiaryToAssistanceInputType $inputType,
        BeneficiaryRepository                     $repository,
        AssistanceFactory                         $factory
    ): JsonResponse {
        if ($assistanceRoot->getTargetType() !== AssistanceTargetType::HOUSEHOLD
            && $assistanceRoot->getTargetType() !== AssistanceTargetType::INDIVIDUAL) {
            throw new InvalidArgumentException('This assistance is only for households or individuals');
        }
        $this->actualizeBeneficiary(
            $factory->hydrate($assistanceRoot),
            $inputType->getBeneficiaryIds(),
            $repository,
            $inputType
        );

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function actualizeBeneficiary(
        Domain\Assistance                                 $assistance,
        array                                             $idList,
        EntityRepository                                  $repository,
        AddRemoveAbstractBeneficiaryToAssistanceInputType $inputType
    ): void {
        foreach ($idList as $id) {
            $beneficiary = $repository->find($id);
            if ($inputType->getAdded()) {
                $assistance->addBeneficiary($beneficiary, $inputType->getJustification());
            } elseif ($inputType->getRemoved()) {
                $assistance->removeBeneficiary($beneficiary, $inputType->getJustification());
            }
        }
    }

    /**
     * @Rest\Put("/web-app/v1/assistances/{id}/assistances-institutions")
     *
     * @param Entity\Assistance                         $assistanceRoot
     * @param AddRemoveInstitutionToAssistanceInputType $inputType
     * @param AssistanceFactory                         $factory
     * @param InstitutionRepository                     $repository
     *
     * @return JsonResponse
     */
    public function addOrRemoveAssistanceInstitutions(
        Entity\Assistance                         $assistanceRoot,
        AddRemoveInstitutionToAssistanceInputType $inputType,
        AssistanceFactory                         $factory,
        InstitutionRepository                     $repository
    ): JsonResponse {
        if ($assistanceRoot->getTargetType() !== AssistanceTargetType::INSTITUTION) {
            throw new InvalidArgumentException('This assistance is only for institutions');
        }

        $this->actualizeBeneficiary(
            $factory->hydrate($assistanceRoot),
            $inputType->getInstitutionIds(),
            $repository,
            $inputType
        );

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Put("/web-app/v1/assistances/{id}/assistances-communities")
     *
     * @param Entity\Assistance                       $assistanceRoot
     * @param AddRemoveCommunityToAssistanceInputType $inputType
     * @param AssistanceFactory                       $factory
     * @param CommunityRepository                     $repository
     *
     * @return JsonResponse
     */
    public function addOrRemoveAssistanceCommunities(
        Entity\Assistance                       $assistanceRoot,
        AddRemoveCommunityToAssistanceInputType $inputType,
        AssistanceFactory                       $factory,
        CommunityRepository                     $repository
    ): JsonResponse {
        if ($assistanceRoot->getTargetType() !== AssistanceTargetType::COMMUNITY) {
            throw new InvalidArgumentException('This assistance is only for communities');
        }

        $this->actualizeBeneficiary(
            $factory->hydrate($assistanceRoot),
            $inputType->getCommunityIds(),
            $repository,
            $inputType
        );

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
