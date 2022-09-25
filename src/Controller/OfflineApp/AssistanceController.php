<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Repository\AssistanceRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use Enum\ModalityType;
use InputType\AssistanceByProjectOfflineAppFilterInputType;
use Entity\Project;
use Serializer\MapperInterface;
use Symfony\Component\HttpFoundation\Response;

class AssistanceController extends AbstractOfflineAppController
{
    /**
     * @var AssistanceRepository
     */
    private $assistanceRepository;

    public function __construct(
        AssistanceRepository $assistanceRepository
    ) {
        $this->assistanceRepository = $assistanceRepository;
    }

    /**
     * @Rest\Get("/offline-app/{version}/projects/{id}/distributions")
     *
     * @param string $version
     * @param Project $project
     *
     * @return void
     */
    public function projectAssistances(string $version, Project $project): Response
    {
        if (!in_array($version, ['v1', 'v2'])) {
            throw $this->createNotFoundException("Endpoint in version $version is not supported");
        }

        $filter = new AssistanceByProjectOfflineAppFilterInputType();
        $filter->setFilter([
            'completed' => '0',
            'notModalityTypes' => [ModalityType::MOBILE_MONEY],
        ]);

        $assistances = $this->assistanceRepository->findByProjectInOfflineApp($project, $project->getCountryIso3(), $filter);

        return $this->json($assistances, Response::HTTP_OK, [], [
            MapperInterface::NEW_API => false, //workaround to be able to match right mapper. Will be (hopefully) done better in the future. See a techdebt topic about mappers context.
            'version' => $version,
        ]);
    }
}
