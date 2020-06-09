<?php

namespace BeneficiaryBundle\Controller;

use BeneficiaryBundle\Mapper\InstitutionMapper;
use BeneficiaryBundle\Utils\ExportCSVService;
use BeneficiaryBundle\Utils\InstitutionService;
use CommonBundle\Response\CommonBinaryFileResponse;
use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BeneficiaryBundle\Entity\Institution;
use CommonBundle\InputType as GlobalInputType;
use BeneficiaryBundle\InputType;

//Annotations
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;

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
        /** @var InstitutionMapper $institutionMapper */
        $institutionMapper = $this->get(InstitutionMapper::class);

        $json = $this->get('serializer')->serialize(
                $institutionMapper->toFullArray($institution),
                'json'
            );
        return new Response($json);
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
        /** @var InstitutionService $institutionService */
        $institutionService = $this->get('beneficiary.institution_service');
        /** @var InstitutionMapper $institutionMapper */
        $institutionMapper = $this->get(InstitutionMapper::class);

        try {
            $institutions = $institutionService->getAll($country, $dataTableType);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $json = $this->get('serializer')->serialize(
                [
                    0 => $institutions[0],
                    1 => $institutionMapper->toFullArrays($institutions[1]),
                ],
                'json'
            );

        return new Response($json);
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
        /** @var InstitutionService $institutionService */
        $institutionService = $this->get('beneficiary.institution_service');
        /** @var InstitutionMapper $institutionMapper */
        $institutionMapper = $this->get(InstitutionMapper::class);
        try {
            $institution = $institutionService->create($country, $newInstitution);
            $this->getDoctrine()->getManager()->persist($institution);
            $this->getDoctrine()->getManager()->flush();
        } catch (ValidationException $exception) {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('serializer')->serialize(
                $institutionMapper->toFullArray($institution),
                'json'
            );
        return new Response($json);
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
        /** @var InstitutionService $institutionService */
        $institutionService = $this->get('beneficiary.institution_service');
        /** @var InstitutionMapper $institutionMapper */
        $institutionMapper = $this->get(InstitutionMapper::class);
        try {
            $institution = $institutionService->update($country, $institution, $institutionType);
            $this->getDoctrine()->getManager()->persist($institution);
            $this->getDoctrine()->getManager()->flush();
        } catch (ValidationException $exception) {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('serializer')->serialize(
                $institutionMapper->toFullArray($institution),
                'json'
            );
        return new Response($json);
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
        /** @var InstitutionService $institutionService */
        $institutionService = $this->get("beneficiary.institution_service");
        /** @var InstitutionMapper $institutionMapper */
        $institutionMapper = $this->get(InstitutionMapper::class);

        $institution = $institutionService->remove($institution);
        $json = $this->get('serializer')->serialize(
                $institutionMapper->toFullArray($institution),
                'json'
            );
        return new Response($json);
    }

}
