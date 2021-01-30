<?php

namespace BeneficiaryBundle\Controller;

use BeneficiaryBundle\Mapper\InstitutionMapper;
use BeneficiaryBundle\Utils\InstitutionService;
use Exception;
use InvalidArgumentException;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Response;
use BeneficiaryBundle\Entity\Institution;
use CommonBundle\InputType as GlobalInputType;
use BeneficiaryBundle\InputType;

//Annotations
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @SWG\Parameter(
 *     name="country",
 *     in="header",
 *     type="string",
 *     required=true
 * )
 */
class InstitutionController extends Controller
{
    /** @var InstitutionMapper */
    private $institutionMapper;
    /** @var InstitutionService */
    private $institutionService;

    /**
     * InstitutionController constructor.
     *
     * @param InstitutionMapper  $institutionMapper
     * @param InstitutionService $institutionService
     */
    public function __construct(InstitutionMapper $institutionMapper, InstitutionService $institutionService)
    {
        $this->institutionMapper = $institutionMapper;
        $this->institutionService = $institutionService;
    }

    /**
     * @Rest\Get("/institutions/{id}")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Institutions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Institution $institution
     * @return Response
     */
    public function showAction(Institution $institution)
    {
        if (true === $institution->getArchived()) {
            return new Response("Institution was archived", Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->institutionMapper->toFullArray($institution));
    }

    /**
     * @Rest\Post("/institutions/get/all", name="all_institutions")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Institutions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All institutions",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Institution::class))
     *     )
     * )
     *
     * @param GlobalInputType\Country $country
     * @param GlobalInputType\DataTableType $dataTableType
     * @return Response
     */
    public function allAction(GlobalInputType\Country $country, GlobalInputType\DataTableType $dataTableType)
    {
        try {
            $institutions = $this->institutionService->getAll($country, $dataTableType);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            0 => $institutions[0],
            1 => $this->institutionMapper->toFullArrays($institutions[1]),
        ]);
    }


    /**
     * @Rest\Put("/institutions", name="add_institution_projects")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Institutions")
     *
     * @SWG\Parameter(
     *     name="institution",
     *     in="body",
     *     required=true,
     *     @Model(type=InputType\NewInstitutionType::class, groups={"FullInstitution"})
     * )
     *
     * @SWG\Parameter(
     *     name="projects",
     *     in="body",
     *     required=true,
     *     type="array",
     *     schema={}
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Institution created",
     *     @Model(type=Institution::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     *
     * @param GlobalInputType\Country $country
     * @param InputType\NewInstitutionType $newInstitution
     * @return Response
     */
    public function createAction(GlobalInputType\Country $country, InputType\NewInstitutionType $newInstitution)
    {
        try {
            $institution = $this->institutionService->createDeprecated($country, $newInstitution);
            $this->getDoctrine()->getManager()->persist($institution);
            $this->getDoctrine()->getManager()->flush();
        } catch (InvalidArgumentException $exception) {
            return new Response(json_encode($exception->getMessage()), Response::HTTP_BAD_REQUEST);
        } catch (ValidationException $exception) {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($this->institutionMapper->toFullArray($institution));
    }


    /**
     * @Rest\Post("/institutions/{id}", name="edit_institution", requirements={"id": "\d+"})
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Institutions")
     *
     * @SWG\Parameter(
     *     name="institution",
     *     in="body",
     *     required=true,
     *     @Model(type=InputType\UpdateInstitutionType::class, groups={"FullInstitution"})
     * )
     *
     * @SWG\Parameter(
     *     name="projects",
     *     in="body",
     *     required=true,
     *     type="array",
     *     schema={}
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Institution edited",
     *     @Model(type=Institution::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     *
     * @param GlobalInputType\Country $country
     * @param InputType\UpdateInstitutionType $institutionType
     * @param Institution $institution
     * @return Response
     */
    public function updateAction(GlobalInputType\Country $country, InputType\UpdateInstitutionType $institutionType, Institution $institution)
    {
        try {
            $institution = $this->institutionService->updateDeprecated($country, $institution, $institutionType);
            $this->getDoctrine()->getManager()->persist($institution);
            $this->getDoctrine()->getManager()->flush();
        } catch (InvalidArgumentException $exception) {
            return new Response(json_encode($exception->getMessage()), Response::HTTP_BAD_REQUEST);
        } catch (ValidationException $exception) {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($this->institutionMapper->toFullArray($institution));
    }

    /**
     * @Rest\Delete("/institutions/{id}")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Institutions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Institution $institution
     * @return Response
     */
    public function deleteAction(Institution $institution)
    {
        $institution = $this->institutionService->remove($institution);
        return $this->json($this->institutionMapper->toFullArray($institution));
    }

}
