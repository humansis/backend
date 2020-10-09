<?php

namespace DistributionBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Utils\DistributionBeneficiaryService;
use DistributionBundle\Utils\DistributionService;
use DistributionBundle\Utils\DistributionCsvService;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use DistributionBundle\Entity\Assistance;
use BeneficiaryBundle\Entity\Beneficiary;
use ProjectBundle\Entity\Project;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Class AssistanceController
 * @package DistributionBundle\Controller
 *
 * @SWG\Parameter(
 *      name="country",
 *      in="header",
 *      type="string",
 *      required=true
 * )
 */
class AssistanceController extends Controller
{
    /**
     * All distributed transactions by parameters
     *
     * @Rest\Get("/distributions/beneficiary/{beneficiaryId}")
     * @ParamConverter("beneficiary", options={"mapping": {"beneficiaryId" : "id"}})
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Distributions")
     * @SWG\Parameter(name="beneficiaryId",
     *     in="path",
     *     type="integer",
     *     required=true
     * )
     * @SWG\Response(
     *     response=200,
     *     description="List of distributed items to beneficiary",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Assistance::class, groups={"DistributionOverview"}))
     *     )
     * )
     * @SWG\Response(response=400, description="HTTP_BAD_REQUEST")
     *
     * @param Beneficiary $beneficiary
     * @return Response
     */
    public function distributionsToBeneficiary(Beneficiary $beneficiary)
    {
        $distributions = $this->getDoctrine()->getRepository(Assistance::class)->findDistributedToBeneficiary($beneficiary);

        $json = $this->get('serializer')
            ->serialize($distributions, 'json', ['groups' => ["DistributionOverview"]]);

        return new Response($json);
    }

    /**
     * All distributed transactions by parameters
     *
     * @Rest\Get("/distributions/household/{householdId}")
     * @ParamConverter("household", options={"mapping": {"householdId" : "id"}})
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Distributions")
     * @SWG\Parameter(name="householdId",
     *     in="path",
     *     type="integer",
     *     required=true
     * )
     * @SWG\Response(
     *     response=200,
     *     description="List of distributed items to household",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=DistributionData::class, groups={"DistributionOverview"}))
     *     )
     * )
     * @SWG\Response(response=400, description="HTTP_BAD_REQUEST")
     *
     * @param Household $household
     * @return Response
     */
    public function distributionsToHousehold(Household $household)
    {
        $distributions = $this->getDoctrine()->getRepository(DistributionData::class)->findDistributedToHousehold($household);

        return $this->json($distributions);
    }

    /**
     * @Rest\Get("/distributions/{id}/random")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Random beneficiaries",
     *     @Model(type=Assistance::class)
     * )
     *
     * @param Request $request
     * @param Assistance $assistance
     *
     * @return Response
     */
    public function getRandomBeneficiariesAction(Request $request, Assistance $assistance)
    {
        if ($request->query->get("size")) {
            $numberToDisplay = $request->query->get("size");

            /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
            $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');
            $receivers = $distributionBeneficiaryService->getRandomBeneficiaries($assistance, $numberToDisplay);

            $json = $this->get('serializer')
                ->serialize(
                    $receivers,
                    'json',
                    ['groups' => ['FullReceivers'], 'datetime_format' => 'd-m-Y']
                );
        } else {
            $json = $this->get('serializer')
                ->serialize(
                    "The size to display is unset",
                    'json',
                    ['groups' => ['FullReceivers'], 'datetime_format' => 'd-m-Y']
                );
        }


        return new Response($json);
    }

    /**
     * @Rest\Post("/distributions/{id}/validate")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Distribution validated",
     *     @Model(type=Assistance::class)
     * )
     *
     * @param Assistance $assistance
     *
     * @return Response
     */
    public function validateAction(Assistance $assistance)
    {
        /** @var DistributionService $distributionService */
        $distributionService = $this->get('distribution.distribution_service');
        $assistance = $distributionService->validateDistribution($assistance);

        $json = $this->get('serializer')
            ->serialize(
                $assistance,
                'json',
                ['groups' => ['FullDistribution'], 'datetime_format' => 'd-m-Y']
            );

        return new Response($json);
    }

    /**
     * Create a distribution.
     *
     * @Rest\Put("/distributions", name="add_distribution")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="object",
     *      required=true,
     *      description="Body of the request",
     * 	  @SWG\Schema(ref=@Model(type=Assistance::class))
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Project created",
     *     @Model(type=Assistance::class)
     * )
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createAction(Request $request)
    {
        $distributionArray = $request->request->all();
        $threshold = $distributionArray['threshold'];

        try {
            $listReceivers = $this->get('distribution.distribution_service')
                ->create($distributionArray['__country'], $distributionArray, $threshold);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('serializer')
            ->serialize(
                $listReceivers,
                'json',
                ['groups' => ['FullReceivers', 'FullDistribution'], 'datetime_format' => 'd-m-Y']
            );

        return new Response($json);
    }

    /**
     * @Rest\Put("/distributions/{id}/beneficiary", name="add_beneficiary_in_distribution")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
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
     * @param Assistance $assistance
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function addBeneficiaryAction(Request $request, Assistance $assistance)
    {
        $data = $request->request->all();

        try {
            /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
            $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');
            $distributionBeneficiary = $distributionBeneficiaryService->addBeneficiary($assistance, $data);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('serializer')
            ->serialize(
                $distributionBeneficiary,
                'json',
                ['groups' => [
                    'FullDistributionBeneficiary',
                    'FullDistribution',
                    'FullBeneficiary',
                ]]
            );

        return new Response($json);
    }

    /**
     * @Rest\Post("/distributions/{distributionId}/beneficiaries/{beneficiaryId}/remove", name="remove_one_beneficiary_in_distribution")
     * @ParamConverter("distribution", options={"mapping": {"distributionId" : "id"}})
     * @ParamConverter("beneficiary", options={"mapping": {"beneficiaryId" : "id"}})
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return if the beneficiary specified has been remove"
     * )
     *
     * @param Request $request
     * @param Assistance $distribution
     * @param Beneficiary $beneficiary
     *
     * @return Response
     */
    public function removeOneBeneficiaryAction(Request $request, Assistance $distribution, Beneficiary $beneficiary)
    {
        $deletionData = $request->request->all();

        /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
        $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');

        $return = $distributionBeneficiaryService->removeBeneficiaryInDistribution($distribution, $beneficiary, $deletionData);

        return new Response(json_encode($return));
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
     *          @SWG\Items(ref=@Model(type=Assistance::class, groups={"SmallDistribution"}))
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

        $assistanceFactory = $this->get('distribution.distribution_data_output_factory');
        $data = [];
        foreach ($distributions as $assistance) {
            $data[] = $assistanceFactory->build($assistance, ['SmallDistribution']);
        }

        $json = $this->get('serializer')
            ->serialize(
                $data,
                'json',
                ['groups' => ['SmallDistribution'], 'datetime_format' => 'd-m-Y']
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
     *     @Model(type=Assistance::class, groups={"SmallDistribution"})
     * )
     *
     * @param Assistance $assistance
     *
     * @return Response
     */
    public function getOneAction(Assistance $assistance)
    {
        $assistanceFactory = $this->get('distribution.distribution_data_output_factory');
        $json = $this->get('serializer')
            ->serialize(
                $assistanceFactory->build($assistance, ['FullDistribution']),
                'json',
                ['groups' => ['FullDistribution'], 'datetime_format' => 'd-m-Y']
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
     * @param Assistance $assistance
     * @return Response
     */
    public function getDistributionBeneficiariesAction(Assistance $assistance)
    {
        /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
        $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');
        $distributionBeneficiaries = $distributionBeneficiaryService->getDistributionBeneficiaries($assistance);

        $dateCallback = function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
            return $innerObject instanceof \DateTime ? $innerObject->format('d-m-Y') : '';
        };
        $dateTimeCallback = function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
            return $innerObject instanceof \DateTime ? $innerObject->format('d-m-Y H:i:s') : '';
        };

        $json = $this->get('serializer')
            ->serialize(
                $distributionBeneficiaries,
                'json',
                [
                    'groups' => ["ValidatedDistribution"],
                    'datetime_format' => 'd-m-Y',
                ]
            );

        return new Response($json);
    }

    /**
     * Get all beneficiaries of a distribution.
     *
     * @Rest\Get("/offline-app/v1/distributions/{id}/beneficiaries")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Offline App")
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
     * @param Assistance $assistance
     * @return Response
     */
    public function offlineGetDistributionBeneficiariesAction(Assistance $assistance)
    {
        return $this->getDistributionBeneficiariesAction($assistance);
    }

    /**
     * Get beneficiaries of a distribution without booklets.
     *
     * @Rest\Get("/distributions/{id}/assignable-beneficiaries", name="get_assignable_beneficiaries_distribution", requirements={"id"="\d+"})
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
     * @param Assistance $assistance
     * @return Response
     */
    public function getDistributionAssignableBeneficiariesAction(Assistance $assistance)
    {
        /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
        $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');
        $distributionBeneficiaries = $distributionBeneficiaryService->getDistributionAssignableBeneficiaries($assistance);
        
        $json = $this->get('serializer')
            ->serialize(
                $distributionBeneficiaries,
                'json',
                ['groups' => ["ValidatedDistribution"], 'datetime_format' => 'd-m-Y H:m:i']
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
     *     name="assistance",
     *     in="body",
     *     required=true,
     *     @Model(type=Assistance::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="distribution updated",
     *     @Model(type=Assistance::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request          $request
     * @param Assistance $assistance
     *
     * @return Response
     */
    public function updateAction(Request $request, Assistance $assistance)
    {
        $distributionArray = $request->request->all();
        try {
            $assistance = $this->get('distribution.distribution_service')
                ->edit($assistance, $distributionArray);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('serializer')
            ->serialize($assistance, 'json', ['groups' => ['FullDistribution'], 'datetime_format' => 'd-m-Y']);
        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Archive a distribution.
     *
     * @Rest\Post("/distributions/{id}/archive", name="archived_project")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
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
     * @param Assistance $distribution
     *
     * @return Response
     */
    public function archiveAction(Assistance $distribution)
    {
        try {
            $archivedDistribution = $this->get('distribution.distribution_service')
                ->archived($distribution);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        $json = $this->get('serializer')
            ->serialize($archivedDistribution, 'json');

        return new Response($json, Response::HTTP_OK);
    }

    /**
    * Complete a distribution.
    *
    * @Rest\Post("/distributions/{id}/complete", name="completed_project")
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
    * @param Assistance $distribution
    *
    * @return Response
    */
    public function completeAction(Assistance $distribution)
    {
        try {
            $completedDistribution = $this->get('distribution.distribution_service')
                ->complete($distribution);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        $json = $this->get('serializer')
            ->serialize($completedDistribution, 'json');

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
     *     description="OK",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Assistance::class, groups={"SmallDistribution"}))
     *     )
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

        $assistanceFactory = $this->get('distribution.distribution_data_output_factory');
        $data = [];
        foreach ($filtered as $assistance) {
            $data[] = $assistanceFactory->build($assistance, ['SmallDistribution']);
        }

        $json = $this->get('serializer')
            ->serialize(
                $data,
                'json',
                ['groups' => ['SmallDistribution'], 'datetime_format' => 'd-m-Y']
            );

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Get distributions of one project.
     *
     * @Rest\Get("/offline-app/v1/projects/{id}/distributions")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ', project)")
     *
     * @SWG\Tag(name="Distributions")
     * @SWG\Tag(name="Offline App")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Assistance::class, groups={"SmallDistribution"}))
     *     )
     * )
     *
     * @SWG\Response(response=400, description="BAD_REQUEST")
     *
     * @param Project $project
     *
     * @return Response
     */
    public function offlineGetDistributionsAction(Project $project)
    {
        return $this->getDistributionsAction($project);
    }

    /**
     * Get distributions with qr voucher commodity of one project.
     *
     * @Rest\Get("/distributions-qr-voucher/projects/{id}", name="get_distributions_qr_voucher_of_project")
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
    public function getQrVoucherDistributionsAction(Project $project)
    {
        try {
            $distributions = $project->getDistributions();
            $filtered = $this->get('distribution.distribution_service')->filterQrVoucherDistributions($distributions);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('serializer')
            ->serialize(
                $filtered,
                'json',
                ['groups' => ['FullDistribution'], 'datetime_format' => 'd-m-Y']
            );

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Import beneficiaries of one distribution.
     *
     * @Rest\Post("/import/beneficiaries/distributions/{id}", name="import_beneficiaries_distribution")
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
     * @param Assistance $assistance
     *
     * @return Response
     */
    public function importBeneficiariesAction(Request $request, Assistance $assistance)
    {
        /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
        $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');
        $beneficiaries = $distributionBeneficiaryService->getBeneficiaries($assistance);

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
                    $return = $distributionCsvService->parseCSV($countryIso3, $beneficiaries, $assistance, $request->files->get('file'));
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
                    $return = $distributionCsvService->saveCSV($countryIso3, $assistance, $data);
                } catch (\Exception $e) {
                    return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                $return = 'An error occured, please check the body';
            }

            $json = $this->get('serializer')
                ->serialize($return, 'json', ['groups' => ['FullHousehold'], 'datetime_format' => 'd-m-Y']);

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
        if (!$request->request->has('target')) {
            return new Response('You must defined a target', 500);
        }

        $target = $request->request->get('target');

        try {
            $beneficiariesInProject = $distributionBeneficiaryService->getAllBeneficiariesInProject($project, $target);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('serializer')
        ->serialize($beneficiariesInProject, 'json', ['groups' => ['FullHousehold'], 'datetime_format' => 'd-m-Y']);

        return new Response($json, Response::HTTP_OK);
    }
    
    /**
     * Edit general relief item
     *
     * @Rest\Post("/distributions/generalrelief/notes", name="edit_general_relief_notes")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="General Relief")
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
     * @param  Request           $request
     * @return Response
     */
    public function editGeneralReliefNotesAction(Request $request)
    {
        $generalReliefs = $request->request->get('generalReliefs');
        try {
            foreach ($generalReliefs as $generalRelief) {
                $this->get('distribution.distribution_service')
                ->editGeneralReliefItemNotes($generalRelief['id'], $generalRelief['notes']);
            }
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Set general relief items as distributed
     *
     * @Rest\Post("/distributions/generalrelief/distributed", name="distribute_general_relief")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_ASSIGN')")
     *
     * @SWG\Tag(name="General Relief")
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
     * @param  Request           $request
     * @return Response
     */
    public function setGeneralReliefItemsAsDistributedAction(Request $request)
    {
        $griIds = $request->request->get('ids');

        try {
            $response = $this->get('distribution.distribution_service')
                ->setGeneralReliefItemsAsDistributed($griIds);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        
        $json = $this->get('serializer')
            ->serialize(
                $response,
                'json',
                ['groups' => ["ValidatedDistribution"], 'datetime_format' => 'd-m-Y H:m:i']
            );

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Set general relief items as distributed.
     *
     * @Rest\Post("/offline-app/v1/distributions/generalrelief/distributed")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_ASSIGN')")
     *
     * @SWG\Tag(name="Offline App")
     * @SWG\Tag(name="General Relief")
     *
     * @SWG\Response(response=200, description="OK")
     * @SWG\Response(response=400, description="BAD_REQUEST")
     *
     * @param  Request  $request
     * @return Response
     */
    public function offlineSetGeneralReliefItemsAsDistributedAction(Request $request)
    {
        return $this->setGeneralReliefItemsAsDistributedAction($request);
    }
}
