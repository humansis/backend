<?php

declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use NewApiBundle\MapperDeprecated\AssistanceMapper;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\AssistanceRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Controller\AbstractController;
use NewApiBundle\Enum\ModalityType;
use NewApiBundle\InputType\AssistanceByProjectOfflineAppFilterInputType;
use NewApiBundle\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;

class AssistanceController extends AbstractController
{
    /**
     * @var AssistanceRepository
     */
    private $assistanceRepository;

    /**
     * @var AssistanceMapper
     */
    private $assistanceMapper;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(
        AssistanceRepository $assistanceRepository,
        AssistanceMapper     $assistanceMapper,
        SerializerInterface  $serializer
    ) {
        $this->assistanceRepository = $assistanceRepository;
        $this->assistanceMapper = $assistanceMapper;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    protected function json($data, $status = 200, $headers = [], $context = []): JsonResponse
    {
        if (!isset($context['offline-app'])) {
            $context['offline-app'] = true;
        }

        return parent::json($data, $status, $headers, $context);
    }

    /**
     * @deprecated This endpoint is not consumed by app because of different interface
     *
     * @Rest\Get("/offline-app/v2/projects/{id}/assistances")
     *
     * @param Project                                      $project
     * @param AssistanceByProjectOfflineAppFilterInputType $filter
     * @param Request                                      $request
     *
     * @return JsonResponse
     */
    public function projectAssistances(Project $project, AssistanceByProjectOfflineAppFilterInputType $filter, Request $request): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $fn = function ($carry, Assistance $assistance) {
            return $carry.$assistance->getId().',';
        };

        /** @var AssistanceRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Assistance::class);
        $assistances = $repository->findByProjectInOfflineApp($project, $countryIso3, $filter);
        $hash = array_reduce($assistances, $fn, '');

        $response = $this->json($assistances);
        $response->setEtag(md5($hash));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Get("/offline-app/v1/projects/{id}/distributions")
     *
     * @param Project $project
     *
     * @return void
     *
     * @deprecated This is only to preserve actually using interface on offline app and prevent before dropping as old endpoint
     * method self::projectAssistances should be revised and used instead in next release
     * old method was located in DistributionBundle\Controller\AssistanceController::offlineGetDistributionsAction
     */
    public function oldProjectAssistances(Project $project): Response
    {
        $filter = new AssistanceByProjectOfflineAppFilterInputType();
        $filter->setFilter([
            'completed' => '0',
            'notModalityTypes' => [ModalityType::MOBILE_MONEY],
        ]);

        $assistances = $this->assistanceRepository->findByProjectInOfflineApp($project, $project->getIso3(), $filter);
        $json = $this->serializer->serialize(
            $this->assistanceMapper->toOldMobileArrays($assistances),
            'json',
            ['groups' => ['SmallAssistance'], 'datetime_format' => 'd-m-Y']
        );

        return new Response($json, Response::HTTP_OK);
    }
}
