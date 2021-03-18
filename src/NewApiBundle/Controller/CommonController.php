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
use Symfony\Component\Intl\Intl;

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
    /** @var array */
    private $iconGroups;

    /**
     * CommonController constructor.
     *
     * @param BeneficiaryService  $beneficiaryService
     * @param ProjectService      $projectService
     * @param AssistanceService   $assistanceService
     * @param TranslatorInterface $translator
     * @param array               $iconGroups
     */
    public function __construct(BeneficiaryService $beneficiaryService, ProjectService $projectService, AssistanceService $assistanceService,
                                TranslatorInterface $translator,
                                array $iconGroups
    )
    {
        $this->beneficiaryService = $beneficiaryService;
        $this->projectService = $projectService;
        $this->assistanceService = $assistanceService;
        $this->translator = $translator;
        $this->iconGroups = $iconGroups;
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

        foreach ($this->iconGroups as $iconGroup) {
            foreach ($iconGroup as $key => $svg) {
                $data[] = ['key' => $key, 'svg' => $svg];
            }
        }

        return $this->json($data);
    }

    /**
     * @Rest\Get("/languages")
     *
     * @return JsonResponse
     */
    public function languages(): JsonResponse
    {
        $data = [];

        foreach ($this->getParameter('app.locales') as $locale) {
            $data[] = [
                'code' => $locale,
                'value' => $this->get('translator')->trans($locale, [], null, $locale),
            ];
        }

        return $this->json($data);
    }

    /**
     * @Rest\Get("/currencies")
     *
     * @return JsonResponse
     */
    public function currencies(): JsonResponse
    {
        $data = [];

        foreach ($this->getParameter('app.currencies') as $currency) {
            $data[] = [
                'code' => $currency,
                'value' => Intl::getCurrencyBundle()->getCurrencyName($currency),
            ];
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
        if (!in_array($language, $this->getParameter('app.locales'))) {
            throw $this->createNotFoundException('Locale '.$language.' does not exists.');
        }

        $data = [];

        foreach ($this->translator->getCatalogue($language)->all('messages') as $key => $value) {
            $data[$key] = $value;
        }

        return $this->json($data);
    }
}
