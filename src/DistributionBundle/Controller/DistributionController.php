<?php

namespace DistributionBundle\Controller;

use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Utils\DistributionBeneficiaryService;
use DistributionBundle\Utils\DistributionService;
use DistributionBundle\Utils\DistributionCsvService;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use DistributionBundle\Entity\DistributionData;
use BeneficiaryBundle\Entity\Beneficiary;
use ProjectBundle\Entity\Project;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class DistributionController
 * @package DistributionBundle\Controller
 */
class DistributionController extends Controller
{
    /**
     * @Rest\Get("/distributions/{id}/random")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Random beneficiaries",
     *     @Model(type=DistributionData::class)
     * )
     *
     * @param Request $request
     * @param DistributionData $distributionData
     *
     * @return Response
     */
    public function getRandomBeneficiariesAction(Request $request, DistributionData $distributionData)
    {
        if($request->query->get("size")){
            $numberToDisplay = $request->query->get("size");

            /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
            $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');
            $receivers = $distributionBeneficiaryService->getRandomBeneficiaries($distributionData, $numberToDisplay);

            $json = $this->get('jms_serializer')
                ->serialize(
                    $receivers,
                    'json',
                    SerializationContext::create()->setSerializeNull(true)->setGroups([
                        'FullReceivers',
                    ])
                );
        }
        else{
            $json = $this->get('jms_serializer')
                ->serialize(
                    "The size to display is unset",
                    'json',
                    SerializationContext::create()->setSerializeNull(true)->setGroups([
                        'FullReceivers',
                    ])
                );
        }


        return new Response($json);
    }

    /**
     * @Rest\Get("/distributions/{id}/validate")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Distribution validated",
     *     @Model(type=DistributionData::class)
     * )
     *
     * @param DistributionData $distributionData
     *
     * @return Response
     */
    public function validateAction(DistributionData $distributionData)
    {
        /** @var DistributionService $distributionService */
        $distributionService = $this->get('distribution.distribution_service');
        $distributionData = $distributionService->validateDistribution($distributionData);

        $json = $this->get('jms_serializer')
            ->serialize(
                $distributionData,
                'json',
                SerializationContext::create()->setSerializeNull(true)->setGroups([
                    'FullReceivers',
                    'FullDistribution',
                ])
            );

        return new Response($json);
    }

    /**
     * Create a distribution.
     *
     * @Rest\Put("/distributions", name="add_distribution")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="object",
     *      required=true,
     *      description="Body of the request",
     * 	  @SWG\Schema(ref=@Model(type=DistributionData::class))
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Project created",
     *     @Model(type=DistributionData::class)
     * )
     *
     * @param Request $request
     *
     * @return Response
     */
    public function addAction(Request $request)
    {
        $distributionArray = $request->request->all();
        $threshold = $distributionArray['threshold'];

        try {
            $listReceivers = $this->get('distribution.distribution_service')
                ->create($distributionArray['__country'], $distributionArray, $threshold);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $listReceivers,
                'json',
                SerializationContext::create()->setSerializeNull(true)->setGroups([
                    'FullReceivers',
                    'FullDistribution',
                ])
            );

        return new Response($json);
    }

    /**
     * @Rest\Put("/distributions/{id}/beneficiary", name="add_beneficiary_in_distribution")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="The object distribution beneficiary added",
     *     @Model(type=DistributionBeneficiary::class)
     * )
     *
     * @param Request          $request
     * @param DistributionData $distributionData
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function addBeneficiaryAction(Request $request, DistributionData $distributionData)
    {
        $data = $request->request->all();
        /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
        $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');
        $distributionBeneficiary = $distributionBeneficiaryService->addBeneficiary($distributionData, $data);

        $json = $this->get('jms_serializer')
            ->serialize(
                $distributionBeneficiary,
                'json',
                SerializationContext::create()->setSerializeNull(true)->setGroups([
                    'FullDistributionBeneficiary',
                    'FullDistribution',
                    'FullBeneficiary',
                ])
            );

        return new Response($json);
    }

    /**
     * @Rest\Delete("/beneficiaries/{id}", name="remove_one_beneficiary_in_distribution")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return if the beneficiary specified has been remove"
     * )
     *
     * @param Request $request
     * @param Beneficiary $beneficiary
     *
     * @return Response
     */
    public function removeOneBeneficiaryAction(Request $request, Beneficiary $beneficiary)
    {
        if ($request->query->get('distribution')) {
            $distributionId = $request->query->get('distribution');

            /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
            $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');

            $return = $distributionBeneficiaryService->removeBeneficiaryInDistribution($distributionId, $beneficiary);

            return new Response(json_encode($return));
        } else {
            $json = $this->get('jms_serializer')
                ->serialize('An error occured, please check the body', 'json', SerializationContext::create()->setSerializeNull(true)->setGroups(['FullHousehold']));
            return new Response($json);
        }
    }

    /**
     * @Rest\Get("/distributions", name="get_all_active_distributions")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Filtered distributions",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=DistributionData::class))
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getAllAction(Request $request)
    {
        $country = $request->request->get('__country');
        try {
            $distributions = $this->get('distribution.distribution_service')->getActiveDistributions($country);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $distributions,
                'json',
                SerializationContext::create()->setGroups(['FullDistribution'])->setSerializeNull(true)
            );

        return new Response($json);
    }


    /**
     * @Rest\Get("/distributions/{id}", name="get_one_distribution", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="one distribution",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=DistributionData::class))
     *     )
     * )
     *
     * @param DistributionData $DistributionData
     *
     * @return Response
     */
    public function getOneAction(DistributionData $DistributionData)
    {
        $json = $this->get('jms_serializer')
            ->serialize(
                $DistributionData,
                'json',
                SerializationContext::create()->setSerializeNull(true)->setGroups(['FullDistribution'])
            );

        return new Response($json);
    }

    /**
     * Get all beneficiaries of a distribution.
     *
     * @Rest\Get("/distributions/{id}/beneficiaries", name="get_beneficiaries_distribution", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="beneficiaries for one distribution",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Beneficiary::class))
     *     )
     * )
     *
     * @param DistributionData $distributionData
     * @return Response
     */
    public function getDistributionBeneficiariesAction(DistributionData $distributionData)
    {
        /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
        $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');
        $distributionBeneficiaries = $distributionBeneficiaryService->getDistributionBeneficiaries($distributionData);
        
        $json = $this->get('jms_serializer')
            ->serialize(
                $distributionBeneficiaries,
                'json',
                SerializationContext::create()->setSerializeNull(true)->setGroups([
                    'Transaction',
                ])
            );

        return new Response($json);
    }

    /**
     * Edit a distribution.
     *
     * @Rest\Post("/distributions/{id}", name="update_distribution")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Parameter(
     *     name="DistributionData",
     *     in="body",
     *     required=true,
     *     @Model(type=DistributionData::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="distribution updated",
     *     @Model(type=DistributionData::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request          $request
     * @param DistributionData $DistributionData
     *
     * @return Response
     */
    public function updateAction(Request $request, DistributionData $DistributionData)
    {
        $distributionArray = $request->request->all();
        try {
            $DistributionData = $this->get('distribution.distribution_service')
                ->edit($DistributionData, $distributionArray);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        $json = $this->get('jms_serializer')
            ->serialize($DistributionData, 'json', SerializationContext::create()->setSerializeNull(true));

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Archive a distribution.
     *
     * @Rest\Post("/distributions/archive/{id}", name="archived_project")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param DistributionData $distribution
     *
     * @return Response
     */
    public function archivedAction(DistributionData $distribution)
    {
        try {
            $archivedDistribution = $this->get('distribution.distribution_service')
                ->archived($distribution);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        $json = $this->get('jms_serializer')
            ->serialize($archivedDistribution, 'json', SerializationContext::create()->setSerializeNull(true));

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Get distributions of one project.
     *
     * @Rest\Get("/distributions/projects/{id}", name="get_distributions_of_project")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ', project)")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Project $project
     *
     * @return Response
     */
    public function getDistributionsAction(Project $project)
    {
        try {
            $distributions = $project->getDistributions();
            $filtered = $this->get('distribution.distribution_service')->filterDistributions($distributions);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $filtered,
                'json',
                SerializationContext::create()->setGroups(['FullDistribution'])->setSerializeNull(true)
            );

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Import beneficiaries of one distribution.
     *
     * @Rest\Post("/import/beneficiaries/distribution/{id}", name="import_beneficiaries_distribution")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Parameter(
     *     name="file",
     *     in="formData",
     *     required=true,
     *     type="file"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return Beneficiaries (old and new) if similarity founded",
     *      examples={
     *          "application/json": {{
     *              "old": @Model(type=Beneficiary::class),
     *              "new": @Model(type=Beneficiary::class)
     *          }}
     *      }
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request          $request
     * @param DistributionData $distributionData
     *
     * @return Response
     */
    public function importAction(Request $request, DistributionData $distributionData)
    {
        /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
        $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');
        $beneficiaries = $distributionBeneficiaryService->getBeneficiaries($distributionData);

        $countryIso3 =  $request->request->get('__country');

        /** @var DistributionCsvService $distributionCsvService */
        $distributionCsvService = $this->get('distribution.distribution_csv_service');

        if ($request->query->get('step')) {
            $step = $request->query->get('step');
            // File Import
            if ($step == 1) {
                if (!$request->files->has('file')) {
                    return new Response('You must upload a file.', Response::HTTP_BAD_REQUEST);
                }
                
                try {
                    $return = $distributionCsvService->parseCSV($countryIso3, $beneficiaries, $distributionData, $request->files->get('file'));
                } catch (\Exception $e) {
                    return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            // Save changes
            } elseif ($step == 2) {
                $data = $request->request->get('data');
                if (!$data) {
                    return new Response('You must provide the data to update.', Response::HTTP_BAD_REQUEST);
                }
                try {
                    $return = $distributionCsvService->saveCSV($countryIso3, $distributionData, $data);
                } catch (\Exception $e) {
                    return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                $return = 'An error occured, please check the body';
            }

            $json = $this->get('jms_serializer')
                ->serialize($return, 'json', SerializationContext::create()->setSerializeNull(true)->setGroups(['FullHousehold']));

            return new Response($json);
        } else {
            return new Response('An error occured, please check the body', Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get beneficiaries of one project.
     *
     * @Rest\Post("/distributions/beneficiaries/project/{id}", name="get_beneficiaries_of_project")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Project $project
     *
     * @param Request $request
     * @return Response
     */
    public function getBeneficiariesInProjectAction(Project $project, Request $request)
    {
        /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
        $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');
        if(!$request->request->has('target'))
            return new Response('You must defined a target', 500);

        $target = $request->request->get('target');

        try {
            $beneficiariesInProject = $distributionBeneficiaryService->getAllBeneficiariesInProject($project, $target);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')
        ->serialize($beneficiariesInProject, 'json', SerializationContext::create()->setSerializeNull(true)->setGroups(['FullHousehold']));

        return new Response($json, Response::HTTP_OK);
    }
}
