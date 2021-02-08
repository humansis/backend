<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\BeneficiaryService;
use CommonBundle\Pagination\Paginator;
use DistributionBundle\Utils\AssistanceService;
use FOS\RestBundle\Controller\Annotations as Rest;
use ProjectBundle\Utils\ProjectService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class CommonController extends AbstractController
{
    /** @var BeneficiaryService */
    private $beneficiaryService;
    /** @var ProjectService */
    private $projectService;
    /** @var AssistanceService */
    private $assistanceService;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * CommonController constructor.
     *
     * @param BeneficiaryService $beneficiaryService
     * @param ProjectService     $projectService
     * @param AssistanceService  $assistanceService
     * @param TranslatorInterface         $translator
     */
    public function __construct(BeneficiaryService $beneficiaryService, ProjectService $projectService, AssistanceService $assistanceService,
                                TranslatorInterface $translator
    )
    {
        $this->beneficiaryService = $beneficiaryService;
        $this->projectService = $projectService;
        $this->assistanceService = $assistanceService;
        $this->translator = $translator;
    }

    /**
     * @Rest\Get("/summaries")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function summaries(Request $request): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $result = [];
        foreach ($request->query->get('code', []) as $code) {
            switch ($code) {
                case 'total_registrations':
                    $result[] = ['code' => $code, 'value' => $this->beneficiaryService->countAll($countryIso3)];
                    break;
                case 'active_projects':
                    $result[] = ['code' => $code, 'value' => $this->projectService->countActive($countryIso3)];
                    break;
                case 'enrolled_beneficiaries':
                    $result[] = ['code' => $code, 'value' => $this->getDoctrine()->getRepository(Household::class)->countUnarchivedByCountryProjects($countryIso3)];
                    break;
                case 'served_beneficiaries':
                    $result[] = ['code' => $code, 'value' => $this->beneficiaryService->countAllServed($countryIso3)];
                    break;
                case 'completed_assistances':
                    $result[] = ['code' => $code, 'value' => $this->assistanceService->countCompleted($countryIso3)];
                    break;
                default:
                    throw new BadRequestHttpException('Invalid query parameter code.'.$code);
            }
        }

        return $this->json(new Paginator($result));
    }

    /**
     * @Rest\Get("/icons")
     *
     * @return JsonResponse
     */
    public function icons(): JsonResponse
    {
        $data = [];

        foreach ($this->getParameter('icons_modality_types') as $key => $svg) {
            $data[] = ['key' => $key, 'svg' => $svg];
        }

        foreach ($this->getParameter('icons_sectors') as $key => $svg) {
            $data[] = ['key' => $key, 'svg' => $svg];
        }

        return $this->json($data);
    }

    /**
     * @Rest\Get("/translations/{language}")
     *
     * @param string $language
     *
     * @return JsonResponse
     */
    public function translations(string $language): JsonResponse
    {
        $data = [];

        foreach ($this->translator->getCatalogue($language)->all('messages') as $key => $value) {
            $data[$key] = $value;
        }

        return $this->json($data);
    }
}
